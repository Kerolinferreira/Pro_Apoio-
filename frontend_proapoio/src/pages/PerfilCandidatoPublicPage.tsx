import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import PropostaModal from '../components/PropostaModal';
import { User, MapPin, Briefcase, GraduationCap, Send, Loader2, AlertTriangle, Calendar, Zap, MessageSquare, X, Copy, CheckCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/Toast';
import { logger } from '../utils/logger'; 

// ===================================
// TIPOS DE DADOS PÚBLICOS
// ===================================
// Os tipos devem refletir apenas o que é retornável publicamente.
interface EnderecoPublico {
    cidade: string;
    estado: string;
}

interface Deficiencia {
    id: number;
    nome: string;
}

interface Experiencia {
    id?: number;
    tipo: 'pessoal' | 'profissional';
    titulo: string;
    descricao: string;
    data_inicio: string;
    data_fim: string | null;
}

interface Vaga {
    id_vaga: number;
    titulo_vaga: string;
    titulo?: string;
    cidade: string;
    estado: string;
    tipo?: string;
    regime_contratacao?: string;
    status: string;
}

interface CandidatoPublico {
    id: number;
    nome_completo: string;
    // Campos privados como email, telefone, cpf NÃO devem ser exibidos
    data_nascimento: string;
    genero: string;
    escolaridade: string;
    endereco: EnderecoPublico; 
    deficiencias_atuadas: Deficiencia[]; 
    experiencias: Experiencia[];
    bio?: string; // Informação adicional
}

// ===================================
// COMPONENTES AUXILIARES
// ===================================

const LoadingSpinner: React.FC = () => (
    <div className="text-center py-xl" aria-live="polite" aria-busy="true">
      <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={32} />
      <p className="text-info">Carregando perfil público...</p>
    </div>
);
const ErrorAlert: React.FC<{ message: string }> = ({ message }) => (
    <div className="alert alert-error text-center my-xl">
      <p className="title-md">{message}</p>
    </div>
);

// Componente para exibir dados em modo leitura
const InfoDisplay: React.FC<{ label: string, value: string | React.ReactNode, icon: React.ReactNode }> = ({ label, value, icon }) => (
    <div className="info-display">
        <p className="text-sm text-muted mb-xs flex-group-item">
            <span className="text-brand-color mr-xs">{icon}</span>
            {label}
        </p>
        <p className="title-md text-base-color">{value}</p>
    </div>
);


/**
 * @component PerfilCandidatoPublicPage
 * @description Exibe o perfil do Agente de Apoio para Instituições (apenas dados públicos e relevantes).
 * Implementa ação de Enviar Proposta.
 */
const PerfilCandidatoPublicPage: React.FC = () => {
    // ID do candidato a ser visualizado
    const { id } = useParams<{ id: string }>();
    // Supondo que APENAS Instituições autenticadas podem ver perfis públicos
    const { user } = useAuth();
    const toast = useToast();

    const [candidato, setCandidato] = useState<CandidatoPublico | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isPropostaSent, setIsPropostaSent] = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [showPropostaModal, setShowPropostaModal] = useState(false);
    const [selectedVaga, setSelectedVaga] = useState<Vaga | null>(null);
    const [vagas, setVagas] = useState<Vaga[]>([]);
    const [loadingVagas, setLoadingVagas] = useState(false);
    const [checkingPropostas, setCheckingPropostas] = useState(false);

    // --- LÓGICA DE BUSCA DO PERFIL (GET /candidatos/{id}) ---
    const fetchProfile = useCallback(async () => {
        if (!id) {
            setError("ID do candidato não fornecido.");
            setLoading(false);
            return;
        }
        setLoading(true);
        setError(null);
        try {
            // GET /candidatos/{id} [cite: Documentação final.docx]
            const response = await api.get(`/candidatos/${id}`);
            const data: CandidatoPublico = response.data;

            setCandidato(data);

        } catch (err) {
            logger.error('Erro ao buscar perfil:', err);
            setError('Não foi possível carregar o perfil do candidato. O perfil pode não estar ativo ou o ID é inválido.');
        } finally {
            setLoading(false);
        }
    }, [id]);

    useEffect(() => {
        fetchProfile();
    }, [fetchProfile]);

    // --- BUSCAR VAGAS DA INSTITUIÇÃO ---
    const fetchVagas = async () => {
        setLoadingVagas(true);
        try {
            const response = await api.get('/vagas/minhas');
            const data = response.data.data || response.data;
            setVagas(Array.isArray(data) ? data.filter((v: Vaga) => v.status === 'ATIVA') : []);
        } catch (err) {
            logger.error('Erro ao buscar vagas:', err);
            toast.error('Não foi possível carregar suas vagas.');
        } finally {
            setLoadingVagas(false);
        }
    };

    // --- VERIFICAR PROPOSTAS EXISTENTES ---
    const checkExistingPropostas = useCallback(async () => {
        if (!user || user.tipo_usuario !== 'instituicao' || !id) return;

        setCheckingPropostas(true);
        try {
            // Busca propostas enviadas pela instituição
            const response = await api.get('/propostas?tipo=enviadas');
            const propostas = response.data.data || [];

            // Verifica se já existe proposta para este candidato
            const propostaExistente = propostas.find((p: any) =>
                p.id_candidato === parseInt(id) && p.status !== 'recusada'
            );

            if (propostaExistente) {
                setIsPropostaSent(true);
            }
        } catch (err) {
            logger.error('Erro ao verificar propostas:', err);
        } finally {
            setCheckingPropostas(false);
        }
    }, [user, id]);

    useEffect(() => {
        if (user && user.tipo_usuario === 'instituicao') {
            checkExistingPropostas();
        }
    }, [user, checkExistingPropostas]);

    // --- AÇÕES DA INSTITUIÇÃO ---

    const handleSendProposta = async () => {
        if (!user || user.tipo_usuario !== 'instituicao') {
            toast.warning('Você precisa estar logado como Instituição para enviar uma proposta.');
            return;
        }

        if (isPropostaSent) {
            toast.info('Você já enviou uma proposta para este candidato.');
            return;
        }

        setShowModal(true);
        await fetchVagas();
    };

    const handleSelectVaga = (vaga: Vaga) => {
        setSelectedVaga(vaga);
        setShowModal(false);
        setShowPropostaModal(true);
    };

    const handleSubmitProposta = async (mensagem: string) => {
        if (!selectedVaga || !id) {
            throw new Error('Dados incompletos para enviar proposta');
        }

        try {
            await api.post('/propostas', {
                id_vaga: selectedVaga.id_vaga,
                id_candidato: parseInt(id),
                mensagem: mensagem,
            });

            setIsPropostaSent(true);
            setShowPropostaModal(false);
            setSelectedVaga(null);
            toast.success('Proposta enviada com sucesso! O candidato será notificado.');
        } catch (err: any) {
            logger.error('Erro ao enviar proposta:', err);

            const errorMsg = err.response?.data?.message ||
                           err.response?.data?.errors?.id_vaga?.[0] ||
                           'Não foi possível enviar a proposta.';

            if (errorMsg.includes('já possui uma proposta')) {
                setIsPropostaSent(true);
                toast.error('Você já enviou uma proposta para este candidato nesta vaga.');
            } else {
                toast.error(errorMsg);
            }

            throw err;
        }
    };

    const handleCopyVagaLink = (vagaId: number) => {
        const link = `${window.location.origin}/vagas/${vagaId}`;
        navigator.clipboard.writeText(link);
        toast.success('Link da vaga copiado! Compartilhe com o candidato.');
    };


    // Renderização Condicional
    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorAlert message={error} />;
    if (!candidato) return <div className="container py-lg"><ErrorAlert message="Candidato não encontrado." /></div>;

    // --- RENDERIZAÇÃO DE SEÇÃO ---
    const Section: React.FC<{ title: string; children: React.ReactNode }> = ({ title, children }) => (
        <div className="card mb-lg">
            <h2 className="title-lg border-bottom-divider pb-sm mb-md">{title}</h2>
            {children}
        </div>
    );
    
    const formatExperienciaPeriodo = (inicio: string, fim: string | null) => {
        const start = new Date(inicio).toLocaleDateString('pt-BR', { year: 'numeric', month: 'short' });
        const end = fim ? new Date(fim).toLocaleDateString('pt-BR', { year: 'numeric', month: 'short' }) : 'Atual';
        return `${start} - ${end}`;
    };

    // --- RENDERIZAÇÃO PRINCIPAL ---
    return (
        <div className="page-wrapper">
            <Header />
            <main className="container py-lg max-w-lg-content">
                <header className="flex-group-md-row mb-lg" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div>
                        <h1 className="heading-secondary">{candidato.nome_completo}</h1>
                        <p className="text-lg text-muted">Agente de Apoio | {candidato.escolaridade}</p>
                    </div>
                    
                    {/* AÇÕES PARA INSTITUIÇÃO (Visível apenas se Instituição estiver logada) */}
                    {user && user.tipo_usuario === 'instituicao' && (
                        <div className="flex-actions-start">
                            {/* Botão Enviar Proposta */}
                            <button
                                onClick={handleSendProposta}
                                className="btn-primary btn-icon btn-sm"
                                disabled={isPropostaSent}
                                aria-label={isPropostaSent ? 'Proposta já enviada' : 'Enviar proposta de vaga'}
                            >
                                <Send size={18} />
                                {isPropostaSent ? 'Proposta Enviada' : 'Enviar Proposta'}
                            </button>
                        </div>
                    )}
                </header>

                {/* DADOS RESUMIDOS (GRID) */}
                <div className="card mb-lg">
                    <div className="grid-3-col">
                        <InfoDisplay 
                            label="Localização" 
                            value={`${candidato.endereco.cidade} - ${candidato.endereco.estado}`} 
                            icon={<MapPin size={20} />}
                        />
                        <InfoDisplay 
                            label="Escolaridade" 
                            value={candidato.escolaridade} 
                            icon={<GraduationCap size={20} />}
                        />
                         <InfoDisplay 
                            label="Experiência" 
                            value={`${candidato.experiencias.filter(e => e.tipo === 'profissional').length} anos profissionais`} 
                            icon={<Briefcase size={20} />}
                        />
                    </div>
                </div>

                {/* SEÇÃO 1: EXPERIÊNCIA E BIO */}
                <Section title="Experiência e Biografia">
                    {candidato.bio && (
                        <div className="mb-md">
                            <h3 className="title-md mb-xs">Bio do Agente</h3>
                            <div className="content-box-sm">
                                <p className="text-base text-base-color whitespace-pre-wrap">{candidato.bio}</p>
                            </div>
                        </div>
                    )}

                    <h3 className="title-md mb-md">Histórico Profissional e Pessoal</h3>
                    
                    <div className="space-y-lg">
                        {(candidato.experiencias || []).length === 0 && (
                            <p className="text-muted">Nenhuma experiência cadastrada.</p>
                        )}
                        {(candidato.experiencias || []).map(exp => (
                            <div key={exp.id} className="border-bottom-divider pb-md">
                                <p className="title-md text-base-color mb-xs">{exp.titulo}</p>
                                <p className="text-sm text-muted mb-xs flex-group-item">
                                    <Calendar size={16} className="mr-xs" />
                                    {formatExperienciaPeriodo(exp.data_inicio, exp.data_fim)}
                                    <span className="badge-gray text-xs ml-sm">{exp.tipo === 'pessoal' ? 'Pessoal' : 'Profissional'}</span>
                                </p>
                                <p className="text-base text-base-color">{exp.descricao}</p>
                            </div>
                        ))}
                    </div>
                </Section>

                {/* SEÇÃO 2: DEFICIÊNCIAS E HABILIDADES ATUADAS */}
                <Section title="Experiência em Atendimento">
                    <h3 className="title-md mb-md">
                        Tipos de Deficiência com Experiência
                    </h3>
                    
                    {Array.isArray(candidato.deficiencias_atuadas) && candidato.deficiencias_atuadas.length > 0 ? (
                        <div className="flex-wrap gap-sm">
                            {candidato.deficiencias_atuadas.map(def => (
                                <span key={def.id} className="badge-deficiencia">
                                    {def.nome}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted italic">O candidato não listou experiências com tipos específicos de deficiência.</p>
                    )}
                </Section>
                
                {/* AVISO DE PRIVACIDADE */}
                <div className="alert alert-info text-sm mt-lg">
                    <AlertTriangle size={20} className="inline mr-sm" />
                    As informações de contato (Email/Telefone) e o CPF são privados e só serão liberados após a aceitação mútua de uma proposta.
                </div>
            </main>
            <Footer />

            {/* MODAL: CONVIDAR CANDIDATO PARA VAGA */}
            {showModal && (
                <div className="modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2 className="title-lg">Convidar {candidato.nome_completo}</h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="btn-icon-only"
                                aria-label="Fechar modal"
                            >
                                <X size={24} />
                            </button>
                        </div>

                        <div className="modal-body">
                            <p className="text-base text-muted mb-md">
                                Selecione uma de suas vagas ativas para compartilhar com este candidato:
                            </p>

                            {loadingVagas ? (
                                <div className="text-center py-lg">
                                    <Loader2 className="icon-spin text-brand-color mx-auto mb-sm" size={32} />
                                    <p className="text-muted">Carregando suas vagas...</p>
                                </div>
                            ) : vagas.length === 0 ? (
                                <div className="alert alert-warning">
                                    <p>Você não possui vagas ativas no momento.</p>
                                    <Link to="/instituicao/vagas/nova" className="btn-primary mt-sm">
                                        Criar Nova Vaga
                                    </Link>
                                </div>
                            ) : (
                                <div className="space-y-md max-h-400 overflow-y-auto">
                                    {vagas.map((vaga) => (
                                        <div key={vaga.id_vaga} className="card-hover border-divider p-md">
                                            <div className="flex-group-md-row" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                                                <div className="flex-1">
                                                    <h3 className="title-md mb-xs">
                                                        {vaga.titulo_vaga || vaga.titulo}
                                                    </h3>
                                                    <p className="text-sm text-muted flex-group-item mb-xs">
                                                        <MapPin size={16} className="mr-xs" />
                                                        {vaga.cidade} - {vaga.estado}
                                                    </p>
                                                    {vaga.tipo && (
                                                        <span className="badge-gray text-xs">{vaga.tipo}</span>
                                                    )}
                                                </div>
                                                <div className="flex-actions-end gap-xs">
                                                    <button
                                                        onClick={() => handleCopyVagaLink(vaga.id_vaga)}
                                                        className="btn-secondary btn-icon btn-sm"
                                                        aria-label="Copiar link da vaga"
                                                        title="Copiar link"
                                                    >
                                                        <Copy size={16} />
                                                    </button>
                                                    <button
                                                        onClick={() => handleSelectVaga(vaga)}
                                                        className="btn-primary btn-icon btn-sm"
                                                        aria-label="Enviar proposta com esta vaga"
                                                    >
                                                        <Send size={16} />
                                                        Enviar Proposta
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="modal-footer">
                            <button
                                onClick={() => setShowModal(false)}
                                className="btn-secondary"
                            >
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL: PROPOSTA COM MENSAGEM PERSONALIZADA */}
            {showPropostaModal && selectedVaga && (
                <PropostaModal
                    isOpen={showPropostaModal}
                    onClose={() => {
                        setShowPropostaModal(false);
                        setSelectedVaga(null);
                    }}
                    onSubmit={handleSubmitProposta}
                    vagaTitulo={selectedVaga.titulo_vaga || selectedVaga.titulo || 'Vaga'}
                    mode="institution"
                />
            )}
        </div>
    );
};

export default PerfilCandidatoPublicPage;

// ===================================
// ESTADOS AUXILIARES (Reutilização)
// ===================================

// Funções para display de data relativa (deve ser importada de utils)
const formatExperienciaPeriodo = (inicio: string, fim: string | null) => {
    const start = new Date(inicio).toLocaleDateString('pt-BR', { year: 'numeric', month: 'short' });
    const end = fim ? new Date(fim).toLocaleDateString('pt-BR', { year: 'numeric', month: 'short' }) : 'Atual';
    return `${start} - ${end}`;
};