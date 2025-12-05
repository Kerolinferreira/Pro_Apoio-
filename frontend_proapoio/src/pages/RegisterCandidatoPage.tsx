import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../contexts/AuthContext';
import { User, Mail, Phone, MapPin, Lock, Briefcase, GraduationCap, Calendar, Loader2, AlertTriangle, ArrowLeft, UserPlus, PlusCircle, Trash2 } from 'lucide-react';
import { maskCEP, maskCPF, maskPhone } from '../utils/masks';
import { ESCOLARIDADE_OPTIONS, ESTADOS_BRASILEIROS, TEMPO_EXPERIENCIA_OPTIONS } from '../constants/options';
import { logger } from '../utils/logger';

// ===================================
// TIPOS E DEFINIÇÕES
// ===================================

type ExperienciaProfissional = {
    idade_aluno?: number | '';
    tempo_experiencia?: string;
    candidatar_mesma_deficiencia: boolean;
    comentario: string;
    deficiencia_ids: number[];
};

type FormData = {
    nome_completo: string;
    email: string;
    telefone: string;
    cpf: string;
    data_nascimento: string; // yyyy-mm-dd
    password: string;
    password_confirmation: string;
    cep: string;
    logradouro: string;
    bairro: string;
    cidade: string;
    estado: string;
    escolaridade: string;
    curso_superior: string;
    instituicao_ensino: string; // Nome da instituição
    experiencias_profissionais: ExperienciaProfissional[]; // Array de experiências
    deficiencia_ids: number[]; // IDs das deficiências selecionadas
};

type FieldErrors = Partial<Record<keyof FormData, string>>;

