import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/Toast';
import { Heart, Send, MapPin, DollarSign, Calendar, Zap, AlertTriangle, Loader2 } from 'lucide-react'; // Ícones para melhor UX
import { parseApiError } from '../utils/errorHandler';
import PropostaModal from '../components/PropostaModal';
import { logger } from '../utils/logger';

// Tipos
interface Deficiencia {
  id: number;
  nome: string;
}

interface Vaga {
  id: number;
  titulo: string;
  descricao: string;
  tipo_apoio: string; // Ex.: Presencial, Online
  data_publicacao: string; // ISO string
  salario: number | null;
  localizacao: string;
  deficiencias: Deficiencia[]; // Lista de deficiências associadas
  necessidades_descricao: string;
  ja_candidatou?: boolean; // Indica se o candidato já se candidatou à vaga
  instituicao?: {
    id: number;
    nome_fantasia: string;
  };
}

/**
 * @component LoadingSpinner
 * @description Componente de carregamento para UX aprimorada.
 */
const LoadingSpinner: React.FC = () => (
  <div className="text-center py-xl" aria-live="polite" aria-busy="true">
    <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={32} />
    <p className="text-info">Carregando detalhes da vaga...</p>
  </div>
);

/**
 * @component ErrorAlert
 * @description Alerta de erro para exibir mensagens importantes.
 */
const ErrorAlert: React.FC<{ message: string }> = ({ message }) => (
  <div className="alert alert-error text-center my-xl">
    <p className="title-md">{message}</p>
  </div>
);

/**
 * @component DetalhesVagaPage
 * @description Exibe os detalhes completos de uma vaga e permite ações do candidato (Salvar/Candidatar-se).
 * Totalmente refatorado para usar classes semânticas do global.css.
 */
const DetalhesVagaPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const toast = useToast();

  const [vaga, setVaga] = useState<Vaga | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Estados de Ação (devem vir do backend após a requisição da vaga)
  const isCandidato = user?.tipo_usuario === 'candidato';
  const [isSaved, setIsSaved] = useState(false);
  const [isApplied, setIsApplied] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  // Estados do Modal de Proposta
  const [isPropostaModalOpen, setIsPropostaModalOpen] = useState(false);
  const [candidatoId, setCandidatoId] = useState<number | null>(null);

  // Busca o ID do candidato quando o usuário é candidato
  useEffect(() => {
    const fetchCandidatoId = async () => {
      if (!isCandidato || !user) return;

      try {
        const response = await api.get('/candidatos/me/');
        setCandidatoId(response.data.id);
      } catch (err) {
        logger.error('Erro ao buscar ID do candidato:', err);
      }
    };

    fetchCandidatoId();
  }, [isCandidato, user]);

  useEffect(() => {
    const fetchVaga = async () => {
      if (!id) {
        setError('ID da vaga não fornecido. [cite: Documentação final.docx - GET /vagas/{id}]');
        setLoading(false);
        return;
      }
      try {
        // GET /vagas/{id} [cite: Documentação final.docx]
        const response = await api.get(`/vagas/${id}`);
        const vagaData = response.data as Vaga;
        setVaga(vagaData);

        // Atualiza o status de candidatura a partir do backend
        if (vagaData.ja_candidatou !== undefined) {
          setIsApplied(vagaData.ja_candidatou);
        }

        setLoading(false);
      } catch (err) {
        logger.error('Erro ao buscar detalhes da vaga:', err);

        // Usa o helper para parsear erros da API
        const { generalMessage } = parseApiError(err);
        const status = (err as any)?.response?.status;

        if (status === 404) {
          setError('Vaga não encontrada. Ela pode ter sido removida ou fechada pela instituição.');
        } else if (status === 403) {
          setError('Você não tem permissão para visualizar esta vaga.');
        } else if (!(err as any).response) {
          setError('Não foi possível conectar ao servidor. Verifique sua conexão com a internet.');
        } else {
          setError(generalMessage);
        }

        setLoading(false);
      }
    };
    fetchVaga();
  }, [id]);

  /**
   * @function handleApply
   * @description Abre o modal de candidatura
   */
  const handleApply = () => {
    if (!candidatoId) {
      toast.error('Erro ao identificar candidato. Tente fazer login novamente.');
      return;
    }
    setIsPropostaModalOpen(true);
  };

  /**
   * @function handleSubmitProposta
   * @description Envia a proposta para a API
   */
  const handleSubmitProposta = async (mensagem: string) => {
    if (!id || !candidatoId) {
      throw new Error('Dados incompletos para enviar proposta');
    }

    try {
      await api.post('/propostas', {
        id_vaga: parseInt(id),
        id_candidato: candidatoId,
        mensagem: mensagem,
      });

      setIsApplied(true);
      toast.success('Proposta enviada com sucesso! A instituição receberá sua candidatura.');
    } catch (err) {
      logger.error('Erro ao enviar proposta:', err);

      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;
      const errorData = (err as any)?.response?.data;

      if (status === 400 && errorData?.message?.includes('já possui uma proposta')) {
        toast.error('Você já enviou uma proposta para esta vaga.');
        setIsApplied(true);
      } else if (status === 401) {
        toast.error('Você precisa estar logado para se candidatar.');
      } else if (status === 404) {
        toast.error('Vaga não encontrada.');
      } else {
        toast.error(generalMessage || 'Não foi possível enviar sua proposta. Tente novamente.');
      }

      throw err; // Re-throw para que o modal saiba que houve erro
    }
  };

  /**
   * @function handleSave
   * @description Lógica para salvar/remover vaga da lista de favoritas do candidato.
   * Utiliza os endpoints: POST /vagas/{id}/salvar e DELETE /vagas/{id}/remover.
   */
  const handleSave = async () => {
    if (!id || isSaving) return;

    setIsSaving(true);
    try {
      if (isSaved) {
        // DELETE /vagas/{id}/remover
        await api.delete(`/vagas/${id}/remover`);
        logger.log('Vaga removida das salvas:', id);
      } else {
        // POST /vagas/{id}/salvar
        await api.post(`/vagas/${id}/salvar`);
        logger.log('Vaga salva com sucesso:', id);
      }
      setIsSaved((prev) => !prev);
      toast.success(isSaved ? 'Vaga removida dos salvos.' : 'Vaga salva com sucesso!');
    } catch (err) {
      logger.error('Erro ao salvar/remover vaga:', err);

      // Usa o helper para parsear erros da API
      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;

      if (status === 401) {
        toast.error('Você precisa estar logado para salvar vagas.');
      } else if (status === 404) {
        toast.error('Vaga não encontrada. Ela pode ter sido removida.');
      } else if (!(err as any).response) {
        toast.error('Não foi possível conectar ao servidor. Verifique sua conexão.');
      } else {
        toast.error(generalMessage || 'Não foi possível atualizar o status da vaga. Tente novamente.');
      }
    } finally {
      setIsSaving(false);
    }
  };

  /**
   * @function formatSalary
   * @description Formata o valor do salário para BRL.
   */
  const formatSalary = (salary: number | null) =>
    salary != null ? `R$ ${salary.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}` : 'A combinar';

  // Renderização condicional de estados
  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorAlert message={error} />;
  if (!vaga) return <ErrorAlert message="Vaga não encontrada ou indisponível." />;

  return (
    <div className="page-wrapper">
      <Header />
      <main className="container py-lg">
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
                className="text-link title-md" // text-link é a classe global para links
              >
                {vaga.instituicao.nome_fantasia}
              </Link>
            ) : (
              <p className="text-muted">Instituição Não Informada</p>
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

          {/* Requisitos Específicos do Aluno */}
          <section className="section-divider mt-lg pt-lg">
            <h2 className="title-lg mb-md">Requisitos Específicos do Aluno</h2>

            {/* Deficiências */}
            <div className="mb-lg">
              <h3 className="title-md mb-xs">Deficiências Associadas</h3>
              {Array.isArray(vaga.deficiencias) && vaga.deficiencias.length > 0 ? (
                <div className="flex-wrap gap-sm">
                  {vaga.deficiencias.map((def) => (
                    // Classe global para badge de deficiência
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

            {/* Descrição do Apoio (Necessidades) */}
            <div>
              <h3 className="title-md mb-xs">
                Descrição Detalhada das Necessidades (Tarefas/Apoio)
              </h3>
              {/* Usa a classe 'content-box' para o fundo cinza claro e formatação */}
              <div className="content-box">
                <p className="text-base whitespace-pre-wrap">
                  {vaga.necessidades_descricao || 'Nenhuma descrição detalhada de necessidades fornecida.'}
                </p>
              </div>
            </div>
          </section>

          {/* Descrição Geral da Vaga */}
          <section className="section-divider mt-lg pt-lg">
            <h2 className="title-lg mb-md">Descrição da Oportunidade</h2>
            <div className="text-base whitespace-pre-wrap">{vaga.descricao}</div>
          </section>

          {/* Ações do Candidato */}
          {isCandidato && (
            <div className="section-divider mt-lg pt-lg flex-actions">

              {/* Botão Salvar Vaga */}
              <button
                onClick={handleSave}
                disabled={isApplied || isSaving}
                className={`btn-secondary btn-icon ${isApplied ? 'hidden' : ''}`}
                aria-label={isSaved ? 'Remover dos favoritos' : 'Salvar vaga nos favoritos'}
              >
                <Heart size={20} fill={isSaved ? 'var(--color-error)' : 'none'} color={isSaved ? 'var(--color-error)' : 'var(--color-text-base)'} />
                {isSaved ? 'Vaga Salva' : 'Salvar Vaga'}
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

      {/* Modal de Proposta */}
      <PropostaModal
        isOpen={isPropostaModalOpen}
        onClose={() => setIsPropostaModalOpen(false)}
        onSubmit={handleSubmitProposta}
        vagaTitulo={vaga.titulo}
      />
    </div>
  );
};

export default DetalhesVagaPage;


// ===================================
// Componentes Auxiliares (Definidos localmente ou em 'components/ui/index.tsx')
// Foram adaptados para usar classes globais.
// ===================================

interface InfoItemProps {
    icon: React.ReactNode;
    label: string;
    value: string;
    valueClass?: string;
}

const InfoItem: React.FC<InfoItemProps> = ({ icon, label, value, valueClass }) => (
    <div className="info-item">
        <p className="text-sm text-muted flex-group-item mb-xs">
            {icon}
            <span className="ml-xs font-semibold">{label}</span>
        </p>
        <p className={`title-md ${valueClass || 'text-base'}`}>{value}</p>
    </div>
);
