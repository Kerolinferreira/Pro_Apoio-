import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { Heart, Send, MapPin, DollarSign, Calendar, Zap, AlertTriangle, Loader2, Link as LinkIcon, CheckCircle } from 'lucide-react';

// Tipos
interface Deficiencia {
    id: number;
    nome: string;
}

interface Vaga {
    id: number;
    titulo: string;
    descricao: string;
    tipo_apoio: string;            
    data_publicacao: string;       
    salario: number | null;
    localizacao: string;
    deficiencias: Deficiencia[];   
    necessidades_descricao: string;
    instituicao?: {
        id: number;
        nome_fantasia: string;
    };
}

// ===================================
// COMPONENTES AUXILIARES
// ===================================

const LoadingSpinner: React.FC = () => (
    <div className="text-center py-xl" aria-live="polite" aria-busy="true">
      <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={32} />
      <p className="text-info">Carregando detalhes da vaga...</p>
    </div>
);
const ErrorAlert: React.FC<{ message: string }> = ({ message }) => (
    <div className="alert alert-error text-center my-xl">
      <p className="title-md">{message}</p>
    </div>
);

interface InfoItemProps {
    icon: React.ReactNode;
    label: string;
    value: string;
    valueClass?: string;
}

const InfoItem: React.FC<InfoItemProps> = ({ icon, label, value, valueClass }) => (
    <div className="info-item">
        <p className="text-sm text-muted mb-xs flex-group-item">
            <span className="text-brand-color mr-xs">{icon}</span>
            {label}
        </p>
        <p className={`title-md text-base-color ${valueClass || ''}`}>{value}</p>
    </div>
);


// ===================================
// PÁGINA PRINCIPAL
// ===================================