// Constantes agora importadas de src/constants/options.ts
const ESCOLARIDADE_OPCOES = ESCOLARIDADE_OPTIONS;
const ESTADOS_OPCOES = ESTADOS_BRASILEIROS;

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
        data_nascimento: '', // yyyy-mm-dd
        password: '',
        password_confirmation: '',
        cep: '',
        logradouro: '',
        bairro: '',
        cidade: '',
        estado: '',
        escolaridade: '',
        curso_superior: '',
        instituicao_ensino: '', // Nome da instituição
        experiencias_profissionais: [{
            idade_aluno: '',
            tempo_experiencia: '',
            candidatar_mesma_deficiencia: false,
            comentario: '',
            deficiencia_ids: []
        }],
        deficiencia_ids: [],
    });

    const [mensagem, setMensagem] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<FieldErrors>({});
    const [deficiencias, setDeficiencias] = useState<{id: number; nome: string}[]>([]);
    const liveRef = useRef<HTMLDivElement>(null);
    const alertRef = useRef<HTMLDivElement>(null);
    const h1Ref = useRef<HTMLHeadingElement>(null);

    useEffect(() => {
        h1Ref.current?.focus();
        // Busca a lista de deficiências da API
        const fetchDeficiencias = async () => {
            try {
                const response = await api.get('/deficiencias');
                setDeficiencias(response.data || []);
            } catch (error) {
                logger.error('Erro ao carregar deficiências:', error);
            }
        };
        fetchDeficiencias();
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

    function handleDeficienciaToggle(deficienciaId: number) {
        setFormData(prev => {
            const isSelected = prev.deficiencia_ids.includes(deficienciaId);
            return {
                ...prev,
                deficiencia_ids: isSelected
                    ? prev.deficiencia_ids.filter(id => id !== deficienciaId)
                    : [...prev.deficiencia_ids, deficienciaId]
            };
        });
    }

    // Funções para gerenciar experiências profissionais
    function addExperiencia() {
        setFormData(prev => ({
            ...prev,
            experiencias_profissionais: [
                ...prev.experiencias_profissionais,
                {
                    idade_aluno: '',
                    tempo_experiencia: '',
                    candidatar_mesma_deficiencia: false,
                    comentario: '',
                    deficiencia_ids: []
                }
            ]
        }));
    }

    function removeExperiencia(index: number) {
        if (formData.experiencias_profissionais.length === 1) {
            alert('Você deve ter pelo menos uma experiência profissional.');
            return;
        }
        setFormData(prev => ({
            ...prev,
            experiencias_profissionais: prev.experiencias_profissionais.filter((_, i) => i !== index)
        }));
    }

    function updateExperiencia(index: number, field: keyof ExperienciaProfissional, value: any) {
        setFormData(prev => ({
            ...prev,
            experiencias_profissionais: prev.experiencias_profissionais.map((exp, i) =>
                i === index ? { ...exp, [field]: value } : exp
            )
        }));
    }

    function toggleDeficienciaInExperiencia(expIndex: number, deficienciaId: number) {
        const exp = formData.experiencias_profissionais[expIndex];
        if (!exp) return;

        const isSelected = exp.deficiencia_ids.includes(deficienciaId);
        updateExperiencia(expIndex, 'deficiencia_ids',
            isSelected
                ? exp.deficiencia_ids.filter(id => id !== deficienciaId)
                : [...exp.deficiencia_ids, deficienciaId]
        );
    }

    function senhaValida(s: string) {
        // Mínimo 8, ao menos uma letra e um número [cite: Documentação final.docx]
        return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(s);
    }

    function dataValida(dataString: string): boolean {
        // Verifica se a data está no formato correto e é uma data válida
        if (!dataString) return false;

        const data = new Date(dataString + 'T00:00:00'); // Adiciona hora para evitar problemas de timezone

        // Verifica se é uma data válida
        if (isNaN(data.getTime())) return false;

        // Verifica se a data fornecida corresponde aos componentes da data criada
        // Isso evita casos como 31 de fevereiro que o JavaScript converte para 3 de março
        const [ano, mes, dia] = dataString.split('-').map(Number);
        return (
            mes !== undefined &&
            data.getFullYear() === ano &&
            data.getMonth() === mes - 1 && // Mês em JS é 0-indexed
            data.getDate() === dia
        );
    }

    function isMaiorDeIdade(dataNascimento: string): boolean {
        if (!dataNascimento) return false;

        const hoje = new Date();
        const nascimento = new Date(dataNascimento + 'T00:00:00');

        // Calcula a idade
        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const mesAtual = hoje.getMonth();
        const mesNascimento = nascimento.getMonth();

        // Ajusta se ainda não fez aniversário neste ano
        if (mesAtual < mesNascimento || (mesAtual === mesNascimento && hoje.getDate() < nascimento.getDate())) {
            idade--;
        }

        return idade >= 18;
    }

    function validar(): FieldErrors {
        const e: FieldErrors = {};
        
        if (formData.nome_completo.trim().length < 3) e.nome_completo = 'Nome completo é obrigatório (mínimo 3 caracteres).';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) e.email = 'E-mail inválido.';
        
        // Validação de campos obrigatórios com máscara
        if (formData.telefone.replace(/\D/g, '').length < 10) e.telefone = 'Telefone inválido.';
        if (formData.cpf.replace(/\D/g, '').length !== 11) e.cpf = 'CPF deve ter 11 dígitos.';

        // Validação de data de nascimento
        if (!formData.data_nascimento.trim()) {
            e.data_nascimento = 'Informe a data de nascimento.';
        } else if (!dataValida(formData.data_nascimento)) {
            e.data_nascimento = 'Data de nascimento inválida.';
        } else if (!isMaiorDeIdade(formData.data_nascimento)) {
            e.data_nascimento = 'Você deve ter pelo menos 18 anos para se cadastrar.';
        }
        
        // Senha
        if (!senhaValida(formData.password)) e.password = 'A senha deve ter no mínimo 8 caracteres com letras e números.';
        if (formData.password !== formData.password_confirmation) e.password_confirmation = 'As senhas não coincidem.';
        
        // Endereço
        if (formData.cep.replace(/\D/g, '').length !== 8) e.cep = 'CEP inválido.';
        if (!formData.cidade.trim()) e.cidade = 'Informe a cidade.';
        if (!formData.estado.trim()) e.estado = 'Informe o estado.';
        
        // Escolaridade Condicional
        if (!formData.escolaridade) e.escolaridade = 'Selecione a escolaridade.';
        const exigeCurso = formData.escolaridade.includes('Superior') || ['Pós-Graduação', 'Mestrado', 'Doutorado'].includes(formData.escolaridade);
        if (exigeCurso) {
            if (!formData.curso_superior.trim()) e.curso_superior = 'Informe o curso.';
            if (!formData.instituicao_ensino.trim()) e.instituicao_ensino = 'Informe a instituição de ensino.';
        }
        
        // Experiências profissionais
        if (!formData.experiencias_profissionais || formData.experiencias_profissionais.length === 0) {
            (e as any).experiencias_profissionais = 'Adicione ao menos uma experiência profissional.';
        } else {
            formData.experiencias_profissionais.forEach((exp, index) => {
                if (!exp.comentario || exp.comentario.trim().length < 20) {
                    (e as any)[`experiencia_${index}_comentario`] = `Descreva a experiência ${index + 1} com mais detalhes (mínimo 20 caracteres).`;
                }
                if (!exp.deficiencia_ids || exp.deficiencia_ids.length === 0) {
                    (e as any)[`experiencia_${index}_deficiencias`] = `Selecione ao menos uma deficiência para a experiência ${index + 1}.`;
                }
            });
        }

        return e;
    }

    async function handleCepBlur() {
        const digits = formData.cep.replace(/\D/g, '');
        if (digits.length !== 8) return;

        try {
            // Usa o proxy do backend para buscar o CEP
            const response = await api.get(`/external/viacep/${digits}`);
            const data = response.data;

            if (data?.erro) {
                setErrors((prev) => ({ ...prev, cep: 'CEP não encontrado.' }));
                announce('CEP não encontrado.');
                return;
            }
            // Preenche os campos (logradouro, bairro, cidade, estado)
            setFormData((prev) => ({
                ...prev,
                logradouro: data.logradouro || prev.logradouro,
                bairro: data.bairro || prev.bairro,
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
            const errorFields = Object.keys(eMap).map(key => {
                const labels: Record<string, string> = {
                    nome_completo: 'Nome completo',
                    password: 'Senha',
                    password_confirmation: 'Confirmar senha',
                    curso_superior: 'Nome do Curso',
                };
                return labels[key] || key.charAt(0).toUpperCase() + key.slice(1);
            });

            const errorMessage = `Por favor, corrija os seguintes campos: ${errorFields.join(', ')}.`;
            setMensagem(errorMessage);
            announce(errorMessage);
            setTimeout(() => alertRef.current?.focus(), 100);
            return;
        }

        setSubmitting(true);
        try {
            // Mapeamento: Chaves do front (ex: nome_completo) para chaves do back (ex: nome)
            const payload = {
                nome: formData.nome_completo,
                email: formData.email,
                telefone: formData.telefone, // Envia COM máscara para validação pt-br-validator
                cpf: formData.cpf.replace(/\D/g, ''), // Envia sem máscara
                data_nascimento: formData.data_nascimento,
                password: formData.password,
                password_confirmation: formData.password_confirmation,

                cep: formData.cep.replace(/\D/g, ''), // Envia sem máscara
                logradouro: formData.logradouro,
                bairro: formData.bairro,
                cidade: formData.cidade,
                estado: formData.estado,

                nivel_escolaridade: formData.escolaridade,
                curso_superior: formData.curso_superior,
                instituicao_ensino: formData.instituicao_ensino,
                experiencias_profissionais: formData.experiencias_profissionais.map(exp => ({
                    idade_aluno: exp.idade_aluno === '' ? null : Number(exp.idade_aluno),
                    tempo_experiencia: exp.tempo_experiencia || null,
                    candidatar_mesma_deficiencia: exp.candidatar_mesma_deficiencia,
                    comentario: exp.comentario.trim(),
                    deficiencia_ids: exp.deficiencia_ids
                })),
                deficiencia_ids: formData.deficiencia_ids,
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
            logger.error('Erro de requisição:', error);
            let erroMsg = 'Falha na comunicação com o servidor. Tente novamente mais tarde.';

            // Erro de validação do Laravel (422)
            if (error.response?.status === 422 && error.response?.data?.errors) {
                // Erros de Validação (422)
                const laravelErrors = error.response.data.errors;
                const fieldErrors: FieldErrors = {};
                
                // Mapeamento de erros do Laravel para campos do React
                Object.entries(laravelErrors).forEach(([key, messages]) => {
                    const msg = Array.isArray(messages) ? messages[0] : 'Erro de validação.';
                    
                    const keyMap: Record<string, keyof FormData> = {
                        'nome': 'nome_completo',
                        'nivel_escolaridade': 'escolaridade',
                    };

                    const formKey = keyMap[key] || key;

                    if (formKey in formData) (fieldErrors as any)[formKey] = msg;
                    else erroMsg = msg; // Mensagem geral (ex: 'O email informado já existe.') se não mapear
                });
                
                setErrors(fieldErrors);
                // Se houver erros de campo, listar os nomes dos campos especificamente
                if (Object.keys(fieldErrors).length > 0) {
                    const labels: Record<string, string> = {
                        nome_completo: 'Nome completo',
                        password: 'Senha',
                        password_confirmation: 'Confirmar senha',
                        curso_superior: 'Nome do Curso',
                        email: 'E-mail',
                        cpf: 'CPF',
                        telefone: 'Telefone',
                        escolaridade: 'Nível de Escolaridade',
                        cep: 'CEP',
                        cidade: 'Cidade',
                        estado: 'Estado',
                    };
                    const camposComErro = Object.keys(fieldErrors)
                        .map(key => labels[key] || key.charAt(0).toUpperCase() + key.slice(1));
                    erroMsg = `Por favor, corrija os seguintes campos: ${camposComErro.join(', ')}.`;
                }
                setMensagem(erroMsg);
            } else if (error.response?.data?.message) {
                // Outros erros da API com uma mensagem específica (ex: 409 Conflict)
                erroMsg = error.response.data.message;
                setMensagem(erroMsg);
            }
            announce(erroMsg);
            setTimeout(() => alertRef.current?.focus(), 0);
        } finally {
            setSubmitting(false);
        }
    }

    const exigeCurso = formData.escolaridade.includes('Superior') || ['Pós-Graduação', 'Mestrado', 'Doutorado'].includes(formData.escolaridade);

    // Calcula data máxima (18 anos atrás) e data mínima (100 anos atrás) para o campo de data de nascimento
    const hoje = new Date();
    const dataMaxima = new Date(hoje.getFullYear() - 18, hoje.getMonth(), hoje.getDate());
    const dataMinima = new Date(hoje.getFullYear() - 100, hoje.getMonth(), hoje.getDate());
    const maxDate = dataMaxima.toISOString().split('T')[0]; // Formato yyyy-mm-dd
    const minDate = dataMinima.toISOString().split('T')[0]; // Formato yyyy-mm-dd

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
                                    <input id="data_nascimento" name="data_nascimento" type="date" required min={minDate} max={maxDate} value={formData.data_nascimento} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.data_nascimento} aria-describedby={errors.data_nascimento ? 'erro-data_nascimento' : 'ajuda-data_nascimento'} />
                                </div>
                                <ErrorText id="erro-data_nascimento" message={errors.data_nascimento} />
                                <p id="ajuda-data_nascimento" className="text-xs text-muted mt-xs">Você deve ter pelo menos 18 anos para se cadastrar.</p>
                            </div>

                        </div>
                        
                        {/* Senha */}
                        <div className="form-grid-2 mt-md">
                            <div className="form-group">
                                <label htmlFor="password" className="form-label">Senha</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="password" name="password" type="password" required value={formData.password} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.password} aria-describedby={errors.password ? 'erro-password' : 'ajuda-senha'} autoComplete="new-password" />
                                </div>
                                <ErrorText id="erro-password" message={errors.password} />
                                <p id="ajuda-senha" className="text-xs text-muted mt-xs">Mínimo 8 caracteres com letras e números.</p>
                            </div>

                            <div className="form-group">
                                <label htmlFor="password_confirmation" className="form-label">Confirmar senha</label>
                                <div className="form-input-icon-wrapper">
                                    <Lock size={20} className="form-icon" />
                                    <input id="password_confirmation" name="password_confirmation" type="password" required value={formData.password_confirmation} onChange={handleChange} className="form-input with-icon" aria-invalid={!!errors.password_confirmation} aria-describedby={errors.password_confirmation ? 'erro-confirmar' : undefined} autoComplete="new-password" />
                                </div>
                                <ErrorText id="erro-confirmar" message={errors.password_confirmation} />
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
                                <p id="ajuda-cep" className="text-xs text-muted mt-xs">Ao sair do campo, os dados de endereço serão preenchidos automaticamente.</p>
                            </div>

                            <div className="form-group col-span-2">
                                <label htmlFor="logradouro" className="form-label">Logradouro</label>
                                <div className="form-input-icon-wrapper">
                                    <input id="logradouro" name="logradouro" type="text" value={formData.logradouro} onChange={handleChange} className="form-input" aria-invalid={!!errors.logradouro} aria-describedby={errors.logradouro ? 'erro-logradouro' : undefined} />
                                </div>
                                <ErrorText id="erro-logradouro" message={errors.logradouro} />
                            </div>

                            <div className="form-group">
                                <label htmlFor="bairro" className="form-label">Bairro</label>
                                <div className="form-input-icon-wrapper">
                                    <input id="bairro" name="bairro" type="text" value={formData.bairro} onChange={handleChange} className="form-input" aria-invalid={!!errors.bairro} aria-describedby={errors.bairro ? 'erro-bairro' : undefined} />
                                </div>
                                <ErrorText id="erro-bairro" message={errors.bairro} />
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
                                    <label htmlFor="curso_superior" className="form-label">Nome do Curso</label>
                                    <div className="form-input-icon-wrapper">
                                        <input id="curso_superior" name="curso_superior" type="text" required={exigeCurso} value={formData.curso_superior} onChange={handleChange} className="form-input" aria-invalid={!!errors.curso_superior} aria-describedby={errors.curso_superior ? 'erro-curso' : undefined} />
                                    </div>
                                    <ErrorText id="erro-curso" message={errors.curso_superior} />
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

                        {/* Experiências Profissionais */}
                        <div className="mt-md">
                            <div className="flex justify-between items-center mb-md">
                                <div>
                                    <label className="form-label">Experiências Profissionais</label>
                                    <p className="text-xs text-muted">Adicione suas experiências com apoio a pessoas com deficiência</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={addExperiencia}
                                    className="btn-secondary btn-sm btn-icon"
                                    disabled={submitting}
                                >
                                    <PlusCircle size={16} className="mr-xs" />
                                    Adicionar Experiência
                                </button>
                            </div>

                            {formData.experiencias_profissionais.map((exp, index) => (
                                <div key={index} className="card-bordered p-md mb-md">
                                    <div className="flex justify-between items-start mb-md">
                                        <h4 className="font-medium">Experiência {index + 1}</h4>
                                        {formData.experiencias_profissionais.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() => removeExperiencia(index)}
                                                className="btn-ghost btn-icon text-red-600"
                                                aria-label={`Remover experiência ${index + 1}`}
                                                disabled={submitting}
                                            >
                                                <Trash2 size={16} />
                                            </button>
                                        )}
                                    </div>

                                    <div className="space-y-md">
                                        {/* Idade do aluno */}
                                        <div className="form-group">
                                            <label htmlFor={`exp_${index}_idade`} className="form-label">
                                                Idade do(a) aluno(a) atendido(a) (opcional)
                                            </label>
                                            <input
                                                id={`exp_${index}_idade`}
                                                type="number"
                                                min="0"
                                                max="120"
                                                value={exp.idade_aluno}
                                                onChange={(e) => updateExperiencia(index, 'idade_aluno', e.target.value === '' ? '' : parseInt(e.target.value))}
                                                className="form-input"
                                                placeholder="Ex: 10"
                                                disabled={submitting}
                                            />
                                        </div>

                                        {/* Tempo de experiência */}
                                        <div className="form-group">
                                            <label htmlFor={`exp_${index}_tempo`} className="form-label">
                                                Tempo de experiência (opcional)
                                            </label>
                                            <select
                                                id={`exp_${index}_tempo`}
                                                value={exp.tempo_experiencia}
                                                onChange={(e) => updateExperiencia(index, 'tempo_experiencia', e.target.value)}
                                                className="form-select"
                                                disabled={submitting}
                                            >
                                                <option value="">Selecione...</option>
                                                {TEMPO_EXPERIENCIA_OPTIONS.map((option) => (
                                                    <option key={option} value={option}>
                                                        {option}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {/* Deficiências trabalhadas */}
                                        <div className="form-group">
                                            <label className="form-label">
                                                Deficiências com as quais trabalhou *
                                            </label>
                                            <div className="space-y-xs">
                                                {deficiencias.map((def) => (
                                                    <label key={def.id} className="flex items-center gap-xs cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            checked={exp.deficiencia_ids.includes(def.id)}
                                                            onChange={() => toggleDeficienciaInExperiencia(index, def.id)}
                                                            className="form-checkbox"
                                                            disabled={submitting}
                                                        />
                                                        <span className="text-sm">{def.nome}</span>
                                                    </label>
                                                ))}
                                            </div>
                                            <ErrorText
                                                id={`erro-exp-${index}-deficiencias`}
                                                message={(errors as any)[`experiencia_${index}_deficiencias`]}
                                            />
                                        </div>

                                        {/* Interesse em trabalhar com mesma deficiência */}
                                        <div className="form-group">
                                            <label className="flex items-center gap-xs cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={exp.candidatar_mesma_deficiencia}
                                                    onChange={(e) => updateExperiencia(index, 'candidatar_mesma_deficiencia', e.target.checked)}
                                                    className="form-checkbox"
                                                    disabled={submitting}
                                                />
                                                <span className="text-sm">Tenho interesse em trabalhar com as mesmas deficiências novamente</span>
                                            </label>
                                        </div>

                                        {/* Descrição */}
                                        <div className="form-group">
                                            <label htmlFor={`exp_${index}_comentario`} className="form-label">
                                                Descrição da experiência *
                                            </label>
                                            <textarea
                                                id={`exp_${index}_comentario`}
                                                value={exp.comentario}
                                                onChange={(e) => updateExperiencia(index, 'comentario', e.target.value)}
                                                placeholder="Descreva sua experiência profissional com apoio a pessoas com deficiência..."
                                                rows={4}
                                                maxLength={1000}
                                                className="form-textarea"
                                                disabled={submitting}
                                                aria-invalid={!!(errors as any)[`experiencia_${index}_comentario`]}
                                                aria-describedby={`exp-${index}-help`}
                                            />
                                            <ErrorText
                                                id={`erro-exp-${index}-comentario`}
                                                message={(errors as any)[`experiencia_${index}_comentario`]}
                                            />
                                            <small className="form-text" id={`exp-${index}-help`}>
                                                {exp.comentario.length}/1000 caracteres (mínimo 20)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Seleção de deficiências com experiência (global - opcional) */}
                        {deficiencias.length > 0 && (
                            <div className="form-group mt-md">
                                <label className="form-label">Deficiências com experiência geral (opcional)</label>
                                <p className="text-xs text-muted mb-sm">Selecione outras deficiências com as quais você possui experiência:</p>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-sm">
                                    {deficiencias.map((def) => (
                                        <label key={def.id} className="flex items-center gap-xs cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={formData.deficiencia_ids.includes(def.id)}
                                                onChange={() => handleDeficienciaToggle(def.id)}
                                                className="form-checkbox"
                                                disabled={submitting}
                                            />
                                            <span className="text-sm">{def.nome}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        )}
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