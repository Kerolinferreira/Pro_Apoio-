import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { User, MapPin, Briefcase, GraduationCap, Accessibility, Send, Heart, Loader2, AlertTriangle, Calendar, Zap, MessageSquare } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext'; 

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
 * Implementa ações de Enviar Proposta e Salvar Candidato.
 */
const PerfilCandidatoPublicPage: React.FC = () => {
    // ID do candidato a ser visualizado
    const { id } = useParams<{ id: string }>(); 
    // Supondo que APENAS Instituições autenticadas podem ver perfis públicos
    const { user } = useAuth(); 

    const [candidato, setCandidato] = useState<CandidatoPublico | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    
    // Simulação de estados de relacionamento com a Instituição logada
    const [isSaved, setIsSaved] = useState(false);
    const [isPropostaSent, setIsPropostaSent] = useState(false);
    const [savingStatus, setSavingStatus] = useState(false);

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
            
            // TODO: Buscar status de relacionamento (se a Instituição logada salvou ou enviou proposta)
            // Ex: const relationship = await api.get(`/instituicoes/me/candidatos/${id}/status`);
            // setIsSaved(relationship.data.salvo);
            // setIsPropostaSent(relationship.data.proposta_enviada);

        } catch (err) {
            console.error('Erro ao buscar perfil:', err);
            setError('Não foi possível carregar o perfil do candidato. O perfil pode não estar ativo ou o ID é inválido.');
        } finally {
            setLoading(false);
        }
    }, [id]);

    useEffect(() => {
        fetchProfile();
    }, [fetchProfile]);
    
    // --- AÇÕES DA INSTITUIÇÃO ---

    const handleSendProposta = () => {
        if (!user || user.tipo_usuario !== 'instituicao') {
            alert('Você precisa estar logado como Instituição para enviar uma proposta.'); // PLACEHOLDER
            return;
        }
        // TODO: Redirecionar para um modal ou página de Envio de Proposta (POST /propostas)
        alert(`Implementar modal de envio de proposta para ${candidato?.nome_completo}.`); // PLACEHOLDER
        // Após o envio bem-sucedido: setIsPropostaSent(true);
    };

    const handleSaveCandidate = async () => {
        if (!user || user.tipo_usuario !== 'instituicao' || !id || savingStatus) return;
        
        setSavingStatus(true);
        try {
            if (isSaved) {
                // DELETE /instituicoes/me/candidatos-salvos/{id} (simulado)
                // await api.delete(`/instituicoes/me/candidatos-salvos/${id}`);
                alert(`Candidato ${candidato?.nome_completo} removido dos salvos.`); // PLACEHOLDER
            } else {
                // POST /instituicoes/me/candidatos-salvos (simulado)
                // await api.post(`/instituicoes/me/candidatos-salvos`, { candidato_id: id });
                alert(`Candidato ${candidato?.nome_completo} salvo!`); // PLACEHOLDER
            }
            setIsSaved(prev => !prev);
        } catch (e) {
            console.error('Erro ao salvar candidato:', e);
            // Mostrar erro
        } finally {
            setSavingStatus(false);
        }
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
                            
                            {/* Botão Salvar Candidato */}
                            <button 
                                onClick={handleSaveCandidate}
                                className={isSaved ? "btn-secondary btn-icon btn-sm btn-error" : "btn-secondary btn-icon btn-sm"}
                                disabled={savingStatus || isPropostaSent}
                                aria-label={isSaved ? 'Remover candidato salvo' : 'Salvar este candidato'}
                            >
                                <Heart size={18} fill={isSaved ? 'var(--color-error)' : 'none'} color={isSaved ? 'var(--color-error)' : 'var(--color-text-muted)'} />
                                {isSaved ? 'Salvo' : 'Salvar'}
                            </button>
                            
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
                    <h3 className="title-md mb-md flex-group-item">
                        <Accessibility size={20} className="mr-sm" />
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