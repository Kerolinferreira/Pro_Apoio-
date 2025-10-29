import React, { useState, useEffect, useCallback, useMemo } from 'react';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { User, Mail, Phone, MapPin, Briefcase, GraduationCap, Accessibility, Save, Edit, Loader2, AlertTriangle, Trash2, PlusCircle, Eye, EyeOff } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext'; 

// ===================================
// TIPOS DE DADOS DO CANDIDATO
// ===================================

interface Endereco {
    cep: string;
    logradouro: string;
    numero: string;
    complemento: string;
    bairro: string;
    cidade: string;
    estado: string;
}

interface Deficiencia {
    id: number;
    nome: string;
    // ... outros campos
}

interface Experiencia {
    id?: number;
    tipo: 'pessoal' | 'profissional';
    titulo: string;
    descricao: string;
    data_inicio: string;
    data_fim: string | null; // null se atual
}

interface Candidato {
    id: number;
    nome_completo: string;
    email: string;
    telefone: string;
    data_nascimento: string;
    genero: string;
    escolaridade: string;
    cpf: string; // Sensível, mas necessário para identificação no sistema
    endereco: Endereco;
    deficiencias_atuadas: Deficiencia[]; // Deficiências com que possui experiência
    experiencias: Experiencia[];
    // ... outros campos (bio, foto, etc)
}

// ===================================
// CONSTANTES E OPÇÕES
// ===================================

const escolaridadeOptions = ['Fundamental Completo', 'Médio Completo', 'Superior Incompleto', 'Superior Completo', 'Pós-graduação'];
const generos = ['Masculino', 'Feminino', 'Outro', 'Não declarar'];
const estadosBrasileiros = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

// ===================================
// PÁGINA PRINCIPAL
// ===================================

