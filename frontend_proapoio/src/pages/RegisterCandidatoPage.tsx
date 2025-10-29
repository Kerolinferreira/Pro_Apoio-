import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { User, Mail, Phone, MapPin, Lock, Briefcase, GraduationCap, Calendar, Loader2, AlertTriangle, ArrowLeft } from 'lucide-react';
import { maskCEP, maskCPF, maskPhone } from '../utils/masks';

// ===================================
// TIPOS E DEFINIÇÕES
// ===================================

type FormData = {
    nome_completo: string;
    email: string;
    telefone: string;
    cpf: string;
    data_nascimento: string;
    senha: string;
    confirmarSenha: string;
    cep: string;
    cidade: string;
    estado: string;
    escolaridade: string;
    curso: string;
    instituicao_ensino: string;
    experiencia: string; // Resumo da experiência
};

type FieldErrors = Partial<Record<keyof FormData, string>>;

const ESCOLARIDADE_OPCOES = [
    'Fundamental Completo',
    'Médio Completo',
    'Superior Incompleto',
    'Superior Completo',
    'Pós-Graduação',
    'Mestrado',
    'Doutorado',
];
const ESTADOS_OPCOES = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

// ===================================
// COMPONENTES AUXILIARES DE FORMULÁRIO (Para evitar repetição no JSX)
// ===================================

const ErrorText: React.FC<{ id: string; message?: string }> = ({ id, message }) => (
    message ? (
        <p id={id} className="error-text">
            {message}
        </p>
    ) : null
);


// ===================================
// LÓGICA DO COMPONENTE PRINCIPAL
// ===================================

