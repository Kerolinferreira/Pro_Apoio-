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

const Select = ({ label, name, value, onChange, required = false, options }) => (
    <div className="mb-4">
        <label htmlFor={name} className="block text-sm font-medium text-gray-700">{label}{required && <span className="text-red-500"> *</span>}</label>
        <select
            id={name}
            name={name}
            value={value}
            onChange={onChange}
            required={required}
            className="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        >
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
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

const initialInstituicaoState = {
    nome_fantasia: '',
    razao_social: '',
    cnpj: '',
    email: '',
    telefone: '',
    senha: '',
    confirmar_senha: '',
    // Desvio 4 será corrigido aqui: Faltam campos de endereço
    cep: '', 
    logradouro: '',
    numero: '',
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
    const [errors, setErrors] = useState({});

    const handleCandidatoChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setCandidatoForm(prev => ({ ...prev, [name]: value }));
        // Remover erro ao começar a digitar
        setErrors(prev => ({ ...prev, [name]: undefined }));

        // Lógica para auto-preenchimento de CEP, se implementada
        // if (name === 'cep' && value.length === 9) { fetchAddress(value, setCandidatoForm); }
    };

    const handleInstituicaoChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setInstituicaoForm(prev => ({ ...prev, [name]: value }));
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
        // ... outras validações obrigatórias (cnpj, cep, etc.)

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
                };
                await api.post('/candidatos/register', payload);
                // Lógica de sucesso - Redirecionar para login ou autenticar
                navigate('/login?success=candidato');

            } else if (userType === 'instituicao') {
                if (!validateInstituicao()) {
                    setLoading(false);
                    return;
                }
                const payload = {
                    ...instituicaoForm,
                    // Mapeamento e sanitização de dados para o payload da API
                };
                await api.post('/instituicoes/register', payload);
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
                    <Input label="Email" name="email" type="email" value={candidatoForm.email} onChange={handleCandidatoChange} required error={errors.email} />
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
        // Renderização para Instituição (Desvio 4 será corrigido aqui)
        return (
             <form onSubmit={handleSubmit} className="space-y-4">
                <Input label="Nome Fantasia" name="nome_fantasia" value={instituicaoForm.nome_fantasia} onChange={handleInstituicaoChange} required error={errors.nome_fantasia} />
                <Input label="Razão Social" name="razao_social" value={instituicaoForm.razao_social} onChange={handleInstituicaoChange} required error={errors.razao_social} />
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input label="CNPJ" name="cnpj" value={instituicaoForm.cnpj} onChange={handleInstituicaoChange} required error={errors.cnpj} />
                    <Input label="Telefone" name="telefone" value={instituicaoForm.telefone} onChange={handleInstituicaoChange} required error={errors.telefone} />
                </div>
                <Input label="Email" name="email" type="email" value={instituicaoForm.email} onChange={handleInstituicaoChange} required error={errors.email} />

                <h3 className="text-lg font-semibold mt-6 mb-2">Endereço</h3>
                {/* DESVIO 4 - CORREÇÃO: Campos de endereço para Instituição que estavam faltando */}
                <div className="grid grid-cols-2 gap-4">
                    <Input label="CEP" name="cep" value={instituicaoForm.cep} onChange={handleInstituicaoChange} required error={errors.cep} />
                    <Input label="Número" name="numero" value={instituicaoForm.numero} onChange={handleInstituicaoChange} required error={errors.numero} />
                </div>
                <Input label="Logradouro" name="logradouro" value={instituicaoForm.logradouro} onChange={handleInstituicaoChange} required error={errors.logradouro} />
                <div className="grid grid-cols-3 gap-4">
                    <Input label="Bairro" name="bairro" value={instituicaoForm.bairro} onChange={handleInstituicaoChange} required error={errors.bairro} />
                    <Input label="Cidade" name="cidade" value={instituicaoForm.cidade} onChange={handleInstituicaoChange} required error={errors.cidade} />
                    <Input label="Estado" name="estado" value={instituicaoForm.estado} onChange={handleInstituicaoChange} required error={errors.estado} />
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