const PerfilCandidatoPage: React.FC = () => {
    const { user } = useAuth();
    const [candidato, setCandidato] = useState<Candidato | null>(null);
    const [formData, setFormData] = useState<Partial<Candidato>>({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [editMode, setEditMode] = useState(false);
    const [deficienciaOptions, setDeficienciaOptions] = useState<Deficiencia[]>([]); // Lista de deficiências disponíveis na API

    // Mock de Deficiências disponíveis (deveria vir da API: GET /deficiencias)
    useEffect(() => {
        setDeficienciaOptions([
            { id: 1, nome: 'Visual' },
            { id: 2, nome: 'Auditiva' },
            { id: 3, nome: 'Física' },
            { id: 4, nome: 'Intelectual' },
        ]);
    }, []);

    // --- LÓGICA DE BUSCA DO PERFIL (GET /candidatos/me/) ---
    const fetchProfile = useCallback(async () => {
        if (!user) return;
        setLoading(true);
        setError(null);
        try {
            // GET /candidatos/me/ [cite: Documentação final.docx]
            const response = await api.get(`/candidatos/me/`);
            const data: Candidato = response.data;

            setCandidato(data);
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

    // --- LÓGICA DE SUBMISSÃO (PUT /candidatos/me/) ---
    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setError(null);

        // Validação básica (deveria ser mais robusta)
        if (!formData.nome_completo || !formData.email || !formData.escolaridade) {
            setError("Por favor, preencha todos os campos obrigatórios.");
            setSaving(false);
            return;
        }

        try {
            // PUT /candidatos/me/ [cite: Documentação final.docx]
            await api.put(`/candidatos/me/`, formData);
            
            // Atualiza o estado principal
            setCandidato(formData as Candidato); 
            setEditMode(false); 
            // TODO: Mostrar mensagem de sucesso (Toast)
            alert('Perfil atualizado com sucesso!'); // PLACEHOLDER
        } catch (err) {
            console.error('Erro ao salvar perfil:', err);
            setError('Erro ao salvar as alterações. Verifique os dados e tente novamente.');
        } finally {
            setSaving(false);
        }
    };

    // --- HANDLERS DO FORMULÁRIO ---
    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleAddressChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, endereco: { ...(prev.endereco || {}), [name]: value } }));
    };

    // Lógica para Deficiências Atuadas (Checkbox)
    const handleDeficienciaChange = (defId: number, isChecked: boolean) => {
        const currentDeficiencias = (formData.deficiencias_atuadas || []).map(d => d.id);
        let newDeficiencias: number[];

        if (isChecked) {
            newDeficiencias = [...currentDeficiencias, defId];
        } else {
            newDeficiencias = currentDeficiencias.filter(id => id !== defId);
        }

        // Reconstroi o array de objetos Deficiencia
        const newDeficienciaObjects = deficienciaOptions
            .filter(d => newDeficiencias.includes(d.id));

        setFormData(prev => ({ ...prev, deficiencias_atuadas: newDeficienciaObjects }));
    };

    // --- LÓGICA DE CEP (Acionar API ViaCEP via Proxy BE) ---
    const handleCepBlur = async (e: React.FocusEvent<HTMLInputElement>) => {
        const cep = e.target.value.replace(/\D/g, '');
        if (cep.length !== 8) return;

        try {
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
            // Mensagem amigável para o usuário
        }
    };

    // Renderização Condicional
    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorAlert message={error} />;
    if (!candidato) return <div className="container py-lg"><ErrorAlert message="Perfil não encontrado. Faça o login novamente." /></div>;

    // --- COMPONENTE DE EDIÇÃO DE SEÇÃO ---
    const Section: React.FC<{ title: string; children: React.ReactNode }> = ({ title, children }) => (
        <div className="card mb-lg">
            <h2 className="title-lg border-bottom-divider pb-sm mb-md">{title}</h2>
            {children}
        </div>
    );
    
    // --- FUNÇÃO DE RENDERIZAÇÃO DE CAMPO ---
    const renderField = (label: string, name: keyof Candidato | keyof Endereco, icon: React.ReactNode, type: string = 'text', options?: string[]) => {
        const isAddressField = (name as keyof Endereco) in (formData.endereco || {});
        const value = isAddressField ? (formData.endereco?.[name as keyof Endereco] || '') : (formData[name as keyof Candidato] || '');
        const onChangeHandler = isAddressField ? handleAddressChange : handleChange;
        const isDisabled = !editMode || (isAddressField && (name === 'logradouro' || name === 'bairro' || name === 'cidade')); // Desabilita campos preenchidos pelo CEP

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
                            onChange={onChangeHandler as any}
                            className="form-select with-icon"
                            disabled={isDisabled}
                        >
                            <option value="">Selecione...</option>
                            {options?.map(opt => (
                                <option key={opt} value={opt}>{opt}</option>
                            ))}
                        </select>
                    ) : (
                        <input
                            id={name as string}
                            name={name as string}
                            type={type}
                            value={value as string}
                            onChange={onChangeHandler as any}
                            onBlur={name === 'cep' ? handleCepBlur : undefined}
                            className="form-input with-icon"
                            disabled={isDisabled}
                            required={type !== 'complemento'}
                        />
                    )}
                </div>
            </div>
        );
    };

    // --- RENDERIZAÇÃO PRINCIPAL ---
    return (
        <div className="page-wrapper">
            <Header />
            <main className="container py-lg">
                <header className="flex-group-md-row mb-lg" style={{ justifyContent: 'space-between' }}>
                    <h1 className="heading-secondary">Meu Perfil de Agente de Apoio</h1>
                    
                    {/* Botão de Edição/Visualização */}
                    <button 
                        onClick={() => setEditMode(prev => !prev)}
                        className={editMode ? "btn-secondary btn-icon" : "btn-primary btn-icon"}
                    >
                        {editMode ? <Eye size={20} className="mr-sm" /> : <Edit size={20} className="mr-sm" />}
                        {editMode ? 'Modo Visualização' : 'Editar Perfil'}
                    </button>
                </header>

                {error && <ErrorAlert message={error} />}
                
                <form onSubmit={handleSave} className="space-y-lg">
                    
                    {/* SEÇÃO 1: DADOS PESSOAIS */}
                    <Section title="Dados Pessoais e Contato">
                        <div className="grid-2-col-lg">
                            {renderField('Nome Completo', 'nome_completo', <User size={20} />, 'text')}
                            {renderField('Email (Não editável)', 'email', <Mail size={20} />, 'email')}
                            {renderField('Telefone', 'telefone', <Phone size={20} />, 'tel')}
                            {renderField('Data de Nascimento', 'data_nascimento', <Calendar size={20} />, 'date')}
                            {renderField('CPF (Apenas leitura)', 'cpf', <Lock size={20} />, 'text')}
                            
                            {/* Gênero - Simulação de Select */}
                            <div className="form-group">
                                <label htmlFor="genero" className="form-label">Gênero</label>
                                <div className="form-input-icon-wrapper">
                                    <User size={20} className="form-icon" />
                                    <select
                                        id="genero"
                                        name="genero"
                                        value={formData.genero || ''}
                                        onChange={handleChange}
                                        className="form-select with-icon"
                                        disabled={!editMode}
                                    >
                                        <option value="">Selecione...</option>
                                        {generos.map(g => <option key={g} value={g}>{g}</option>)}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </Section>

                    {/* SEÇÃO 2: ENDEREÇO */}
                    <Section title="Endereço">
                        <div className="grid-2-col-lg">
                            {renderField('CEP', 'cep', <MapPin size={20} />, 'text')}
                            {renderField('Logradouro', 'logradouro', <MapPin size={20} />, 'text')}
                            
                            {renderField('Número', 'numero', <MapPin size={20} />, 'text')}
                            {renderField('Complemento (Opcional)', 'complemento', <MapPin size={20} />, 'text')}
                            
                            {renderField('Bairro', 'bairro', <MapPin size={20} />, 'text')}
                            {renderField('Cidade', 'cidade', <MapPin size={20} />, 'text')}
                            
                            {/* Estado - Simulação de Select */}
                            <div className="form-group">
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
                    
                    {/* SEÇÃO 3: EXPERIÊNCIA E ESCOLARIDADE */}
                    <Section title="Qualificação Profissional">
                        <div className="grid-2-col-lg">
                            {/* Escolaridade - Simulação de Select */}
                            {renderField('Nível de Escolaridade', 'escolaridade', <GraduationCap size={20} />, 'select', escolaridadeOptions)}
                        </div>
                        
                        <h3 className="title-md mt-md mb-md">Experiências Relevantes (Profissional e Pessoal)</h3>
                        
                        {/* Area de listagem de Experiências (Simplificada - A implementação completa requer sub-componentes) */}
                        <div className="space-y-md">
                            {/* Experiências Map */}
                            {(candidato.experiencias || []).length === 0 && (
                                <p className="text-muted">Nenhuma experiência cadastrada. Adicione para aumentar sua compatibilidade.</p>
                            )}
                            {(candidato.experiencias || []).map(exp => (
                                <div key={exp.id} className="card-simple flex-group-md-row" style={{ justifyContent: 'space-between' }}>
                                    <div className="flex-group" style={{ gap: '0.1rem' }}>
                                        <p className="font-semibold text-base-color">{exp.titulo} <span className="badge-gray text-xs ml-xs">{exp.tipo === 'pessoal' ? 'Pessoal' : 'Profissional'}</span></p>
                                        <p className="text-sm text-muted">{exp.descricao.substring(0, 80)}...</p>
                                    </div>
                                    {editMode && (
                                        <div className="flex-actions-start">
                                            <button type="button" className="btn-secondary btn-sm" aria-label="Editar experiência">Editar</button>
                                            <button type="button" className="btn-secondary btn-sm btn-error" aria-label="Remover experiência"><Trash2 size={16} /></button>
                                        </div>
                                    )}
                                </div>
                            ))}
                            
                            {editMode && (
                                <button type="button" className="btn-secondary btn-icon mt-md" aria-label="Adicionar nova experiência">
                                    <PlusCircle size={20} className="mr-sm" /> Adicionar Experiência
                                </button>
                            )}
                        </div>
                    </Section>

                    {/* SEÇÃO 4: DEFICIÊNCIAS ATUADAS */}
                    <Section title="Experiência com Deficiências Específicas">
                        <p className="text-sm text-muted mb-md">Selecione os tipos de deficiência com os quais você tem experiência de apoio ou atuação:</p>
                        
                        <div className="grid-2-col-lg space-y-xs">
                            {deficienciaOptions.map(def => (
                                <label key={def.id} className="checkbox-label text-base" style={{ fontWeight: '500' }}>
                                    <input
                                        type="checkbox"
                                        className="form-checkbox"
                                        checked={(formData.deficiencias_atuadas || []).some(d => d.id === def.id)}
                                        onChange={(e) => handleDeficienciaChange(def.id, e.target.checked)}
                                        disabled={!editMode}
                                    />
                                    <Accessibility size={20} className="text-brand-color mr-xs" />
                                    {def.nome}
                                </label>
                            ))}
                        </div>
                    </Section>

                    {/* BOTÕES DE SALVAR */}
                    {editMode && (
                        <div className="flex-actions-end mt-lg pt-lg border-top-divider">
                            <button
                                type="submit"
                                disabled={saving}
                                className="btn-primary btn-icon"
                            >
                                {saving ? <Loader2 size={20} className="icon-spin mr-sm" /> : <Save size={20} className="mr-sm" />}
                                {saving ? 'Salvando...' : 'Salvar Alterações'}
                            </button>
                        </div>
                    )}
                </form>
            </main>
            <Footer />
        </div>
    );
};

export default PerfilCandidatoPage;

// ===================================
// ESTADOS AUXILIARES (Para reutilização)
// ===================================

const LoadingSpinner: React.FC = () => (
    <div className="text-center py-xl" aria-live="polite" aria-busy="true">
      <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={32} />
      <p className="text-info">Carregando perfil...</p>
    </div>
);
const ErrorAlert: React.FC<{ message: string }> = ({ message }) => (
    <div className="alert alert-error text-center my-xl">
      <p className="title-md">{message}</p>
    </div>
);