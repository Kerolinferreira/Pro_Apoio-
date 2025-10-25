import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../contexts/AuthContext';
// Componentes de UI simulados, use os seus reais
const Input = ({ label, name, type = 'text', value, onChange, required = false, error }) => (
    <div className="mb-4">
        <label htmlFor={name} className="block text-sm font-medium text-gray-700">{label}{required && <span className="text-red-500"> *</span>}</label>
        <input
            id={name}
            name={name}
            type={type}
            value={value}
            onChange={onChange}
            required={required}
            className={`mt-1 block w-full rounded-md border p-2 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${error ? 'border-red-500' : 'border-gray-300'}`}
        />
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);

const Select = ({ label, name, value, onChange, required = false, options, error }) => (
    <div className="mb-4">
        <label htmlFor={name} className="block text-sm font-medium text-gray-700">{label}{required && <span className="text-red-500"> *</span>}</label>
        <select
            id={name}
            name={name}
            value={value}
            onChange={onChange}
            required={required}
            className={`mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm ${error ? 'border-red-500' : 'border-gray-300'}`}
        >
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);

const CheckboxGroup = ({ label, name, options, values, onChange, required = false, error }) => (
    <div className="mb-4">
        <label className="block text-sm font-medium text-gray-700">{label}{required && <span className="text-red-500"> *</span>}</label>
        <div className="mt-2 space-y-2">
            {options.map((option) => (
                <div key={option.value} className="flex items-center">
                    <input
                        id={`${name}-${option.value}`}
                        name={name}
                        type="checkbox"
                        value={option.value}
                        checked={values.includes(option.value)}
                        onChange={onChange}
                        className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label htmlFor={`${name}-${option.value}`} className="ml-3 text-sm text-gray-700">
                        {option.label}
                    </label>
                </div>
            ))}
        </div>
        {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
);


const Button = ({ children, onClick, disabled = false, variant = 'primary', type = 'button' }) => (
    <button
        type={type}
        onClick={onClick}
        disabled={disabled}
        className={`w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white ${
            disabled ? 'bg-gray-400 cursor-not-allowed' : variant === 'primary' ? 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500' : 'bg-gray-600 hover:bg-gray-700'
        }`}
    >
        {children}
    </button>
);


const escolaridadeOptions = [
    { value: '', label: 'Selecione a Escolaridade' },
    { value: 'Fundamental Completo', label: 'Fundamental Completo' },
    { value: 'Médio Completo', label: 'Médio Completo' },
    { value: 'Superior Incompleto', label: 'Superior Incompleto' },
    { value: 'Superior Completo', label: 'Superior Completo' },
    // Adicionar mais níveis conforme a documentação
];

const tipoInstituicaoOptions = [
    { value: '', label: 'Selecione o Tipo de Instituição' },
    { value: 'Pública Municipal', label: 'Pública Municipal' },
    { value: 'Pública Estadual', label: 'Pública Estadual' },
    { value: 'Pública Federal', label: 'Pública Federal' },
    { value: 'Privada', label: 'Privada' },
    { value: 'Filantrópica', label: 'Filantrópica' },
];

const niveisOferecidosOptions = [
    { value: 'Ed. Infantil', label: 'Ed. Infantil' },
    { value: 'Fundamental I', label: 'Fundamental I' },
    { value: 'Fundamental II', label: 'Fundamental II' },
    { value: 'Ensino Médio', label: 'Ensino Médio' },
    { value: 'Técnica', label: 'Técnica' },
    { value: 'Superior', label: 'Superior' },
    { value: 'EJA', label: 'EJA' },
];


const initialCandidatoState = {
    nome_completo: '',
    email: '',
    telefone: '',
    cpf: '',
    data_nascimento: '',
    senha: '',
    confirmar_senha: '',
    escolaridade: '',
    curso: '', // Condicional
    instituicao_ensino: '', // Condicional
    cep: '',
    logradouro: '',
    numero: '',
    bairro: '',
    cidade: '',
    estado: '',
    // ... outros campos necessários
};

// CORREÇÃO: Inclusão de todos os campos obrigatórios da Instituição (Documentação Módulo 2)
const initialInstituicaoState = {
    nome_fantasia: '',
    razao_social: '',
    cnpj: '',
    email: '', // Email de login
    senha: '',
    confirmar_senha: '',
    // Campos organizacionais
    codigo_inep: '', // Doc: Opcional, BE: nullable
    tipo_instituicao: '', // Doc: Obrigatório, BE: nullable (validaremos como obrigatório para aderência à doc)
    niveis_oferecidos: [] as string[], // Doc: Obrigatório, BE: nullable|json (validaremos como obrigatório para aderência à doc)
    // Campos de contato
    email_corporativo: '', // Doc: Obrigatório, BE: nullable (validaremos como obrigatório para aderência à doc)
    celular_corporativo: '', // Doc: Obrigatório, BE: nullable (validaremos como obrigatório para aderência à doc)
    telefone_fixo: '', // Doc: Não obrigatório, BE: nullable
    // Campos do responsável
    nome_responsavel: '', // Doc: Obrigatório, BE: nullable (validaremos como obrigatório para aderência à doc)
    funcao_responsavel: '', // Doc: Obrigatório, BE: nullable (validaremos como obrigatório para aderência à doc)
    // Endereço (Desvio 4 corrigido no original, mantido e ajustado para obrigatoriedade da doc)
    cep: '', // Doc: Obrigatório
    logradouro: '',
    numero: '', // Doc: Obrigatório
    bairro: '',
    cidade: '',
    estado: '',
    complemento: '',
    ponto_referencia: '',
};

const RegisterPage: React.FC = () => {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [userType, setUserType] = useState<'candidato' | 'instituicao' | null>(null);
    const [candidatoForm, setCandidatoForm] = useState(initialCandidatoState);
    const [instituicaoForm, setInstituicaoForm] = useState(initialInstituicaoState);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string | undefined>>({});

    const handleCandidatoChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setCandidatoForm(prev => ({ ...prev, [name]: value }));
        // Remover erro ao começar a digitar
        setErrors(prev => ({ ...prev, [name]: undefined }));

        // Lógica para auto-preenchimento de CEP, se implementada
        // if (name === 'cep' && value.length === 9) { fetchAddress(value, setCandidatoForm); }
    };

    const handleInstituicaoChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value, type } = e.target;

        if (name === 'niveis_oferecidos' && type === 'checkbox') {
            const checked = (e.target as HTMLInputElement).checked;
            setInstituicaoForm(prev => {
                const currentNiveis = prev.niveis_oferecidos as string[];
                const newNiveis = checked
                    ? [...currentNiveis, value]
                    : currentNiveis.filter(n => n !== value);
                return { ...prev, [name]: newNiveis };
            });
        } else {
            setInstituicaoForm(prev => ({ ...prev, [name]: value }));
        }

        setErrors(prev => ({ ...prev, [name]: undefined }));

        // Lógica para auto-preenchimento de CEP, se implementada
        // if (name === 'cep' && value.length === 9) { fetchAddress(value, setInstituicaoForm); }
    };


    const validateCandidato = () => {
        const newErrors: Record<string, string> = {};
        if (candidatoForm.senha !== candidatoForm.confirmar_senha) {
            newErrors.confirmar_senha = 'As senhas não coincidem.';
        }
        // ... outras validações obrigatórias (email, cpf, etc.)

        if (candidatoForm.escolaridade && isSuperiorLevel(candidatoForm.escolaridade)) {
            if (!candidatoForm.curso) { newErrors.curso = 'O campo Curso é obrigatório para este nível de escolaridade.'; }
            if (!candidatoForm.instituicao_ensino) { newErrors.instituicao_ensino = 'O campo Instituição é obrigatório para este nível de escolaridade.'; }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const validateInstituicao = () => {
        const newErrors: Record<string, string> = {};
        if (instituicaoForm.senha !== instituicaoForm.confirmar_senha) {
            newErrors.confirmar_senha = 'As senhas não coincidem.';
        }
        
        // Validações obrigatórias conforme a documentação 
        if (!instituicaoForm.tipo_instituicao) { newErrors.tipo_instituicao = 'O Tipo de Instituição é obrigatório.'; }
        if (instituicaoForm.niveis_oferecidos.length === 0) { newErrors.niveis_oferecidos = 'Selecione ao menos um Nível Oferecido.'; }
        if (!instituicaoForm.nome_responsavel) { newErrors.nome_responsavel = 'O Nome do Responsável é obrigatório.'; }
        if (!instituicaoForm.funcao_responsavel) { newErrors.funcao_responsavel = 'A Função do Responsável é obrigatória.'; }
        if (!instituicaoForm.email_corporativo) { newErrors.email_corporativo = 'O Email Corporativo é obrigatório.'; }
        if (!instituicaoForm.celular_corporativo) { newErrors.celular_corporativo = 'O Celular Corporativo é obrigatório.'; }
        if (!instituicaoForm.cep) { newErrors.cep = 'O CEP é obrigatório.'; }
        if (!instituicaoForm.numero) { newErrors.numero = 'O Número do endereço é obrigatório.'; }
        // ... outras validações obrigatórias (cnpj, email de login, razao_social, nome_fantasia)

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    // Lógica Condicional para Escolaridade Superior
    const isSuperiorLevel = (escolaridade: string): boolean => {
        const upperLevels = ['Superior Incompleto', 'Superior Completo'];
        return upperLevels.includes(escolaridade);
    };


    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        try {
            if (userType === 'candidato') {
                if (!validateCandidato()) {
                    setLoading(false);
                    return;
                }
                const payload = {
                    ...candidatoForm,
                    // Mapeamento e sanitização de dados para o payload da API
                    nome: candidatoForm.nome_completo, // O backend espera 'nome' para User
                    // O backend espera 'escolaridade' como 'nivel_escolaridade' ou 'escolaridade'
                    // e 'curso'/'instituicao_ensino' como 'curso_superior'/'instituicao_ensino'
                    nivel_escolaridade: candidatoForm.escolaridade,
                    curso_superior: candidatoForm.curso,
                    instituicao_ensino: candidatoForm.instituicao_ensino,
                };
                await api.post('/auth/register/candidato', payload);
                // Lógica de sucesso - Redirecionar para login ou autenticar
                navigate('/login?success=candidato');

            } else if (userType === 'instituicao') {
                if (!validateInstituicao()) {
                    setLoading(false);
                    return;
                }
                const payload = {
                    ...instituicaoForm,
                    // CORREÇÃO: O backend espera o campo 'nome' para a tabela User
                    nome: instituicaoForm.nome_fantasia, 
                    // Os nomes de campo já estão alinhados com o backend request (e.g., celular_corporativo, telefone_fixo)
                };
                await api.post('/auth/register/instituicao', payload);
                // Lógica de sucesso - Redirecionar para login ou autenticar
                navigate('/login?success=instituicao');
            }
        } catch (error) {
            console.error('Erro de registro:', error);
            setErrors({ api: 'Ocorreu um erro ao processar seu cadastro. Verifique os dados e tente novamente.' });
        } finally {
            setLoading(false);
        }
    };

    const renderCandidatoForm = () => {
        const showEducationalDetails = isSuperiorLevel(candidatoForm.escolaridade);

        return (
            <form onSubmit={handleSubmit} className="space-y-4">
                <Input label="Nome Completo" name="nome_completo" value={candidatoForm.nome_completo} onChange={handleCandidatoChange} required error={errors.nome_completo} />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="Email de Login" name="email" type="email" value={candidatoForm.email} onChange={handleCandidatoChange} required error={errors.email} />
                    <Input label="Telefone" name="telefone" value={candidatoForm.telefone} onChange={handleCandidatoChange} required error={errors.telefone} />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="CPF" name="cpf" value={candidatoForm.cpf} onChange={handleCandidatoChange} required error={errors.cpf} />
                    <Input label="Data de Nascimento" name="data_nascimento" type="date" value={candidatoForm.data_nascimento} onChange={handleCandidatoChange} required error={errors.data_nascimento} />
                </div>

                <Select
                    label="Escolaridade"
                    name="escolaridade"
                    value={candidatoForm.escolaridade}
                    onChange={handleCandidatoChange}
                    required
                    options={escolaridadeOptions}
                    error={errors.escolaridade}
                />
                
                {/* INÍCIO DA CORREÇÃO - Renderização Condicional */}
                {showEducationalDetails && (
                    <>
                        <Input 
                            label="Nome do Curso" 
                            name="curso" 
                            value={candidatoForm.curso} 
                            onChange={handleCandidatoChange} 
                            required={showEducationalDetails} 
                            error={errors.curso} 
                        />
                        <Input 
                            label="Instituição de Ensino" 
                            name="instituicao_ensino" 
                            value={candidatoForm.instituicao_ensino} 
                            onChange={handleCandidatoChange} 
                            required={showEducationalDetails} 
                            error={errors.instituicao_ensino} 
                        />
                    </>
                )}
                {/* FIM DA CORREÇÃO */}

                <h3 className="text-lg font-semibold mt-6 mb-2">Endereço</h3>
                {/* Campos de endereço (CEP, logradouro, etc.) viriam aqui */}
                <div className="grid grid-cols-2 gap-4">
                    <Input label="CEP" name="cep" value={candidatoForm.cep} onChange={handleCandidatoChange} required error={errors.cep} />
                    <Input label="Número" name="numero" value={candidatoForm.numero} onChange={handleCandidatoChange} required error={errors.numero} />
                </div>
                <Input label="Logradouro" name="logradouro" value={candidatoForm.logradouro} onChange={handleCandidatoChange} required error={errors.logradouro} />
                <div className="grid grid-cols-3 gap-4">
                    <Input label="Bairro" name="bairro" value={candidatoForm.bairro} onChange={handleCandidatoChange} required error={errors.bairro} />
                    <Input label="Cidade" name="cidade" value={candidatoForm.cidade} onChange={handleCandidatoChange} required error={errors.cidade} />
                    <Input label="Estado" name="estado" value={candidatoForm.estado} onChange={handleCandidatoChange} required error={errors.estado} />
                </div>
                

                <h3 className="text-lg font-semibold mt-6 mb-2">Segurança</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="Senha" name="senha" type="password" value={candidatoForm.senha} onChange={handleCandidatoChange} required error={errors.senha} />
                    <Input label="Confirmar Senha" name="confirmar_senha" type="password" value={candidatoForm.confirmar_senha} onChange={handleCandidatoChange} required error={errors.confirmar_senha} />
                </div>

                {errors.api && <p className="text-red-600 text-center">{errors.api}</p>}
                
                <Button type="submit" disabled={loading}>
                    {loading ? 'Cadastrando...' : 'Cadastrar Candidato'}
                </Button>
            </form>
        );
    };

    const renderInstituicaoForm = () => {
        // CORREÇÃO: Renderização de todos os campos da Instituição conforme documentação 
        return (
             <form onSubmit={handleSubmit} className="space-y-4">
                <h3 className="text-lg font-semibold mb-2">Dados Básicos e Legais</h3>
                <Input label="Nome Fantasia" name="nome_fantasia" value={instituicaoForm.nome_fantasia} onChange={handleInstituicaoChange} required error={errors.nome_fantasia} />
                <Input label="Razão Social" name="razao_social" value={instituicaoForm.razao_social} onChange={handleInstituicaoChange} required error={errors.razao_social} />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="CNPJ" name="cnpj" value={instituicaoForm.cnpj} onChange={handleInstituicaoChange} required error={errors.cnpj} />
                    <Input label="Código INEP/MEC (Opcional)" name="codigo_inep" value={instituicaoForm.codigo_inep} onChange={handleInstituicaoChange} error={errors.codigo_inep} />
                </div>
                
                <Select
                    label="Tipo de Instituição"
                    name="tipo_instituicao"
                    value={instituicaoForm.tipo_instituicao}
                    onChange={handleInstituicaoChange}
                    required
                    options={tipoInstituicaoOptions}
                    error={errors.tipo_instituicao}
                />

                <CheckboxGroup
                    label="Níveis Oferecidos"
                    name="niveis_oferecidos"
                    options={niveisOferecidosOptions}
                    values={instituicaoForm.niveis_oferecidos}
                    onChange={handleInstituicaoChange}
                    required
                    error={errors.niveis_oferecidos}
                />

                <h3 className="text-lg font-semibold mt-6 mb-2">Contatos</h3>
                <Input label="Email de Login" name="email" type="email" value={instituicaoForm.email} onChange={handleInstituicaoChange} required error={errors.email} />
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Input label="Email Corporativo" name="email_corporativo" type="email" value={instituicaoForm.email_corporativo} onChange={handleInstituicaoChange} required error={errors.email_corporativo} />
                    <Input label="Celular Corporativo" name="celular_corporativo" value={instituicaoForm.celular_corporativo} onChange={handleInstituicaoChange} required error={errors.celular_corporativo} />
                    <Input label="Telefone Fixo (Opcional)" name="telefone_fixo" value={instituicaoForm.telefone_fixo} onChange={handleInstituicaoChange} error={errors.telefone_fixo} />
                </div>

                <h3 className="text-lg font-semibold mt-6 mb-2">Responsável</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="Nome Completo do Responsável" name="nome_responsavel" value={instituicaoForm.nome_responsavel} onChange={handleInstituicaoChange} required error={errors.nome_responsavel} />
                    <Input label="Função do Responsável" name="funcao_responsavel" value={instituicaoForm.funcao_responsavel} onChange={handleInstituicaoChange} required error={errors.funcao_responsavel} />
                </div>
                

                <h3 className="text-lg font-semibold mt-6 mb-2">Endereço</h3>
                {/* DESVIO 4 - CORREÇÃO: Campos de endereço para Instituição que estavam faltando */}
                <div className="grid grid-cols-2 gap-4">
                    <Input label="CEP" name="cep" value={instituicaoForm.cep} onChange={handleInstituicaoChange} required error={errors.cep} />
                    <Input label="Número" name="numero" value={instituicaoForm.numero} onChange={handleInstituicaoChange} required error={errors.numero} />
                </div>
                <Input label="Logradouro" name="logradouro" value={instituicaoForm.logradouro} onChange={handleInstituicaoChange} error={errors.logradouro} />
                <div className="grid grid-cols-3 gap-4">
                    <Input label="Bairro" name="bairro" value={instituicaoForm.bairro} onChange={handleInstituicaoChange} error={errors.bairro} />
                    <Input label="Cidade" name="cidade" value={instituicaoForm.cidade} onChange={handleInstituicaoChange} error={errors.cidade} />
                    <Input label="Estado" name="estado" value={instituicaoForm.estado} onChange={handleInstituicaoChange} error={errors.estado} />
                </div>
                <Input label="Complemento (Opcional)" name="complemento" value={instituicaoForm.complemento} onChange={handleInstituicaoChange} error={errors.complemento} />
                <Input label="Ponto de Referência (Opcional)" name="ponto_referencia" value={instituicaoForm.ponto_referencia} onChange={handleInstituicaoChange} error={errors.ponto_referencia} />
                {/* FIM CORREÇÃO DESVIO 4 */}


                <h3 className="text-lg font-semibold mt-6 mb-2">Segurança</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="Senha" name="senha" type="password" value={instituicaoForm.senha} onChange={handleInstituicaoChange} required error={errors.senha} />
                    <Input label="Confirmar Senha" name="confirmar_senha" type="password" value={instituicaoForm.confirmar_senha} onChange={handleInstituicaoChange} required error={errors.confirmar_senha} />
                </div>

                {errors.api && <p className="text-red-600 text-center">{errors.api}</p>}

                <Button type="submit" disabled={loading}>
                    {loading ? 'Cadastrando...' : 'Cadastrar Instituição'}
                </Button>
            </form>
        );
    };

    if (!userType) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
                <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-md text-center">
                    <h1 className="text-2xl font-bold mb-6 text-gray-800">Escolha o seu Perfil</h1>
                    <p className="text-gray-600 mb-8">Você está se cadastrando como:</p>
                    <div className="space-y-4">
                        <Button onClick={() => setUserType('candidato')}>
                            Agente de Apoio (Candidato)
                        </Button>
                        <Button onClick={() => setUserType('instituicao')} variant="secondary">
                            Instituição de Ensino
                        </Button>
                    </div>
                    <Link to="/login" className="mt-6 inline-block text-sm text-blue-600 hover:text-blue-800">
                        Já tem conta? Entrar
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen flex flex-col items-center pt-10 pb-20 bg-gray-100">
            <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
                <h1 className="text-3xl font-bold text-center mb-6 text-gray-800">
                    Cadastro de {userType === 'candidato' ? 'Agente de Apoio' : 'Instituição'}
                </h1>
                <p className="text-center text-sm mb-8 text-gray-500 cursor-pointer hover:text-blue-600" onClick={() => setUserType(null)}>
                    &larr; Voltar para a escolha de perfil
                </p>

                {userType === 'candidato' ? renderCandidatoForm() : renderInstituicaoForm()}
            </div>
        </div>
    );
};

export default RegisterPage;