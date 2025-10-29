import React, { useState, useRef, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { Building, Lock, Mail, Phone, MapPin, User, Briefcase, GraduationCap, Loader2, AlertTriangle, ArrowLeft } from 'lucide-react';
import { maskCEP, maskCNPJ, maskPhone, maskFixo } from '../utils/masks';

// ===================================
// TIPOS E DEFINIÇÕES
// ===================================

type InstituicaoFormData = {
    nome_fantasia: string;
    razao_social: string;
    cnpj: string;
    email: string; // Email de login
    senha: string;
    confirmar_senha: string;
    codigo_inep: string; 
    tipo_instituicao: string; 
    niveis_oferecidos: string[]; 
    email_corporativo: string; 
    celular_corporativo: string; 
    telefone_fixo: string; 
    nome_responsavel: string; 
    funcao_responsavel: string; 
    cep: string; 
    logradouro: string;
    numero: string; 
    bairro: string; 
    cidade: string; 
    estado: string; 
    complemento: string;
    ponto_referencia: string;
};

type FieldErrors = Partial<Record<keyof InstituicaoFormData | 'api', string>>;

const TIPO_INSTITUICAO_OPCOES = [
    { value: 'Pública Municipal', label: 'Pública Municipal' },
    { value: 'Pública Estadual', label: 'Pública Estadual' },
    { value: 'Pública Federal', label: 'Pública Federal' },
    { value: 'Privada', label: 'Privada' },
    { value: 'Filantrópica', label: 'Filantrópica' },
];

const NIVEIS_OFERECIDOS_OPCOES = [
    { value: 'Ed. Infantil', label: 'Ed. Infantil' },
    { value: 'Fundamental I', label: 'Fundamental I' },
    { value: 'Fundamental II', label: 'Fundamental II' },
    { value: 'Ensino Médio', label: 'Ensino Médio' },
    { value: 'Técnica', label: 'Técnica' },
    { value: 'Superior', label: 'Superior' },
    { value: 'EJA', label: 'EJA' },
];

// Estado Inicial
const initialInstituicaoState: InstituicaoFormData = {
    nome_fantasia: '', razao_social: '', cnpj: '', email: '', senha: '', confirmar_senha: '',
    codigo_inep: '', tipo_instituicao: '', niveis_oferecidos: [], email_corporativo: '',
    celular_corporativo: '', telefone_fixo: '', nome_responsavel: '', funcao_responsavel: '',
    cep: '', logradouro: '', numero: '', bairro: '', cidade: '', estado: '',
    complemento: '', ponto_referencia: '',
};

// ===================================
// MÁSCARAS E AUXILIARES (Reutilizadas do Candidato)
// ===================================

const ErrorText: React.FC<{ id: string; message?: string }> = ({ id, message }) => (
    message ? (<p id={id} className="error-text">{message}</p>) : null
);
function senhaValida(s: string) {
    // Mínimo 8, ao menos uma letra e um número [cite: Documentação final.docx]
    return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(s);
}

// ===================================
// LÓGICA DA PÁGINA
// ===================================

const RegisterInstituicaoPage: React.FC = () => {
    const navigate = useNavigate();
    const [instituicaoForm, setInstituicaoForm] = useState(initialInstituicaoState);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<FieldErrors>({});
    const alertRef = useRef<HTMLDivElement>(null);

    // Validações alinhadas com o RegisterInstituicaoRequest.php
    const validar = (): FieldErrors => {
        const e: FieldErrors = {};
        
        // 1. Dados Básicos e Legais
        if (!instituicaoForm.nome_fantasia.trim()) e.nome_fantasia = 'O Nome Fantasia é obrigatório.';
        if (!instituicaoForm.razao_social.trim()) e.razao_social = 'A Razão Social é obrigatória.';
        if (instituicaoForm.cnpj.replace(/\D/g, '').length !== 14) e.cnpj = 'CNPJ deve ter 14 dígitos.';
        
        // 2. Classificação
        if (!instituicaoForm.tipo_instituicao) e.tipo_instituicao = 'O Tipo de Instituição é obrigatório.';
        if (instituicaoForm.niveis_oferecidos.length === 0) e.niveis_oferecidos = 'Selecione ao menos um Nível.';
        
        // 3. Contatos
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(instituicaoForm.email)) e.email = 'Email de Login inválido.';
        if (!instituicaoForm.email_corporativo.trim()) e.email_corporativo = 'Email Corporativo é obrigatório.';
        if (instituicaoForm.celular_corporativo.replace(/\D/g, '').length < 10) e.celular_corporativo = 'Celular Corporativo inválido.';
        
        // 4. Responsável
        if (!instituicaoForm.nome_responsavel.trim()) e.nome_responsavel = 'Nome do Responsável é obrigatório.';
        if (!instituicaoForm.funcao_responsavel.trim()) e.funcao_responsavel = 'A Função do Responsável é obrigatória.';
        
        // 5. Endereço
        if (instituicaoForm.cep.replace(/\D/g, '').length !== 8) e.cep = 'CEP inválido.';
        if (!instituicaoForm.numero.trim()) e.numero = 'O Número é obrigatório.';
        if (!instituicaoForm.bairro.trim()) e.bairro = 'O Bairro é obrigatório.';
        if (!instituicaoForm.cidade.trim()) e.cidade = 'A Cidade é obrigatória.';
        if (!instituicaoForm.estado.trim()) e.estado = 'O Estado é obrigatório.';

        // 6. Segurança
        if (!senhaValida(instituicaoForm.senha)) e.senha = 'Senha deve ter no mínimo oito caracteres com letras e números.';
        if (instituicaoForm.senha !== instituicaoForm.confirmar_senha) e.confirmar_senha = 'As senhas não coincidem.';
        
        return e;
    };
    
    // --- HANDLERS ---
    const handleInstituicaoChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value, type } = e.target;
        
        let newValue = value;
        // Aplicação de máscaras
        if (name === 'cnpj') newValue = maskCNPJ(value);
        if (name === 'cep') newValue = maskCEP(value);
        if (name === 'celular_corporativo') newValue = maskPhone(value);
        if (name === 'telefone_fixo') newValue = maskFixo(value);
        
        setInstituicaoForm(prev => {
            // Lógica para CheckboxGroup (niveis_oferecidos)
            if (name === 'niveis_oferecidos' && type === 'checkbox') {
                const checked = (e.target as HTMLInputElement).checked;
                const currentNiveis = prev.niveis_oferecidos as string[];
                const newNiveis = checked
                    ? [...currentNiveis, value]
                    : currentNiveis.filter(n => n !== value);
                return { ...prev, [name]: newNiveis };
            }
            return { ...prev, [name]: newValue };
        });

        setErrors(prev => ({ ...prev, [name]: undefined }));
    };

    const handleCepBlur = async () => {
        const digits = instituicaoForm.cep.replace(/\D/g, '');
        if (digits.length !== 8) return; 

        try {
            const res = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
            const data = await res.json();
            
            if (data?.erro) {
                setErrors((prev) => ({ ...prev, cep: 'CEP não encontrado.' }));
                return;
            }
            setInstituicaoForm((prev) => ({
                ...prev,
                logradouro: data.logradouro || '',
                bairro: data.bairro || '',
                cidade: data.localidade || '',
                estado: data.uf || '',
            }));
        } catch {
            // Silencioso em caso de falha de rede
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        const eMap = validar();
        setErrors(eMap);
        if (Object.keys(eMap).length > 0) {
            alertRef.current?.focus();
            return;
        }

        setLoading(true);

        try {
            // Mapeamento e limpeza de máscaras antes do envio
            const payload = {
                ...instituicaoForm,
                nome: instituicaoForm.nome_fantasia, // Mapeamento nome_fantasia -> nome (Tabela User)
                cnpj: instituicaoForm.cnpj.replace(/\D/g, ''),
                cep: instituicaoForm.cep.replace(/\D/g, ''),
                celular_corporativo: instituicaoForm.celular_corporativo.replace(/\D/g, ''),
                telefone_fixo: instituicaoForm.telefone_fixo.replace(/\D/g, ''),
                
                // Mapeamento de arrays e exclusão de confirmação
                confirmar_senha: undefined,
                niveis_oferecidos: instituicaoForm.niveis_oferecidos, 
            };
            
            // POST /auth/register/instituicao [cite: Documentação final.docx]
            await api.post('/auth/register/instituicao', payload);
            
            // Sucesso: redireciona para login
            navigate('/login?success=instituicao');
        } catch (error: any) {
            console.error('Erro de registro:', error);
            let erroMsg = 'Ocorreu um erro ao processar seu cadastro.';
            
            if (error.response?.data?.errors) {
                const laravelErrors = error.response.data.errors;
                const fieldErrors: FieldErrors = {};

                // Mapeamento e mensagens de erro
                Object.entries(laravelErrors).forEach(([key, messages]) => {
                    const msg = Array.isArray(messages) ? messages[0] : 'Erro de validação.';
                    
                    if (key === 'nome') fieldErrors.nome_fantasia = msg;
                    else if (key === 'niveis_oferecidos') fieldErrors.niveis_oferecidos = 'Selecione ao menos um Nível Oferecido.';
                    else if (key in instituicaoForm) (fieldErrors as any)[key] = msg;
                    else erroMsg = msg; // Erro geral (ex: 'O email já existe.')
                });
                
                setErrors({ ...fieldErrors, api: erroMsg });
            } else if (error.message) {
                setErrors({ api: error.message });
            }
            alertRef.current?.focus();
        } finally {
            setLoading(false);
        }
    };

    return (
        // Usa 'auth-container' para layout centralizado
        <main className="auth-container" aria-labelledby="titulo-cadastro-instituicao"> 
            
            <div className="card-auth-large">
                
                <h1
                    id="titulo-cadastro-instituicao"
                    className="heading-secondary mb-md text-center"
                    tabIndex={-1}
                >
                    Cadastro de Instituição de Ensino
                </h1>
                
                <p className="text-sm text-muted text-center mb-lg">
                    Preencha os dados legais e de contato da sua instituição.
                </p>

                {/* Alerta de Erro Geral */}
                {errors.api && (
                    <div
                        ref={alertRef}
                        role="alert"
                        tabIndex={-1}
                        className={`alert alert-error mb-md`}
                    >
                        <AlertTriangle size={20} className="inline mr-sm" />
                        {errors.api}
                    </div>
                )}
                
                <form
                    onSubmit={handleSubmit}
                    className="space-y-lg"
                    aria-label="Formulário de cadastro de instituição"
                    noValidate
                >
                    {/* SEÇÃO 1: DADOS LEGAIS */}
                    <fieldset className="fieldset-group">
                        <legend className="title-md fieldset-legend">1. Dados Legais e Classificação</legend>
                        
                        <div className="form-grid-2">
                            <div className="form-group">
                                <label htmlFor="nome_fantasia" className="form-label">Nome Fantasia</label>
                                <div className="form-input-icon-wrapper">
                                    <Building size={20} className="form-icon" />
                                    <input id="nome_fantasia" name="nome_fantasia" type="text" required value={instituicaoForm.nome_fantasia} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.nome_fantasia} />
                                </div>
                                <ErrorText id="erro-nome_fantasia" message={errors.nome_fantasia} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="razao_social" className="form-label">Razão Social</label>
                                <input id="razao_social" name="razao_social" type="text" required value={instituicaoForm.razao_social} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.razao_social} />
                                <ErrorText id="erro-razao_social" message={errors.razao_social} />
                            </div>

                            <div className="form-group">
                                <label htmlFor="cnpj" className="form-label">CNPJ</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="cnpj" name="cnpj" type="text" required value={instituicaoForm.cnpj} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.cnpj} inputMode="numeric" />
                                </div>
                                <ErrorText id="erro-cnpj" message={errors.cnpj} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="codigo_inep" className="form-label">Código INEP/MEC (Opcional)</label>
                                <input id="codigo_inep" name="codigo_inep" type="text" value={instituicaoForm.codigo_inep} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.codigo_inep} />
                                <ErrorText id="erro-codigo_inep" message={errors.codigo_inep} />
                            </div>
                        </div>

                        <div className="form-group mt-md">
                            <label htmlFor="tipo_instituicao" className="form-label">Tipo de Instituição</label>
                            <div className="form-input-icon-wrapper">
                                <Briefcase size={20} className="form-icon" />
                                <select id="tipo_instituicao" name="tipo_instituicao" required value={instituicaoForm.tipo_instituicao} onChange={handleInstituicaoChange} className="form-select with-icon" aria-invalid={!!errors.tipo_instituicao}>
                                    <option value="">Selecione o Tipo de Instituição</option>
                                    {TIPO_INSTITUICAO_OPCOES.map((op) => (<option key={op.value} value={op.value}>{op.label}</option>))}
                                </select>
                            </div>
                            <ErrorText id="erro-tipo_instituicao" message={errors.tipo_instituicao} />
                        </div>
                        
                        <div className="form-group mt-md" role="group" aria-labelledby="niveis-oferecidos-label">
                            <label id="niveis-oferecidos-label" className="form-label">Níveis Oferecidos <span className="text-muted">(Selecione ao menos um)</span></label>
                            <div className="flex-wrap gap-md mt-xs">
                                {NIVEIS_OFERECIDOS_OPCOES.map((op) => (
                                    <label key={op.value} className="checkbox-label text-sm" style={{ fontWeight: 500 }}>
                                        <input
                                            type="checkbox"
                                            name="niveis_oferecidos"
                                            value={op.value}
                                            checked={instituicaoForm.niveis_oferecidos.includes(op.value)}
                                            onChange={handleInstituicaoChange}
                                            className="form-checkbox"
                                        />
                                        {op.label}
                                    </label>
                                ))}
                            </div>
                            <ErrorText id="erro-niveis_oferecidos" message={errors.niveis_oferecidos} />
                        </div>
                    </fieldset>

                    {/* SEÇÃO 2: CONTATOS E ACESSO */}
                    <fieldset className="fieldset-group">
                        <legend className="title-md fieldset-legend">2. Contatos e Acesso</legend>

                        <div className="form-grid-2">
                            <div className="form-group">
                                <label htmlFor="email" className="form-label">Email de Login</label>
                                <div className="form-input-icon-wrapper">
                                    <Mail size={20} className="form-icon" />
                                    <input id="email" name="email" type="email" required value={instituicaoForm.email} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.email} autoComplete="email" />
                                </div>
                                <ErrorText id="erro-email" message={errors.email} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="email_corporativo" className="form-label">Email Corporativo</label>
                                <input id="email_corporativo" name="email_corporativo" type="email" required value={instituicaoForm.email_corporativo} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.email_corporativo} autoComplete="email" />
                                <ErrorText id="erro-email_corporativo" message={errors.email_corporativo} />
                            </div>

                            <div className="form-group">
                                <label htmlFor="celular_corporativo" className="form-label">Celular Corporativo</label>
                                <div className="form-input-icon-wrapper">
                                    <Phone size={20} className="form-icon" />
                                    <input id="celular_corporativo" name="celular_corporativo" type="tel" required value={instituicaoForm.celular_corporativo} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.celular_corporativo} inputMode="numeric" />
                                </div>
                                <ErrorText id="erro-celular_corporativo" message={errors.celular_corporativo} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="telefone_fixo" className="form-label">Telefone Fixo (Opcional)</label>
                                <input id="telefone_fixo" name="telefone_fixo" type="tel" value={instituicaoForm.telefone_fixo} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.telefone_fixo} inputMode="numeric" />
                                <ErrorText id="erro-telefone_fixo" message={errors.telefone_fixo} />
                            </div>
                            
                            {/* Segurança */}
                            <div className="form-group mt-md">
                                <label htmlFor="senha" className="form-label">Senha</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="senha" name="senha" type="password" required value={instituicaoForm.senha} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.senha} autoComplete="new-password" />
                                </div>
                                <ErrorText id="erro-senha" message={errors.senha} />
                                <p className="text-xs text-muted mt-xs">Mínimo 8 caracteres com letras e números.</p>
                            </div>

                            <div className="form-group mt-md">
                                <label htmlFor="confirmar_senha" className="form-label">Confirmar Senha</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="confirmar_senha" name="confirmar_senha" type="password" required value={instituicaoForm.confirmar_senha} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.confirmar_senha} autoComplete="new-password" />
                                </div>
                                <ErrorText id="erro-confirmar_senha" message={errors.confirmar_senha} />
                            </div>
                        </div>
                    </fieldset>

                    {/* SEÇÃO 3: RESPONSÁVEL E ENDEREÇO */}
                    <fieldset className="fieldset-group">
                        <legend className="title-md fieldset-legend">3. Responsável e Endereço</legend>
                        
                        {/* Responsável */}
                        <div className="form-grid-2">
                            <div className="form-group">
                                <label htmlFor="nome_responsavel" className="form-label">Nome Completo do Responsável</label>
                                <div className="form-input-icon-wrapper">
                                    <User size={20} className="form-icon" />
                                    <input id="nome_responsavel" name="nome_responsavel" type="text" required value={instituicaoForm.nome_responsavel} onChange={handleInstituicaoChange} className="form-input with-icon" aria-invalid={!!errors.nome_responsavel} />
                                </div>
                                <ErrorText id="erro-nome_responsavel" message={errors.nome_responsavel} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="funcao_responsavel" className="form-label">Função do Responsável (Ex: Diretor(a))</label>
                                <input id="funcao_responsavel" name="funcao_responsavel" type="text" required value={instituicaoForm.funcao_responsavel} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.funcao_responsavel} />
                                <ErrorText id="erro-funcao_responsavel" message={errors.funcao_responsavel} />
                            </div>
                        </div>
                        
                        {/* Endereço */}
                        <div className="form-grid-3 mt-lg">
                            <div className="form-group col-span-3">
                                <label htmlFor="cep" className="form-label">CEP</label>
                                <div className="form-input-icon-wrapper">
                                    <MapPin size={20} className="form-icon" />
                                    <input id="cep" name="cep" type="text" inputMode="numeric" maxLength={10} required value={instituicaoForm.cep} onChange={handleInstituicaoChange} onBlur={handleCepBlur} className="form-input with-icon" aria-invalid={!!errors.cep} placeholder="00000-000" />
                                </div>
                                <ErrorText id="erro-cep" message={errors.cep} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="logradouro" className="form-label">Logradouro</label>
                                <input id="logradouro" name="logradouro" type="text" value={instituicaoForm.logradouro} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.logradouro} />
                                <ErrorText id="erro-logradouro" message={errors.logradouro} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="numero" className="form-label">Número</label>
                                <input id="numero" name="numero" type="text" required value={instituicaoForm.numero} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.numero} />
                                <ErrorText id="erro-numero" message={errors.numero} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="complemento" className="form-label">Complemento (Opcional)</label>
                                <input id="complemento" name="complemento" type="text" value={instituicaoForm.complemento} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.complemento} />
                                <ErrorText id="erro-complemento" message={errors.complemento} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="bairro" className="form-label">Bairro</label>
                                <input id="bairro" name="bairro" type="text" required value={instituicaoForm.bairro} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.bairro} />
                                <ErrorText id="erro-bairro" message={errors.bairro} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="cidade" className="form-label">Cidade</label>
                                <input id="cidade" name="cidade" type="text" required value={instituicaoForm.cidade} onChange={handleInstituicaoChange} className="form-input" aria-invalid={!!errors.cidade} />
                                <ErrorText id="erro-cidade" message={errors.cidade} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="estado" className="form-label">Estado</label>
                                <select id="estado" name="estado" required value={instituicaoForm.estado} onChange={handleInstituicaoChange} className="form-select" aria-invalid={!!errors.estado}>
                                    <option value="">UF</option>
                                    {ESTADOS_OPCOES.map(e => <option key={e} value={e}>{e}</option>)}
                                </select>
                                <ErrorText id="erro-estado" message={errors.estado} />
                            </div>

                            <div className="form-group col-span-3">
                                <label htmlFor="ponto_referencia" className="form-label">Ponto de Referência (Opcional)</label>
                                <textarea id="ponto_referencia" name="ponto_referencia" rows={2} value={instituicaoForm.ponto_referencia} onChange={handleInstituicaoChange} className="form-textarea" aria-invalid={!!errors.ponto_referencia} />
                                <ErrorText id="erro-ponto_referencia" message={errors.ponto_referencia} />
                            </div>
                        </div>
                    </fieldset>


                    {/* BOTÃO DE SUBMISSÃO */}
                    <button
                        type="submit"
                        disabled={loading}
                        className="btn-primary w-full btn-lg mt-xl"
                        aria-busy={loading}
                    >
                        {loading ? <Loader2 size={24} className="icon-spin mr-sm" /> : <Building size={24} className="mr-sm" />}
                        {loading ? 'Cadastrando...' : 'Cadastrar Instituição'}
                    </button>
                </form>
                
                {/* Link para voltar ao seletor de perfil */}
                <div className='mt-md text-center'>
                    <Link to="/register" className="btn-link text-sm btn-icon" type="button">
                        <ArrowLeft size={16} className="mr-xs" /> Voltar para a escolha de perfil
                    </Link>
                </div>
            </div>
        </main>
    );
};

export default RegisterInstituicaoPage;