export default function RegisterCandidatoPage() {
    const navigate = useNavigate();
    const [formData, setFormData] = useState<FormData>({
        nome_completo: '',
        email: '',
        telefone: '',
        cpf: '',
        data_nascimento: '',
        senha: '',
        confirmarSenha: '',
        cep: '',
        cidade: '',
        estado: '',
        escolaridade: '',
        curso: '',
        instituicao_ensino: '',
        experiencia: '',
    });

    const [mensagem, setMensagem] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<FieldErrors>({});
    const liveRef = useRef<HTMLDivElement>(null);
    const alertRef = useRef<HTMLDivElement>(null);
    const h1Ref = useRef<HTMLHeadingElement>(null);

    useEffect(() => {
        h1Ref.current?.focus();
    }, []);

    // Acessibilidade: anuncia mensagens para leitores de tela
    function announce(text: string) {
        if (liveRef.current) {
            liveRef.current.textContent = text;
        }
    }

    function handleChange(
        e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
    ) {
        const { name, value } = e.target;
        
        let newValue = value;
        // Aplicação de máscaras
        if (name === 'cep') newValue = maskCEP(value);
        if (name === 'cpf') newValue = maskCPF(value);
        if (name === 'telefone') newValue = maskPhone(value);

        setFormData((prev) => {
            const next = { ...prev, [name]: newValue };
            // Limpa erros ao digitar
            if (errors[name as keyof FormData]) {
                const copy = { ...errors };
                delete copy[name as keyof FormData];
                setErrors(copy);
            }
            return next;
        });
    }

    function senhaValida(s: string) {
        // Mínimo 8, ao menos uma letra e um número [cite: Documentação final.docx]
        return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(s);
    }

    function validar(): FieldErrors {
        const e: FieldErrors = {};
        
        if (formData.nome_completo.trim().length < 3) e.nome_completo = 'Nome completo é obrigatório (mínimo 3 caracteres).';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) e.email = 'E-mail inválido.';
        
        // Validação de campos obrigatórios com máscara
        if (formData.telefone.replace(/\D/g, '').length < 10) e.telefone = 'Telefone inválido.';
        if (formData.cpf.replace(/\D/g, '').length !== 11) e.cpf = 'CPF deve ter 11 dígitos.';
        if (!formData.data_nascimento.trim()) e.data_nascimento = 'Informe a data de nascimento.';
        
        // Senha
        if (!senhaValida(formData.senha)) e.senha = 'A senha deve ter no mínimo 8 caracteres com letras e números.';
        if (formData.senha !== formData.confirmarSenha) e.confirmarSenha = 'As senhas não coincidem.';
        
        // Endereço
        if (formData.cep.replace(/\D/g, '').length !== 8) e.cep = 'CEP inválido.';
        if (!formData.cidade.trim()) e.cidade = 'Informe a cidade.';
        if (!formData.estado.trim()) e.estado = 'Informe o estado.';
        
        // Escolaridade Condicional
        if (!formData.escolaridade) e.escolaridade = 'Selecione a escolaridade.';
        const exigeCurso = formData.escolaridade.includes('Superior') || ['Pós-Graduação', 'Mestrado', 'Doutorado'].includes(formData.escolaridade);
        if (exigeCurso) {
            if (!formData.curso.trim()) e.curso = 'Informe o curso.';
            if (!formData.instituicao_ensino.trim()) e.instituicao_ensino = 'Informe a instituição de ensino.';
        }
        
        // Experiência
        if (formData.experiencia.trim().length < 20) e.experiencia = 'Descreva sua experiência em mais detalhes (mínimo 20 caracteres).';
        
        return e;
    }

    async function handleCepBlur() {
        const digits = formData.cep.replace(/\D/g, '');
        if (digits.length !== 8) return; 

        try {
            // Chamada à API ViaCEP [cite: Documentação final.docx]
            const res = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
            const data = await res.json();
            
            if (data?.erro) {
                setErrors((prev) => ({ ...prev, cep: 'CEP não encontrado.' }));
                announce('CEP não encontrado.');
                return;
            }
            // Preenche os campos (cidade, estado)
            setFormData((prev) => ({
                ...prev,
                cidade: data.localidade || prev.cidade,
                estado: data.uf || prev.estado,
            }));
            announce('Endereço preenchido automaticamente.');
        } catch {
            announce('Falha ao consultar CEP.');
        }
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setMensagem(null);

        const eMap = validar();
        setErrors(eMap);
        if (Object.keys(eMap).length > 0) {
            setMensagem('Corrija os campos destacados.');
            setTimeout(() => alertRef.current?.focus(), 0);
            return;
        }

        setSubmitting(true);
        try {
            // Mapeamento: Chaves do front (ex: nome_completo) para chaves do back (ex: nome)
            const payload = {
                nome: formData.nome_completo,
                email: formData.email,
                telefone: formData.telefone.replace(/\D/g, ''), // Envia sem máscara
                cpf: formData.cpf.replace(/\D/g, ''), // Envia sem máscara
                data_nascimento: formData.data_nascimento,
                senha: formData.senha,
                senha_confirmation: formData.confirmarSenha, 
                
                cep: formData.cep.replace(/\D/g, ''), // Envia sem máscara
                cidade: formData.cidade,
                estado: formData.estado,
                
                nivel_escolaridade: formData.escolaridade, 
                curso_superior: formData.curso, 
                instituicao_ensino: formData.instituicao_ensino, 
                experiencia: formData.experiencia,
            };

            // POST /auth/register/candidato [cite: Documentação final.docx]
            const resposta = await api.post('/auth/register/candidato', payload);

            if (resposta.status === 201 || resposta.status === 200) {
                setMensagem('Cadastro realizado com sucesso. Você será redirecionado para o login.');
                announce('Cadastro realizado com sucesso.');
                // Redireciona após 2 segundos
                setTimeout(() => navigate('/login?success=candidato'), 2000); 
            } else {
                setMensagem('Erro inesperado no servidor. Tente novamente.');
            }
        } catch (error: any) {
            console.error('Erro de requisição:', error);
            let erroMsg = 'Falha na comunicação com o servidor ou dados já cadastrados.';

            if (error.response?.data?.errors) {
                // Erros de Validação (422)
                const laravelErrors = error.response.data.errors;
                const fieldErrors: FieldErrors = {};
                
                // Mapeamento de erros do Laravel para campos do React
                Object.entries(laravelErrors).forEach(([key, messages]) => {
                    const msg = Array.isArray(messages) ? messages[0] : 'Erro de validação.';
                    
                    if (key === 'nome') fieldErrors.nome_completo = msg;
                    else if (key in formData) (fieldErrors as any)[key] = msg;
                    else erroMsg = msg; // Mensagem geral (ex: 'O email informado já existe.')
                });
                
                setErrors(fieldErrors);
                setMensagem(Object.keys(fieldErrors).length > 0 ? 'Corrija os campos destacados.' : erroMsg);
            } else if (error.message) {
                erroMsg = error.message;
            }
            
            announce(erroMsg);
            setTimeout(() => alertRef.current?.focus(), 0);
        } finally {
            setSubmitting(false);
        }
    }

    const exigeCurso = formData.escolaridade.includes('Superior') || ['Pós-Graduação', 'Mestrado', 'Doutorado'].includes(formData.escolaridade);

    return (
        // Usa 'auth-container' e 'card-auth' para o layout centralizado, com largura maior (max-w-2xl)
        <main className="auth-container" aria-labelledby="titulo-cadastro-candidato"> 
            
            <div className="card-auth-large">
                
                <h1
                    id="titulo-cadastro-candidato"
                    className="heading-secondary mb-md text-center"
                    tabIndex={-1}
                    ref={h1Ref}
                >
                    Cadastro de Agente de Apoio
                </h1>
                <p className="text-sm text-muted text-center mb-lg">
                    Preencha suas informações pessoais e qualificações. Todos os campos são obrigatórios.
                </p>

                {/* Alerta de Mensagem */}
                {mensagem && (
                    <div
                        ref={alertRef}
                        role="alert"
                        tabIndex={-1}
                        className={`alert ${mensagem.includes('sucesso') ? 'alert-success' : 'alert-error'} mb-md`}
                    >
                        <AlertTriangle size={20} className="inline mr-sm" />
                        {mensagem}
                    </div>
                )}
                <div ref={liveRef} className="sr-only" aria-live="polite" />
                
                <form
                    onSubmit={handleSubmit}
                    className="space-y-lg" // Espaçamento grande entre seções
                    aria-label="Formulário de cadastro de candidato"
                    noValidate
                >
                    {/* SEÇÃO: DADOS PESSOAIS E CONTA */}
                    <fieldset className="fieldset-group">
                        <legend className="title-md fieldset-legend">1. Informações Pessoais e de Acesso</legend>
                        
                        {/* 2 Colunas */}
                        <div className="form-grid-2">
                            <div className="form-group">
                                <label htmlFor="nome_completo" className="form-label">Nome completo</label>
                                <div className="form-input-icon-wrapper">
                                    <User size={20} className="form-icon" />
                                    <input id="nome_completo" name="nome_completo" type="text" required value={formData.nome_completo} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.nome_completo} aria-describedby={errors.nome_completo ? 'erro-nome_completo' : undefined} />
                                </div>
                                <ErrorText id="erro-nome_completo" message={errors.nome_completo} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="email" className="form-label">E-mail</label>
                                <div className="form-input-icon-wrapper">
                                    <Mail size={20} className="form-icon" />
                                    <input id="email" name="email" type="email" required value={formData.email} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.email} aria-describedby={errors.email ? 'erro-email' : undefined} autoComplete="email" />
                                </div>
                                <ErrorText id="erro-email" message={errors.email} />
                            </div>

                            <div className="form-group">
                                <label htmlFor="telefone" className="form-label">Telefone</label>
                                <div className="form-input-icon-wrapper">
                                    <Phone size={20} className="form-icon" />
                                    <input id="telefone" name="telefone" type="tel" required value={formData.telefone} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.telefone} aria-describedby={errors.telefone ? 'erro-telefone' : undefined} />
                                </div>
                                <ErrorText id="erro-telefone" message={errors.telefone} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="cpf" className="form-label">CPF</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="cpf" name="cpf" type="text" required value={formData.cpf} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.cpf} aria-describedby={errors.cpf ? 'erro-cpf' : undefined} inputMode="numeric" />
                                </div>
                                <ErrorText id="erro-cpf" message={errors.cpf} />
                            </div>
                            
                            <div className="form-group">
                                <label htmlFor="data_nascimento" className="form-label">Data de Nascimento</label>
                                <div className="form-input-icon-wrapper">
                                    <Calendar size={20} className="form-icon" />
                                    <input id="data_nascimento" name="data_nascimento" type="date" required value={formData.data_nascimento} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.data_nascimento} aria-describedby={errors.data_nascimento ? 'erro-data_nascimento' : undefined} />
                                </div>
                                <ErrorText id="erro-data_nascimento" message={errors.data_nascimento} />
                            </div>

                        </div>
                        
                        {/* Senha */}
                        <div className="form-grid-2 mt-md">
                            <div className="form-group">
                                <label htmlFor="senha" className="form-label">Senha</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="senha" name="senha" type="password" required value={formData.senha} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.senha} aria-describedby={errors.senha ? 'erro-senha' : 'ajuda-senha'} autoComplete="new-password" />
                                </div>
                                <ErrorText id="erro-senha" message={errors.senha} />
                                <p id="ajuda-senha" className="text-xs text-muted mt-xs">Mínimo 8 caracteres com letras e números.</p>
                            </div>

                            <div className="form-group">
                                <label htmlFor="confirmarSenha" className="form-label">Confirmar senha</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="confirmarSenha" name="confirmarSenha" type="password" required value={formData.confirmarSenha} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.confirmarSenha} aria-describedby={errors.confirmarSenha ? 'erro-confirmar' : undefined} autoComplete="new-password" />
                                </div>
                                <ErrorText id="erro-confirmar" message={errors.confirmarSenha} />
                            </div>
                        </div>
                    </fieldset>

                    {/* SEÇÃO: ENDEREÇO */}
                    <fieldset className="fieldset-group">
                        <legend className="title-md fieldset-legend">2. Endereço</legend>
                        
                        <div className="form-grid-3">
                            <div className="form-group col-span-3">
                                <label htmlFor="cep" className="form-label">CEP</label>
                                <div className="form-input-icon-wrapper">
                                    <MapPin size={20} className="form-icon" />
                                    <input id="cep" name="cep" type="text" inputMode="numeric" maxLength={10} required value={formData.cep} onChange={handleChange} onBlur={handleCepBlur} className="form-input with-icon" aria-invalid={!!errors.cep} aria-describedby={errors.cep ? 'erro-cep' : 'ajuda-cep'} placeholder="00000-000" />
                                </div>
                                <ErrorText id="erro-cep" message={errors.cep} />
                                <p id="ajuda-cep" className="text-xs text-muted mt-xs">Ao sair do campo, cidade e estado serão preenchidos.</p>
                            </div>

                            <div className="form-group">
                                <label htmlFor="cidade" className="form-label">Cidade</label>
                                <div className="form-input-icon-wrapper">
                                    <input id="cidade" name="cidade" type="text" required value={formData.cidade} onChange={handleChange} className="form-input" aria-invalid={!!errors.cidade} aria-describedby={errors.cidade ? 'erro-cidade' : undefined} />
                                </div>
                                <ErrorText id="erro-cidade" message={errors.cidade} />
                            </div>

                            <div className="form-group">
                                <label htmlFor="estado" className="form-label">Estado</label>
                                <div className="form-input-icon-wrapper">
                                    <input id="estado" name="estado" type="text" required value={formData.estado} onChange={handleChange} className="form-input" aria-invalid={!!errors.estado} aria-describedby={errors.estado ? 'erro-estado' : undefined} />
                                </div>
                                <ErrorText id="erro-estado" message={errors.estado} />
                            </div>
                        </div>
                    </fieldset>

                    {/* SEÇÃO: QUALIFICAÇÃO */}
                    <fieldset className="fieldset-group">
                        <legend className="title-md fieldset-legend">3. Qualificação e Experiência</legend>
                        
                        <div className="form-group">
                            <label htmlFor="escolaridade" className="form-label">Nível de Escolaridade</label>
                            <div className="form-input-icon-wrapper">
                                <GraduationCap size={20} className="form-icon" />
                                <select id="escolaridade" name="escolaridade" required value={formData.escolaridade} onChange={handleChange} className="form-select with-icon" aria-invalid={!!errors.escolaridade} aria-describedby={errors.escolaridade ? 'erro-escolaridade' : 'ajuda-escolaridade'}>
                                    <option value="">Selecione</option>
                                    {ESCOLARIDADE_OPCOES.map((op) => (<option key={op} value={op}>{op}</option>))}
                                </select>
                            </div>
                            <ErrorText id="erro-escolaridade" message={errors.escolaridade} />
                            <p id="ajuda-escolaridade" className="text-xs text-muted mt-xs">Se escolher nível superior ou acima, os campos de curso abaixo se tornarão obrigatórios.</p>
                        </div>
                        
                        {/* Campos curso e instituição condicional */}
                        {exigeCurso && (
                            <div className='form-grid-2 mt-md'>
                                <div className="form-group">
                                    <label htmlFor="curso" className="form-label">Nome do Curso</label>
                                    <div className="form-input-icon-wrapper">
                                        <input id="curso" name="curso" type="text" required={exigeCurso} value={formData.curso} onChange={handleChange} className="form-input" aria-invalid={!!errors.curso} aria-describedby={errors.curso ? 'erro-curso' : undefined} />
                                    </div>
                                    <ErrorText id="erro-curso" message={errors.curso} />
                                </div>
                                
                                <div className="form-group">
                                    <label htmlFor="instituicao_ensino" className="form-label">Instituição de Ensino</label>
                                    <div className="form-input-icon-wrapper">
                                        <input id="instituicao_ensino" name="instituicao_ensino" type="text" required={exigeCurso} value={formData.instituicao_ensino} onChange={handleChange} className="form-input" aria-invalid={!!errors.instituicao_ensino} aria-describedby={errors.instituicao_ensino ? 'erro-instituicao_ensino' : undefined} />
                                    </div>
                                    <ErrorText id="erro-instituicao_ensino" message={errors.instituicao_ensino} />
                                </div>
                            </div>
                        )}

                        <div className="form-group mt-md">
                            <label htmlFor="experiencia" className="form-label">Descreva sua experiência</label>
                            <textarea id="experiencia" name="experiencia" rows={4} required value={formData.experiencia} onChange={handleChange} className="form-textarea" aria-invalid={!!errors.experiencia} aria-describedby={errors.experiencia ? 'erro-experiencia' : 'ajuda-experiencia'} />
                            <ErrorText id="erro-experiencia" message={errors.experiencia} />
                            <p id="ajuda-experiencia" className="text-xs text-muted mt-xs">Informe pelo menos uma experiência relevante com alunos com deficiência (mínimo 20 caracteres).</p>
                        </div>
                    </fieldset>

                    {/* BOTÃO DE SUBMISSÃO */}
                    <button
                        type="submit"
                        disabled={submitting}
                        className="btn-primary w-full btn-lg mt-xl"
                        aria-busy={submitting}
                        aria-disabled={submitting}
                    >
                        {submitting ? <Loader2 size={24} className="icon-spin mr-sm" /> : <UserPlus size={24} className="mr-sm" />}
                        {submitting ? 'Criando Conta…' : 'Criar Conta de Agente de Apoio'}
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
}