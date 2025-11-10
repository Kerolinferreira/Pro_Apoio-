import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { User, Mail, Phone, MapPin, Briefcase, GraduationCap, Accessibility, Save, Edit, Loader2, AlertTriangle, Trash2, PlusCircle, Eye, EyeOff, Calendar, Lock as LockIcon } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/Toast';
import { ESCOLARIDADE_OPTIONS, GENERO_OPTIONS, ESTADOS_BRASILEIROS } from '../constants/options';
import { parseApiError, getFieldErrorMessage } from '../utils/errorHandler';
import ImageUpload from '../components/ImageUpload';
import ChangePasswordModal from '../components/ChangePasswordModal';
import DeleteAccountModal from '../components/DeleteAccountModal';
import ExperienciaProfissionalModal from '../components/ExperienciaProfissionalModal';
import ExperienciaPessoalModal from '../components/ExperienciaPessoalModal';
import { logger } from '../utils/logger'; 

// ===================================
// TIPOS DE DADOS DO CANDIDATO
// ===================================

interface Endereco {
    cep?: string;
    logradouro?: string;
    numero?: string;
    complemento?: string;
    bairro?: string;
    cidade?: string;
    estado?: string;
}

interface Deficiencia {
    id: number;
    nome: string;
    // ... outros campos
}

interface ExperienciaProfissional {
    id_experiencia_profissional: number;
    id?: number; // Alias para compatibilidade
    idade_aluno?: number | null;
    tempo_experiencia?: string | null;
    interesse_mesma_deficiencia: boolean;
    descricao: string;
    deficiencias: Deficiencia[]; // Deficiências relacionadas
}

interface ExperienciaPessoal {
    id_experiencia_pessoal: number;
    id?: number; // Alias para compatibilidade
    interesse_atuar: boolean;
    descricao: string;
}

interface Candidato {
    id: number;
    nome_completo: string;
    email: string;
    telefone: string;
    data_nascimento: string;
    genero: string;
    escolaridade: string;
    experiencia?: string; // Resumo de experiência do candidato
    cpf: string; // Sensível, mas necessário para identificação no sistema
    foto_url?: string | null;
    endereco: Endereco;
    deficiencias_atuadas: Deficiencia[]; // Deficiências com que possui experiência
    experienciasProfissionais?: ExperienciaProfissional[];
    experienciasPessoais?: ExperienciaPessoal[];
    // ... outros campos (bio, foto, etc)
}

// Constantes agora importadas de src/constants/options.ts

// ===================================
// PÁGINA PRINCIPAL
// ===================================

