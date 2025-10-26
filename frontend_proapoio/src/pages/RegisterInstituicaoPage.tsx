import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../contexts/AuthContext';
// Importação dos componentes de UI conforme solicitado:
import { Input, Select, CheckboxGroup, Button } from '../components/ui/index';


// =========================================================================================
// CONSTANTES E OPÇÕES (Mantidas localmente, pois são específicas desta página/domínio)
// =========================================================================================

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

// Estado Inicial
const initialInstituicaoState = {
    nome_fantasia: '',
    razao_social: '',
    cnpj: '',
    email: '', // Email de login
    senha: '',
    confirmar_senha: '',
    codigo_inep: '', 
    tipo_instituicao: '', 
    niveis_oferecidos: [] as string[], 
    email_corporativo: '', 
    celular_corporativo: '', 
    telefone_fixo: '', 
    nome_responsavel: '', 
    funcao_responsavel: '', 
    cep: '', 
    logradouro: '',
    numero: '', 
    bairro: '', // Requerido pelo BE
    cidade: '', // Requerido pelo BE
    estado: '', // Requerido pelo BE
    complemento: '',
    ponto_referencia: '',
};

// =========================================================================================
// LÓGICA DA PÁGINA
// =========================================================================================

const RegisterInstituicaoPage: React.FC = () => {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [instituicaoForm, setInstituicaoForm] = useState(initialInstituicaoState);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string | undefined>>({});

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
    };
    
    const validateInstituicao = () => {
        const newErrors: Record<string, string> = {};
        if (instituicaoForm.senha !== instituicaoForm.confirmar_senha) {
            newErrors.confirmar_senha = 'As senhas não coincidem.';
        }
        
        // Validações obrigatórias (MUITO IMPORTANTE: Alinhado com RegisterInstituicaoRequest.php)
        if (!instituicaoForm.nome_fantasia) { newErrors.nome_fantasia = 'O Nome Fantasia é obrigatório.'; }
        if (!instituicaoForm.razao_social) { newErrors.razao_social = 'A Razão Social é obrigatória.'; }
        if (!instituicaoForm.cnpj) { newErrors.cnpj = 'O CNPJ é obrigatório.'; }
        if (!instituicaoForm.email) { newErrors.email = 'O Email de Login é obrigatório.'; }
        if (!instituicaoForm.senha) { newErrors.senha = 'A Senha é obrigatória.'; }

        if (!instituicaoForm.tipo_instituicao) { newErrors.tipo_instituicao = 'O Tipo de Instituição é obrigatório.'; }
        if (instituicaoForm.niveis_oferecidos.length === 0) { newErrors.niveis_oferecidos = 'Selecione ao menos um Nível Oferecido.'; }
        if (!instituicaoForm.nome_responsavel) { newErrors.nome_responsavel = 'O Nome do Responsável é obrigatório.'; }
        if (!instituicaoForm.funcao_responsavel) { newErrors.funcao_responsavel = 'A Função do Responsável é obrigatória.'; }
        if (!instituicaoForm.email_corporativo) { newErrors.email_corporativo = 'O Email Corporativo é obrigatório.'; }
        if (!instituicaoForm.celular_corporativo) { newErrors.celular_corporativo = 'O Celular Corporativo é obrigatório.'; }
        
        // Validações de Endereço (Corrigido para ser 'required' conforme o BE)
        if (!instituicaoForm.cep) { newErrors.cep = 'O CEP é obrigatório.'; }
        if (!instituicaoForm.numero) { newErrors.numero = 'O Número do endereço é obrigatório.'; }
        if (!instituicaoForm.bairro) { newErrors.bairro = 'O Bairro é obrigatório.'; }
        if (!instituicaoForm.cidade) { newErrors.cidade = 'A Cidade é obrigatória.'; }
        if (!instituicaoForm.estado) { newErrors.estado = 'O Estado é obrigatório.'; }

        setErrors(newErrors);
        return Object.keys(newErrors).every(key => !newErrors[key]);
    };


    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!validateInstituicao()) {
            return;
        }

        setLoading(true);

        try {
            const payload = {
                ...instituicaoForm,
                // Mapeamento de campos para a API do Laravel
                nome: instituicaoForm.nome_fantasia, 
                // Remove o campo de confirmação antes de enviar
                confirmar_senha: undefined,
            };
            
            await api.post('/auth/register/instituicao', payload);
            
            // Sucesso: redireciona para login
            navigate('/login?success=instituicao');
        } catch (error) {
            console.error('Erro de registro:', error);
            // Lidar com erros de validação do Laravel ou de rede
            setErrors({ api: 'Ocorreu um erro ao processar seu cadastro. Verifique os dados e tente novamente.' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex flex-col items-center pt-10 pb-20 bg-gray-100">
            <div className="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl">
                <h1 className="text-3xl font-bold text-center mb-6 text-gray-800">
                    Cadastro de Instituição de Ensino
                </h1>
                <Link to="/register" className="text-center text-sm mb-8 inline-block text-gray-500 cursor-pointer hover:text-blue-600">
                    &larr; Voltar para a escolha de perfil
                </Link>

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
                    <div className="grid grid-cols-2 gap-4">
                        <Input label="CEP" name="cep" value={instituicaoForm.cep} onChange={handleInstituicaoChange} required error={errors.cep} />
                        <Input label="Número" name="numero" value={instituicaoForm.numero} onChange={handleInstituicaoChange} required error={errors.numero} />
                    </div>
                    {/* Logradouro não é required, mas Bairro, Cidade e Estado são! */}
                    <Input label="Logradouro" name="logradouro" value={instituicaoForm.logradouro} onChange={handleInstituicaoChange} error={errors.logradouro} />
                    <div className="grid grid-cols-3 gap-4">
                        <Input label="Bairro" name="bairro" value={instituicaoForm.bairro} onChange={handleInstituicaoChange} required error={errors.bairro} />
                        <Input label="Cidade" name="cidade" value={instituicaoForm.cidade} onChange={handleInstituicaoChange} required error={errors.cidade} />
                        <Input label="Estado" name="estado" value={instituicaoForm.estado} onChange={handleInstituicaoChange} required error={errors.estado} />
                    </div>
                    <Input label="Complemento (Opcional)" name="complemento" value={instituicaoForm.complemento} onChange={handleInstituicaoChange} error={errors.complemento} />
                    <Input label="Ponto de Referência (Opcional)" name="ponto_referencia" value={instituicaoForm.ponto_referencia} onChange={handleInstituicaoChange} error={errors.ponto_referencia} />

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
            </div>
        </div>
    );
};

export default RegisterInstituicaoPage;
