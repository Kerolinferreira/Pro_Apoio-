import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { Heart, Send, MapPin, DollarSign, Calendar, Zap, AlertTriangle, Loader2 } from 'lucide-react'; // Ícones para melhor UX

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

  const [vaga, setVaga] = useState<Vaga | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Estados de Ação (devem vir do backend após a requisição da vaga)
  const isCandidato = true; // Simulação: assumimos que o usuário é um candidato logado
  const [isSaved, setIsSaved] = useState(false);
  const [isApplied, setIsApplied] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

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
        setVaga(response.data as Vaga);
        
        // Simulação de verificação de status (Deve ser implementado no BE)
        // setIsSaved(response.data.status_candidato?.salva || false);
        // setIsApplied(response.data.status_candidato?.proposta_enviada || false);

        setLoading(false);
      } catch (err) {
        console.error('Erro ao buscar detalhes da vaga:', err);
        setError('Não foi possível carregar os detalhes desta vaga. Ela pode ter sido removida ou fechada.');
        setLoading(false);
      }
    };
    fetchVaga();
  }, [id]);

  /**
   * @function handleApply
   * @description Lógica para iniciar o processo de candidatura.
   * Deve abrir um modal para preencher a proposta (POST /propostas).
   */
  const handleApply = () => {
    // Ações de validação e abertura de modal
    console.log('Abrir Modal de Proposta para Vaga ID:', id);
    // TODO: Implementar Modal de Envio de Proposta/Formulário (POST /propostas)
    alert('Função Candidatar-se: Implementar Modal de Envio de Proposta.'); // PLACEHOLDER
    // Após o envio bem-sucedido no modal: setIsApplied(true);
  };

  /**
   * @function handleSave
   * @description Lógica para salvar/remover vaga salva (POST/DELETE /candidatos/me/vagas-salvas).
   */
  const handleSave = async () => {
    if (!id || isSaving) return;

    setIsSaving(true);
    try {
        if (isSaved) {
            // DELETE /candidatos/me/vagas-salvas/{id} (Endpoint simulado)
            console.log('Removendo vaga salva:', id);
            // await api.delete(`/candidatos/me/vagas-salvas/${id}`);
        } else {
            // POST /candidatos/me/vagas-salvas (Endpoint simulado)
            console.log('Salvando vaga:', id);
            // await api.post('/candidatos/me/vagas-salvas', { vaga_id: id });
        }
        setIsSaved(prev => !prev);
    } catch (e) {
        console.error('Erro ao salvar/remover vaga:', e);
        // Mostrar feedback de erro para o usuário (via Toast ou Alert)
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