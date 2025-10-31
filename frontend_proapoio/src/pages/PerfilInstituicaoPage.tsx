import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api'; // Certifique-se de que o caminho está correto
import Header from '../components/Header';
import Footer from '../components/Footer';
import { Briefcase, Building, Mail, Phone, MapPin, Save, Edit, Loader2, AlertTriangle, Eye, EyeOff, PlusCircle, Square, Lock as LockIcon, Pause, XCircle, CheckCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext'; 

// ===================================
// TIPOS DE DADOS DA INSTITUIÇÃO
// ===================================

interface Endereco { // Definido como opcional para campos que podem vir vazios ou ser preenchidos pelo CEP
    cep?: string; // Opcional, mas pode ser preenchido
    logradouro?: string; // Opcional, preenchido pelo CEP
    numero?: string; // Opcional, mas obrigatório no formulário
    complemento?: string; // Opcional
    bairro?: string; // Opcional, preenchido pelo CEP
    cidade?: string; // Opcional, preenchido pelo CEP
    estado?: string; // Opcional, preenchido pelo CEP
}

interface VagaResumo {
    id: number;
    titulo_vaga: string;
    cidade: string;
    regime_contratacao: string;
    status: 'ATIVA' | 'PAUSADA' | 'FECHADA'; // Status da vaga
    data_publicacao: string;
}

interface Instituicao {
    id: number;
    nome_fantasia: string;
    razao_social: string;
    cnpj: string; // Sensível, mas necessário para identificação
    email: string; // Email de contato da instituição
    telefone: string; // Telefone de contato da instituição
    descricao: string; // Descrição da instituição
    endereco: Endereco; // Objeto de endereço
    vagas: VagaResumo[]; // Array de vagas publicadas
}

// ===================================
// CONSTANTES E OPÇÕES
// ===================================

const estadosBrasileiros = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

// Mapeamento de Status de Vaga para Badge
const vagaStatusMap = {
    ATIVA: { label: 'Ativa', cls: 'badge-green', icon: <CheckCircle size={12} /> },
    PAUSADA: { label: 'Pausada', cls: 'badge-yellow', icon: <Pause size={12} /> },
    FECHADA: { label: 'Fechada', cls: 'badge-gray', icon: <XCircle size={12} /> },
};

// ===================================
// COMPONENTES AUXILIARES
// ===================================

const LoadingSpinner: React.FC = () => (
    <div className="text-center py-xl" aria-live="polite" aria-busy="true">
      <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={32} />
      <p className="text-info">Carregando perfil institucional...</p> {/* Mensagem de carregamento */}
    </div>
);
const ErrorAlert: React.FC<{ message: string }> = ({ message }) => (
    <div className="alert alert-error text-center my-xl">
      <p className="title-md">{message}</p>
    </div>
);


// ===================================
// COMPONENTE DE CAMPO DE SEÇÃO (DEFINIÇÃO ÚNICA E CORRETA)
// ===================================

const SectionField: React.FC<{ 
    label: string, // Rótulo do campo
    name: keyof Instituicao | keyof Endereco, // Nome do campo (pode ser da Instituição ou do Endereço)
    icon: React.ReactNode, // Ícone a ser exibido
    type?: string, // Tipo do input (text, email, tel, textarea, select)
    options?: string[], // Opções para select
    readonly?: boolean, // Se o campo é somente leitura
    formData: Partial<Instituicao>, // Dados do formulário
    handleChange: (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => void, 
    handleAddressChange: (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => void, 
    handleCepBlur?: (e: React.FocusEvent<HTMLInputElement | HTMLTextAreaElement>) => Promise<void>, 
    editMode: boolean 
}> = ({ label, name, icon, type = 'text', options, readonly = false, formData, handleChange, handleAddressChange, handleCepBlur, editMode }) => {
    
    // VERIFICA SE O CAMPO PERTENCE AO ENDEREÇO OU À INSTITUIÇÃO
    const isAddressField = name in (formData.endereco || {});
    const value = isAddressField // Valor do campo
        ? (formData.endereco?.[name as keyof Endereco] || '') 
        : (formData[name as keyof Instituicao] || '');
        
    const onChangeHandler = isAddressField ? handleAddressChange : handleChange;
    
    // Desabilitar o campo se não estiver no modo edição, for somente leitura, 
    // ou se for um campo de endereço que deve ser preenchido pelo CEP
    const isDisabled = !editMode || readonly || (isAddressField && (name === 'logradouro' || name === 'bairro' || name === 'cidade' || name === 'estado'));

    const InputComponent = type === 'textarea' ? 'textarea' : 'input'; // Componente a ser renderizado

    return (
        <div className="form-group">
            <label htmlFor={name as string} className="form-label">{label}</label>
            <div className="form-input-icon-wrapper">
                {icon}
                {type === 'select' ? (
                    <select
                        id={name as string}
                        name={name as string}
                        value={value as string}
                        onChange={onChangeHandler as any} // Handler de mudança
                        className="form-select with-icon"
                        disabled={isDisabled}
                    >
                        <option value="">Selecione...</option>
                        {options?.map(opt => (
                            <option key={opt} value={opt}>{opt}</option>
                        ))}
                    </select>
                ) : (
                    <InputComponent
                        id={name as string}
                        name={name as string}
                        type={type === 'textarea' ? undefined : type}
                        value={value as string}
                        onChange={onChangeHandler as any} // Handler de mudança
                        onBlur={name === 'cep' ? handleCepBlur : undefined}
                        className={type === 'textarea' ? 'form-textarea with-icon pl-lg' : 'form-input with-icon'}
                        disabled={isDisabled}
                        required={!readonly}
                        rows={type === 'textarea' ? 4 : undefined}
                    />
                )}
            </div>
        </div>
    );
};


// ===================================
// PÁGINA PRINCIPAL
// ===================================

const PerfilInstituicaoPage: React.FC = () => {
    const { user } = useAuth();
    const [instituicao, setInstituicao] = useState<Instituicao | null>(null); // Estado para os dados da instituição
    const [formData, setFormData] = useState<Partial<Instituicao>>({}); // Estado para os dados do formulário
    const [loading, setLoading] = useState(true); // Estado de carregamento
    const [saving, setSaving] = useState(false); // Estado de salvamento
    const [error, setError] = useState<string | null>(null);
    const [editMode, setEditMode] = useState(false);

    // --- LÓGICA DE BUSCA DO PERFIL (GET /instituicoes/me/) ---
    const fetchProfile = useCallback(async () => {
        if (!user) return;
        setLoading(true);
        setError(null);
        try {
            // GET /instituicoes/me/
            const response = await api.get(`/instituicoes/me/`);
            const data: Instituicao = response.data;

            setInstituicao(data);
            setFormData(data); // Inicializa o formulário com os dados atuais
        } catch (err) {
            console.error('Erro ao buscar perfil:', err);
            setError('Não foi possível carregar seu perfil. Tente recarregar a página.');
        } finally {
            setLoading(false);
        }
    }, [user]);

    useEffect(() => {
        fetchProfile();
    }, [fetchProfile]);

    // --- LÓGICA DE SUBMISSÃO (PUT /instituicoes/me/) ---
    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setError(null);

        if (!formData.nome_fantasia || !formData.cnpj) {
            setError("Por favor, preencha todos os campos obrigatórios.");
            setSaving(false);
            return;
        }

        try {
            // PUT /instituicoes/me/
            await api.put(`/instituicoes/me/`, formData);
            // Atualiza o estado principal
            setInstituicao(formData as Instituicao); 
            setEditMode(false); 
            // TODO: Mostrar mensagem de sucesso
            alert('Perfil atualizado com sucesso!'); // PLACEHOLDER
        } catch (err) {
            console.error('Erro ao salvar perfil:', err);
            setError('Erro ao salvar as alterações. Verifique os dados e tente novamente.');
        } finally {
            setSaving(false);
        }
    };

    // --- HANDLERS E UTILITÁRIOS ---
    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target; // Extrai nome e valor do evento
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleAddressChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target; // Extrai nome e valor do evento
        setFormData(prev => ({ ...prev, endereco: { ...(prev.endereco || {}), [name]: value } }));
    };

    const handleCepBlur = async (e: React.FocusEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const cep = e.target.value.replace(/\D/g, '');
        if (cep.length !== 8) return;
        
        try {
            // Busca o CEP na API ViaCEP através do proxy do backend
            const response = await api.get(`/external/viacep/${cep}`);
            
            if (!response.data.erro) {
                const data = response.data;
                setFormData(prev => ({
                    ...prev,
                    endereco: {
                        ...(prev.endereco || {}),
                        logradouro: data.logradouro,
                        bairro: data.bairro,
                        cidade: data.localidade,
                        estado: data.uf,
                    }
                }));
            }
        } catch (error) {
            console.error('Erro ao buscar CEP:', error);
        }
    };
    
    // --- FUNÇÕES CRUD DE VAGA (Ações Rápidas) ---
    const handleVagaAction = async (vagaId: number, action: 'pausar' | 'fechar') => {
        if (!instituicao) return;
        // Confirmação antes de executar a ação
        // TODO: Implementar Modal de Confirmação customizado (Substitui window.confirm)
        const confirmed = window.confirm(`Deseja ${action} a vaga ID ${vagaId}?`);
        if (!confirmed) return;
        
        try {
            // PUT /vagas/{id}/pausar ou /vagas/{id}/fechar
            await api.put(`/vagas/${vagaId}/${action}`);
            
            // Atualização otimista na UI para refletir a mudança de status
            setInstituicao(prev => prev ? {
                ...prev,
                vagas: prev.vagas.map(v => v.id === vagaId ? { ...v, status: action.toUpperCase() as 'PAUSADA' | 'FECHADA' } : v)
            } : null);
            
            alert(`Vaga ${action === 'pausar' ? 'pausada' : 'fechada'} com sucesso!`); // PLACEHOLDER
        } catch (e) {
            console.error(`Erro ao ${action} vaga:`, e);
            alert(`Falha ao ${action} a vaga.`); // PLACEHOLDER
        }
    };


    // --- COMPONENTE DE EDIÇÃO DE SEÇÃO ---
    const Section: React.FC<{ title: string; children: React.ReactNode }> = ({ title, children }) => (
        <div className="card mb-lg"> {/* Estilo de cartão com margem inferior */}
            <h2 className="title-lg border-bottom-divider pb-sm mb-md">{title}</h2>
            {children}
        </div>
    );
    
    // Renderização Condicional
    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorAlert message={error} />;
    if (!instituicao) return <div className="container py-lg"><ErrorAlert message="Perfil institucional não encontrado. Faça o login novamente." /></div>; // Exibe erro se o perfil não for encontrado

    // --- RENDERIZAÇÃO PRINCIPAL ---
    return (
        <div className="page-wrapper">
            <Header />
            <main className="container py-lg">
                <header className="flex-group-md-row mb-lg" style={{ justifyContent: 'space-between' }}>
                    <h1 className="heading-secondary">Perfil da Instituição</h1>
                    
                    {/* Botão de Edição/Visualização */}
                    <button 
                        onClick={() => setEditMode(prev => !prev)}
                        className={editMode ? "btn-secondary btn-icon" : "btn-primary btn-icon"} // Alterna entre botões de edição e visualização
                    >
                        {editMode ? <Eye size={20} className="mr-sm" /> : <Edit size={20} className="mr-sm" />}
                        {editMode ? 'Modo Visualização' : 'Editar Perfil'}
                    </button>
                </header>

                {error && <ErrorAlert message={error} />}
                
                <form onSubmit={handleSave} className="space-y-lg">
                    
                    {/* SEÇÃO 1: DADOS INSTITUCIONAIS */}
                    <Section title="Identificação e Contato">
                        <div className="grid-2-col-lg"> {/* Layout de grid com 2 colunas */}
                            <SectionField label="Nome Fantasia" name="nome_fantasia" icon={<Building size={20} />} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="Razão Social (Apenas leitura)" name="razao_social" icon={<LockIcon size={20} />} readonly formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="CNPJ (Apenas leitura)" name="cnpj" icon={<LockIcon size={20} />} readonly formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="Email" name="email" icon={<Mail size={20} />} type="email" formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="Telefone" name="telefone" icon={<Phone size={20} />} type="tel" formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                        </div>
                        {/* Campo de descrição da instituição */}
                        <div className="form-group mt-lg">
                            <SectionField label="Descrição da Instituição e Requisitos" name="descricao" icon={<Briefcase size={20} />} type="textarea" formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <p className="text-sm text-muted mt-xs">Descreva a missão da instituição e quais são os requisitos gerais para agentes de apoio.</p>
                        </div>
                    </Section>

                    {/* SEÇÃO 2: ENDEREÇO */}
                    <Section title="Endereço Principal">
                        <div className="grid-2-col-lg"> {/* Layout de grid com 2 colunas */}
                            <SectionField label="CEP" name="cep" icon={<MapPin size={20} />} handleCepBlur={handleCepBlur} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="Logradouro" name="logradouro" icon={<MapPin size={20} />} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            
                            <SectionField label="Número" name="numero" icon={<MapPin size={20} />} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="Complemento (Opcional)" name="complemento" icon={<MapPin size={20} />} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            
                            <SectionField label="Bairro" name="bairro" icon={<MapPin size={20} />} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            <SectionField label="Cidade" name="cidade" icon={<MapPin size={20} />} formData={formData} handleChange={handleChange} handleAddressChange={handleAddressChange} editMode={editMode} />
                            
                            {/* Estado - Simulação de Select */}
                            <div className="form-group"> {/* Campo de seleção para o estado */}
                                <label htmlFor="estado" className="form-label">Estado</label>
                                <div className="form-input-icon-wrapper">
                                    <MapPin size={20} className="form-icon" />
                                    <select
                                        id="estado"
                                        name="estado"
                                        value={formData.endereco?.estado || ''}
                                        onChange={handleAddressChange}
                                        className="form-select with-icon"
                                        disabled={!editMode}
                                    >
                                        <option value="">Selecione...</option>
                                        {estadosBrasileiros.map(e => <option key={e} value={e}>{e}</option>)}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </Section>
                    
                    {/* BOTÕES DE SALVAR */}
                    {editMode && (
                        <div className="flex-actions-end mt-lg pt-lg border-top-divider">
                            <button
                                type="submit"
                                disabled={saving}
                                className="btn-primary btn-icon"
                            > {/* Botão de salvar com ícone de carregamento */}
                                {saving ? <Loader2 size={20} className="icon-spin mr-sm" /> : <Save size={20} className="mr-sm" />}
                                {saving ? 'Salvando...' : 'Salvar Alterações'}
                            </button>
                        </div>
                    )}
                </form>

                {/* SEÇÃO 3: VAGAS PUBLICADAS (Apenas em Modo Visualização) */}
                <section className="mt-xl pt-lg border-top-divider" aria-labelledby="vagas-publicadas">
                    <header className="flex-group-md-row mb-lg" style={{ justifyContent: 'space-between' }}>
                        <h2 id="vagas-publicadas" className="title-lg">Vagas Publicadas ({instituicao.vagas.length})</h2> {/* Título da seção de vagas */}
                        <Link to="#" className="btn-primary btn-icon btn-sm"> {/* Botão para publicar nova vaga */}
                            <PlusCircle size={20} className="mr-sm" /> Publicar Nova Vaga
                        </Link>
                    </header>
                    {/* Exibe mensagem se não houver vagas publicadas */}
                    {instituicao.vagas.length === 0 ? (
                        <div className="alert alert-warning">
                            <p>Sua instituição ainda não publicou nenhuma vaga.</p>
                        </div>
                    ) : (
                        <ul className="space-y-md">
                            {instituicao.vagas.map(vaga => {
                                const status = vagaStatusMap[vaga.status] || vagaStatusMap.FECHADA;
                                return ( // Renderiza um card para cada vaga
                                    <li key={vaga.id} className="card-simple">
                                        <div className="flex-group-md-row" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                                            <div className="flex-group" style={{ gap: '0.1rem' }}>
                                                <Link to={`/vagas/${vaga.id}`} className="btn-link-clean title-md mb-xs"> {/* Link para os detalhes da vaga */}
                                                    {vaga.titulo_vaga}
                                                </Link>
                                                <p className="text-sm text-muted">{vaga.cidade} • {vaga.regime_contratacao}</p> {/* Informações da vaga */}
                                                <p className="text-sm text-muted">Publicado em: {new Date(vaga.data_publicacao).toLocaleDateString()}</p>
                                            </div>
                                            
                                            <div className={`badge ${status.cls} flex-group-item`} style={{ gap: '0.25rem' }}>
                                                {status.icon} {status.label}
                                            </div>
                                            
                                            <div className="flex-actions-start"> {/* Botões de ação para a vaga */}
                                                <Link to={`/vagas/${vaga.id}/editar`} className="btn-secondary btn-sm" aria-label={`Editar vaga ${vaga.titulo_vaga}`}> {/* Botão de editar */}
                                                    <Edit size={16} />
                                                </Link>
                                                {vaga.status === 'ATIVA' && (
                                                    <button onClick={() => handleVagaAction(vaga.id, 'pausar')} className="btn-secondary btn-sm btn-warning" aria-label="Pausar vaga"> {/* Botão de pausar vaga */}
                                                        <Pause size={16} />
                                                    </button>
                                                )}
                                                {vaga.status !== 'FECHADA' && (
                                                    <button onClick={() => handleVagaAction(vaga.id, 'fechar')} className="btn-secondary btn-sm btn-error" aria-label="Fechar vaga"> {/* Botão de fechar vaga */}
                                                        <XCircle size={16} />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </section>
                
            </main>
            <Footer />
        </div>
    );
};

export default PerfilInstituicaoPage;