const PerfilCandidatoPage: React.FC = () => {
    const { user, logout } = useAuth();
    const toast = useToast();
    const navigate = useNavigate();
    const [candidato, setCandidato] = useState<Candidato | null>(null);
    const [formData, setFormData] = useState<Partial<Candidato>>({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [editMode, setEditMode] = useState(false);
    const [deficienciaOptions, setDeficienciaOptions] = useState<Deficiencia[]>([]); // Lista de deficiências disponíveis na API
    const [isChangePasswordModalOpen, setIsChangePasswordModalOpen] = useState(false);
    const [isDeleteAccountModalOpen, setIsDeleteAccountModalOpen] = useState(false);
    const [isExpProfModalOpen, setIsExpProfModalOpen] = useState(false);
    const [isExpPessoalModalOpen, setIsExpPessoalModalOpen] = useState(false);
    const [deletingExpProId, setDeletingExpProId] = useState<number | null>(null);
    const [deletingExpPesId, setDeletingExpPesId] = useState<number | null>(null);

    // Busca lista de deficiências da API
    useEffect(() => {
        const fetchDeficiencias = async () => {
            try {
                const response = await api.get('/deficiencias');
                // Backend retorna: [{ id_deficiencia: 1, nome: 'Visual' }, ...]
                const options = response.data.map((def: any) => ({
                    id: def.id_deficiencia || def.id,
                    nome: def.nome
                }));
                setDeficienciaOptions(options);
            } catch (err) {
                logger.error('Erro ao buscar deficiências:', err);
                // Fallback: usar opções padrão em caso de erro
                setDeficienciaOptions([
                    { id: 1, nome: 'Visual' },
                    { id: 2, nome: 'Auditiva' },
                    { id: 3, nome: 'Física' },
                    { id: 4, nome: 'Intelectual' },
                ]);
            }
        };
        fetchDeficiencias();
    }, []);

    // --- LÓGICA DE BUSCA DO PERFIL (GET /candidatos/me/) ---
    const fetchProfile = useCallback(async () => {
        if (!user) return;
        setLoading(true);
        setError(null);
        try {
            // GET /candidatos/me/ [cite: Documentação final.docx]
            const response = await api.get(`/candidatos/me/`);
            const apiData = response.data;

            // Mapeia dados da API para o formato esperado pelo frontend
            const mappedData: Candidato = {
                ...apiData,
                id: apiData.id_usuario || apiData.id,
                nome_completo: apiData.nome_completo || apiData.nome || '',
                email: apiData.email || '',
                telefone: apiData.telefone || '',
                data_nascimento: apiData.data_nascimento || '',
                genero: apiData.genero || '',
                cpf: apiData.cpf || '',
                escolaridade: apiData.nivel_escolaridade || apiData.escolaridade || '',
                foto_url: apiData.foto_url || null,
                endereco: apiData.endereco || {
                    cep: apiData.cep || '',
                    logradouro: apiData.logradouro || '',
                    numero: apiData.numero || '',
                    complemento: apiData.complemento || '',
                    bairro: apiData.bairro || '',
                    cidade: apiData.cidade || '',
                    estado: apiData.estado || ''
                },
                deficiencias_atuadas: apiData.deficiencias_atuadas || [],
                experienciasProfissionais: apiData.experienciasProfissionais || [],
                experienciasPessoais: apiData.experienciasPessoais || [],
                experiencias: [
                    ...(apiData.experienciasProfissionais || []).map((exp: any) => ({
                        id: exp.id_experiencia_profissional,
                        tipo: 'profissional' as const,
                        titulo: exp.titulo || 'Experiência Profissional',
                        descricao: exp.descricao || '',
                        data_inicio: exp.data_inicio || '',
                        data_fim: exp.data_fim || null
                    })),
                    ...(apiData.experienciasPessoais || []).map((exp: any) => ({
                        id: exp.id_experiencia_pessoal,
                        tipo: 'pessoal' as const,
                        titulo: exp.titulo || 'Experiência Pessoal',
                        descricao: exp.descricao || '',
                        data_inicio: exp.data_inicio || '',
                        data_fim: exp.data_fim || null
                    }))
                ]
            };

            setCandidato(mappedData);
            setFormData(mappedData); // Inicializa o formulário com os dados atuais
        } catch (err) {
            logger.error('Erro ao buscar perfil:', err);
            setError('Não foi possível carregar seu perfil. Tente recarregar a página.');
        } finally {
            setLoading(false);
        }
    }, [user]);

    useEffect(() => {
        fetchProfile();
    }, [fetchProfile]);

    // --- LÓGICA DE EXCLUSÃO DE CONTA ---
    const handleDeleteSuccess = () => {
        logout(); // Faz logout do usuário
        navigate('/'); // Redireciona para a página inicial
    };

    // --- LÓGICA DE EXPERIÊNCIAS ---
    const handleDeleteExperienciaProfissional = async (id: number) => {
        if (!window.confirm('Tem certeza que deseja remover esta experiência profissional?')) {
            return;
        }

        setDeletingExpProId(id);
        try {
            await api.delete(`/candidatos/me/experiencias-profissionais/${id}`);
            toast.success('Experiência profissional removida com sucesso!');
            fetchProfile(); // Recarrega o perfil
        } catch (error: any) {
            const { generalMessage } = parseApiError(error);
            toast.error(generalMessage);
        } finally {
            setDeletingExpProId(null);
        }
    };

    const handleDeleteExperienciaPessoal = async (id: number) => {
        if (!window.confirm('Tem certeza que deseja remover esta experiência pessoal?')) {
            return;
        }

        setDeletingExpPesId(id);
        try {
            await api.delete(`/candidatos/me/experiencias-pessoais/${id}`);
            toast.success('Experiência pessoal removida com sucesso!');
            fetchProfile(); // Recarrega o perfil
        } catch (error: any) {
            const { generalMessage } = parseApiError(error);
            toast.error(generalMessage);
        } finally {
            setDeletingExpPesId(null);
        }
    };

    // --- LÓGICA DE SUBMISSÃO (PUT /candidatos/me/) ---
    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setError(null);
        setFieldErrors({});

        // Validação básica (deveria ser mais robusta)
        if (!formData.nome_completo || !formData.email || !formData.escolaridade) {
            setError("Por favor, preencha todos os campos obrigatórios.");
            setSaving(false);
            return;
        }

        try {
            // PUT /candidatos/me/ - Enviando apenas os campos editáveis
            // Mapeando campos do frontend para os nomes esperados pela API
            const payload = {
                nome_completo: formData.nome_completo,
                telefone: formData.telefone,
                data_nascimento: formData.data_nascimento,
                genero: formData.genero,
                experiencia: formData.experiencia, // Resumo de experiência do candidato
                // Envia dados de endereço como campos separados (não objeto aninhado)
                cep: formData.endereco?.cep,
                logradouro: formData.endereco?.logradouro,
                numero: formData.endereco?.numero,
                complemento: formData.endereco?.complemento,
                bairro: formData.endereco?.bairro,
                cidade: formData.endereco?.cidade,
                estado: formData.endereco?.estado,
                // Mapeia escolaridade para nivel_escolaridade (nome usado pelo backend)
                nivel_escolaridade: formData.escolaridade,
                deficiencias_atuadas: formData.deficiencias_atuadas?.map(d => d.id), // Envia apenas os IDs
            };
            const response = await api.put(`/candidatos/me/`, payload);
            const updatedData = response.data; // Opcional: usar dados retornados pela API

            // Atualiza o estado principal
            setCandidato(formData as Candidato);
            setEditMode(false);
            toast.success('Perfil atualizado com sucesso!');
        } catch (err) {
            logger.error('Erro ao salvar perfil:', err);

            // Usa o helper para parsear erros da API
            const { generalMessage, fieldErrors: parsedFieldErrors } = parseApiError(err, {
                'nivel_escolaridade': 'escolaridade',
                'cep': 'cep',
                'estado': 'estado'
            });

            // Define os erros de campo
            setFieldErrors(parsedFieldErrors);

            // Define a mensagem de erro com detalhes dos campos, se houver
            if (Object.keys(parsedFieldErrors).length > 0) {
                const fieldLabels: Record<string, string> = {
                    'nome_completo': 'Nome completo',
                    'telefone': 'Telefone',
                    'email': 'E-mail',
                    'escolaridade': 'Escolaridade',
                    'data_nascimento': 'Data de nascimento',
                    'genero': 'Gênero',
                    'cep': 'CEP',
                    'logradouro': 'Logradouro',
                    'bairro': 'Bairro',
                    'cidade': 'Cidade',
                    'estado': 'Estado'
                };
                const errorMessage = getFieldErrorMessage(parsedFieldErrors, fieldLabels);
                setError(errorMessage);
                toast.error(errorMessage);
            } else {
                setError(generalMessage);
                toast.error(generalMessage);
            }
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
            logger.error('Erro ao buscar CEP:', error);
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
    const renderField = (
        label: string,
        name: keyof Candidato | keyof Endereco,
        icon: React.ReactNode,
        type: string = 'text',
        options?: string[],
        readOnly: boolean = false,
        isRequired: boolean = true
    ) => {
        const isAddressField = (name as keyof Endereco) in (formData.endereco || {});
        const value = isAddressField ? (formData.endereco?.[name as keyof Endereco] || '') : (formData[name as keyof Candidato] || '');
        const onChangeHandler = isAddressField ? handleAddressChange : handleChange;
        const isDisabled = !editMode || readOnly || (isAddressField && (name === 'logradouro' || name === 'bairro' || name === 'cidade')); // Desabilita campos preenchidos pelo CEP

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
                            readOnly={readOnly}
                            required={isRequired}
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

                    {/* Botões de Ação */}
                    <div className="flex-actions-start">
                        <button
                            onClick={() => setIsChangePasswordModalOpen(true)}
                            className="btn-secondary btn-icon btn-sm"
                        >
                            <LockIcon size={18} />
                            <span className="hidden-sm">Alterar Senha</span>
                        </button>
                        <button
                            onClick={() => setEditMode(prev => !prev)}
                            className={editMode ? "btn-secondary btn-icon" : "btn-primary btn-icon"}
                        >
                            {editMode ? <Eye size={20} className="mr-sm" /> : <Edit size={20} className="mr-sm" />}
                            {editMode ? 'Modo Visualização' : 'Editar Perfil'}
                        </button>
                    </div>
                </header>

                {error && <ErrorAlert message={error} />}
                
                <form onSubmit={handleSave} className="space-y-lg">
                    
                    {/* SEÇÃO 1: DADOS PESSOAIS */}
                    <Section title="Dados Pessoais e Contato">
                        {/* Upload de Foto */}
                        <div className="mb-lg pb-lg border-bottom-divider">
                            <ImageUpload
                                currentImageUrl={candidato?.foto_url}
                                onUploadSuccess={(imageUrl) => {
                                    setCandidato(prev => prev ? { ...prev, foto_url: imageUrl } : null);
                                    setFormData(prev => ({ ...prev, foto_url: imageUrl }));
                                }}
                                uploadEndpoint="/candidatos/me/foto"
                                fieldName="foto"
                                label="Foto de Perfil"
                                disabled={!editMode}
                            />
                        </div>

                        <div className="grid-2-col-lg">
                            {renderField('Nome Completo', 'nome_completo', <User size={20} />, 'text', undefined, false, true)}
                            {renderField('Email (Não editável)', 'email', <Mail size={20} />, 'email', undefined, true, true)}
                            {renderField('Telefone', 'telefone', <Phone size={20} />, 'tel', undefined, false, true)}
                            {renderField('Data de Nascimento (Não editável)', 'data_nascimento', <Calendar size={20} />, 'date', undefined, true, true)}
                            {renderField('CPF (Não editável)', 'cpf', <LockIcon size={20} />, 'text', undefined, true, true)}
                            
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
                                        {GENERO_OPTIONS.map(g => <option key={g} value={g}>{g}</option>)}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </Section>

                    {/* SEÇÃO 2: ENDEREÇO */}
                    <Section title="Endereço">
                        <div className="grid-2-col-lg">
                            {renderField('CEP', 'cep', <MapPin size={20} />, 'text', undefined, false, true)}
                            {renderField('Logradouro', 'logradouro', <MapPin size={20} />, 'text', undefined, false, true)}

                            {renderField('Número (Opcional)', 'numero', <MapPin size={20} />, 'text', undefined, false, false)}
                            {renderField('Complemento (Opcional)', 'complemento', <MapPin size={20} />, 'text', undefined, false, false)}

                            {renderField('Bairro', 'bairro', <MapPin size={20} />, 'text', undefined, false, true)}
                            {renderField('Cidade', 'cidade', <MapPin size={20} />, 'text', undefined, false, true)}
                            
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
                                        {ESTADOS_BRASILEIROS.map(e => <option key={e} value={e}>{e}</option>)}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </Section>
                    
                    {/* SEÇÃO 3: EXPERIÊNCIA E ESCOLARIDADE */}
                    <Section title="Qualificação Profissional">
                        <div className="grid-2-col-lg">
                            {/* Escolaridade - Simulação de Select */}
                            {renderField('Nível de Escolaridade', 'escolaridade', <GraduationCap size={20} />, 'select', ESCOLARIDADE_OPTIONS as unknown as string[])}
                        </div>
                        
                        <h3 className="title-md mt-md mb-md">Experiências Relevantes</h3>

                        {/* Experiências Profissionais */}
                        <div className="mb-lg">
                            <div className="flex-between-center mb-sm">
                                <h4 className="heading-quaternary">Experiências Profissionais</h4>
                                {editMode && (
                                    <button
                                        type="button"
                                        onClick={() => setIsExpProfModalOpen(true)}
                                        className="btn-secondary btn-icon btn-sm"
                                        aria-label="Adicionar experiência profissional"
                                    >
                                        <PlusCircle size={18} className="mr-sm" /> Adicionar
                                    </button>
                                )}
                            </div>
                            <div className="space-y-sm">
                                {(candidato?.experienciasProfissionais || []).length === 0 && (
                                    <p className="text-muted text-sm">Nenhuma experiência profissional cadastrada.</p>
                                )}
                                {(candidato?.experienciasProfissionais || []).map(exp => (
                                    <div key={exp.id_experiencia_profissional} className="card-simple">
                                        <div className="flex-between-start">
                                            <div className="flex-1">
                                                <div className="flex-wrap gap-xs mb-xs">
                                                    <span className="badge-primary text-xs">Profissional</span>
                                                    {exp.tempo_experiencia && (
                                                        <span className="badge-gray text-xs">{exp.tempo_experiencia}</span>
                                                    )}
                                                    {exp.idade_aluno && (
                                                        <span className="badge-gray text-xs">Aluno: {exp.idade_aluno} anos</span>
                                                    )}
                                                </div>
                                                <p className="text-sm mb-xs">{exp.descricao}</p>
                                                {exp.deficiencias && exp.deficiencias.length > 0 && (
                                                    <div className="flex-wrap gap-xs">
                                                        {exp.deficiencias.map((def) => (
                                                            <span key={def.id} className="badge-outline text-xs">
                                                                {def.nome}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                                {exp.interesse_mesma_deficiencia && (
                                                    <p className="text-xs text-success mt-xs">
                                                        ✓ Interesse em trabalhar com as mesmas deficiências
                                                    </p>
                                                )}
                                            </div>
                                            {editMode && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteExperienciaProfissional(exp.id_experiencia_profissional)}
                                                    className="btn-secondary btn-sm btn-error"
                                                    aria-label="Remover experiência profissional"
                                                    disabled={deletingExpProId === exp.id_experiencia_profissional}
                                                >
                                                    {deletingExpProId === exp.id_experiencia_profissional ? (
                                                        <Loader2 size={16} className="icon-spin" />
                                                    ) : (
                                                        <Trash2 size={16} />
                                                    )}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Experiências Pessoais */}
                        <div>
                            <div className="flex-between-center mb-sm">
                                <h4 className="heading-quaternary">Experiências Pessoais</h4>
                                {editMode && (
                                    <button
                                        type="button"
                                        onClick={() => setIsExpPessoalModalOpen(true)}
                                        className="btn-secondary btn-icon btn-sm"
                                        aria-label="Adicionar experiência pessoal"
                                    >
                                        <PlusCircle size={18} className="mr-sm" /> Adicionar
                                    </button>
                                )}
                            </div>
                            <div className="space-y-sm">
                                {(candidato?.experienciasPessoais || []).length === 0 && (
                                    <p className="text-muted text-sm">Nenhuma experiência pessoal cadastrada.</p>
                                )}
                                {(candidato?.experienciasPessoais || []).map(exp => (
                                    <div key={exp.id_experiencia_pessoal} className="card-simple">
                                        <div className="flex-between-start">
                                            <div className="flex-1">
                                                <span className="badge-secondary text-xs mb-xs inline-block">Pessoal</span>
                                                <p className="text-sm mb-xs">{exp.descricao}</p>
                                                {exp.interesse_atuar && (
                                                    <p className="text-xs text-success">
                                                        ✓ Interesse em atuar profissionalmente
                                                    </p>
                                                )}
                                            </div>
                                            {editMode && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteExperienciaPessoal(exp.id_experiencia_pessoal)}
                                                    className="btn-secondary btn-sm btn-error"
                                                    aria-label="Remover experiência pessoal"
                                                    disabled={deletingExpPesId === exp.id_experiencia_pessoal}
                                                >
                                                    {deletingExpPesId === exp.id_experiencia_pessoal ? (
                                                        <Loader2 size={16} className="icon-spin" />
                                                    ) : (
                                                        <Trash2 size={16} />
                                                    )}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
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

                {/* ZONA DE PERIGO */}
                <section className="card mt-xl border-danger">
                    <div className="card-header bg-danger-light">
                        <h3 className="heading-tertiary text-danger">
                            <AlertTriangle size={20} className="mr-sm" />
                            Zona de Perigo
                        </h3>
                    </div>
                    <div className="card-body">
                        <div className="flex-between-center">
                            <div>
                                <h4 className="heading-quaternary mb-xs">Excluir Conta</h4>
                                <p className="text-muted mb-0">
                                    Exclui permanentemente sua conta e todos os seus dados. Esta ação não pode ser desfeita.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsDeleteAccountModalOpen(true)}
                                className="btn-danger btn-icon btn-sm"
                            >
                                <Trash2 size={18} />
                                <span className="hidden-sm">Excluir Conta</span>
                            </button>
                        </div>
                    </div>
                </section>
            </main>
            <Footer />

            {/* Modal de Alteração de Senha */}
            <ChangePasswordModal
                isOpen={isChangePasswordModalOpen}
                onClose={() => setIsChangePasswordModalOpen(false)}
                endpoint="/candidatos/me/senha"
            />

            {/* Modal de Exclusão de Conta */}
            <DeleteAccountModal
                isOpen={isDeleteAccountModalOpen}
                onClose={() => setIsDeleteAccountModalOpen(false)}
                onSuccess={handleDeleteSuccess}
                endpoint="/candidatos/me"
            />

            {/* Modal de Experiência Profissional */}
            <ExperienciaProfissionalModal
                isOpen={isExpProfModalOpen}
                onClose={() => setIsExpProfModalOpen(false)}
                onSuccess={fetchProfile}
                deficienciaOptions={deficienciaOptions}
            />

            {/* Modal de Experiência Pessoal */}
            <ExperienciaPessoalModal
                isOpen={isExpPessoalModalOpen}
                onClose={() => setIsExpPessoalModalOpen(false)}
                onSuccess={fetchProfile}
            />
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