const DetalhesVagaPage: React.FC = () => {
    const { id } = useParams<{ id: string }>();

    const [vaga, setVaga] = useState<Vaga | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    // Suposição: usuário logado é candidato
    const isCandidato = true; 
    const [isSaved, setIsSaved] = useState(false);
    const [isApplied, setIsApplied] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        const fetchVaga = async () => {
            if (!id) {
                setError('ID da vaga não fornecido.');
                setLoading(false);
                return;
            }
            try {
                // GET /vagas/{id} [cite: Documentação final.docx]
                const response = await api.get(`/vagas/${id}`);
                const data: Vaga = response.data;
                setVaga(data); 

                // TODO: Buscar status de relacionamento com o candidato logado (isSaved, isApplied)

                setLoading(false);
            } catch (err) {
                console.error('Erro ao buscar detalhes da vaga:', err);
                setError('Não foi possível carregar os detalhes desta vaga. Ela pode não existir.');
                setLoading(false);
            }
        };
        fetchVaga();
    }, [id]);

    const handleApply = () => {
        // TODO: Implementar Modal de Envio de Proposta/Formulário (POST /propostas)
        alert('Implementar Modal de Envio de Proposta. Após sucesso, setIsApplied(true).'); // PLACEHOLDER
        if (vaga) setIsApplied(true); // Simulação
    };

    const handleSave = async () => {
        if (!vaga || isApplied || isSaving) return;

        setIsSaving(true);
        try {
            if (isSaved) {
                // DELETE /candidatos/me/vagas-salvas/{id} (simulado)
                console.log('Remover vaga salva:', vaga.id);
            } else {
                // POST /candidatos/me/vagas-salvas (simulado)
                console.log('Salvar vaga:', vaga.id);
            }
            setIsSaved(prev => !prev);
        } catch (e) {
            console.error('Erro ao salvar/remover vaga:', e);
            // Mostrar feedback de erro
        } finally {
            setIsSaving(false);
        }
    };

    const formatSalary = (salary: number | null) =>
        salary != null ? `R$ ${salary.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : 'A combinar';

    if (loading) return <LoadingSpinner />;
    if (error) return <ErrorAlert message={error} />;
    if (!vaga) return <div className="container py-lg"><ErrorAlert message="Vaga não encontrada ou indisponível." /></div>;

    return (
        <div className="page-wrapper">
            <Header />
            <main className="container py-lg max-w-lg-content">
                
                {/* Usamos 'card' como container principal de conteúdo */}
                <div className="card"> 
                    
                    {/* Título e Instituição */}
                    <div className="border-bottom-divider pb-md mb-lg"> 
                        <h1 className="heading-secondary mb-xs">
                            {vaga.titulo}
                        </h1>
                        {vaga.instituicao ? (
                            <Link
                                to={`/instituicoes/${vaga.instituicao.id}`}
                                className="btn-link title-md btn-icon" 
                            >
                                <Building size={20} className="mr-xs" />
                                {vaga.instituicao.nome_fantasia}
                            </Link>
                        ) : (
                            <p className="text-muted title-md">Instituição Não Informada</p>
                        )}
                    </div>

                    {/* Informações Básicas (Grid 2 colunas) */}
                    <div className="grid-2-col gap-y-md gap-x-lg text-base"> 
                        
                        <InfoItem 
                            icon={<Zap size={20} />}
                            label="Tipo de Apoio" 
                            value={vaga.tipo_apoio} 
                        />
                        <InfoItem 
                            icon={<MapPin size={20} />}
                            label="Localização" 
                            value={vaga.localizacao} 
                        />
                        <InfoItem 
                            icon={<DollarSign size={20} />}
                            label="Remuneração Estimada" 
                            value={formatSalary(vaga.salario)} 
                            valueClass="text-success-color"
                        />
                        <InfoItem 
                            icon={<Calendar size={20} />}
                            label="Publicado em" 
                            value={new Date(vaga.data_publicacao).toLocaleDateString('pt-BR')} 
                        />
                    </div>

                    {/* Descrição Geral da Vaga */}
                    <section className="section-divider mt-lg pt-lg">
                        <h2 className="title-lg mb-md">Descrição da Oportunidade</h2>
                        <div className="text-base whitespace-pre-wrap">{vaga.descricao}</div>
                    </section>
                    
                    {/* Requisitos Específicos do Aluno */}
                    <section className="section-divider mt-lg pt-lg">
                        <h2 className="title-lg mb-md">Requisitos Específicos do Aluno</h2>

                        {/* Deficiências */}
                        <div className="mb-md">
                            <h3 className="title-md mb-xs flex-group-item">
                                <Accessibility size={20} className="mr-sm" /> Deficiências Associadas
                            </h3>
                            {Array.isArray(vaga.deficiencias) && vaga.deficiencias.length > 0 ? (
                                <div className="flex-wrap gap-sm">
                                    {vaga.deficiencias.map((def) => (
                                        <span
                                            key={def.id}
                                            className="badge-deficiencia" 
                                        >
                                            {def.nome}
                                        </span>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted italic">Nenhuma deficiência específica listada.</p>
                            )}
                        </div>

                        {/* Descrição do Apoio */}
                        <div>
                            <h3 className="title-md mb-xs">
                                Detalhes das Necessidades de Apoio
                            </h3>
                            {/* Usa a classe 'content-box-sm' para o fundo cinza claro e formatação */}
                            <div className="content-box-sm">
                                <p className="text-base text-base-color whitespace-pre-wrap">
                                    {vaga.necessidades_descricao || 'Nenhuma descrição detalhada de necessidades fornecida.'}
                                </p>
                            </div>
                        </div>
                    </section>


                    {/* Ações do Candidato */}
                    {isCandidato && (
                        <div className="section-divider mt-lg pt-lg flex-actions">
                            
                            {/* Mensagem se já foi aplicada */}
                            {isApplied && (
                                <div className="badge badge-success flex-group-item title-md">
                                    <CheckCircle size={20} /> Proposta Enviada
                                </div>
                            )}

                            {/* Botão Salvar Vaga */}
                            <button
                                onClick={handleSave}
                                disabled={isApplied || isSaving}
                                className={isSaved ? "btn-secondary btn-icon" : "btn-secondary btn-icon btn-brand-ring"}
                                aria-label={isSaved ? 'Remover vaga salva' : 'Salvar vaga'}
                            >
                                <Heart size={20} fill={isSaved ? 'var(--color-error)' : 'none'} color={isSaved ? 'var(--color-error)' : 'var(--color-text-muted)'} />
                                {isSaving ? 'Aguarde...' : (isSaved ? 'Vaga Salva' : 'Salvar Vaga')}
                            </button>

                            {/* Botão Candidatar-se */}
                            <button
                                onClick={handleApply}
                                disabled={isApplied}
                                className="btn-primary btn-icon"
                            >
                                <Send size={20} />
                                {isApplied ? 'Proposta Enviada' : 'Candidatar-se Agora'}
                            </button>
                        </div>
                    )}
                </div>
            </main>
            <Footer />
        </div>
    );
};

export default DetalhesVagaPage;