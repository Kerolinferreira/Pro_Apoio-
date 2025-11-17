import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { PlusCircle, Edit, Pause, Play, XCircle, CheckCircle, Briefcase, MapPin, DollarSign, Calendar, Loader2, Frown } from 'lucide-react';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ConfirmModal from '../components/ConfirmModal';
import { useToast } from '../components/Toast';
import { TIPO_VAGA_OPTIONS, MODALIDADE_VAGA_OPTIONS } from '../constants/options';
import { logger } from '../utils/logger';

// Tipos
interface Vaga {
  id: number;
  titulo: string;
  descricao: string;
  tipo: string;
  modalidade: string;
  remuneracao: number | null;
  cidade: string;
  estado: string;
  status: 'ATIVA' | 'PAUSADA' | 'FECHADA';
  data_criacao: string;
  numero_propostas?: number;
}

// Mapeamento de Status de Vaga para Badge
const vagaStatusMap = {
  ATIVA: { label: 'Ativa', cls: 'badge-green', icon: <CheckCircle size={14} /> },
  PAUSADA: { label: 'Pausada', cls: 'badge-yellow', icon: <Pause size={14} /> },
  FECHADA: { label: 'Fechada', cls: 'badge-gray', icon: <XCircle size={14} /> },
};

const MinhasVagasPage: React.FC = () => {
  const toast = useToast();
  const [vagas, setVagas] = useState<Vaga[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Estados para modal de confirmação
  const [modalState, setModalState] = useState<{
    show: boolean;
    vagaId: number | null;
    action: 'pausar' | 'fechar' | 'excluir' | 'retomar' | null;
    title?: string;
  }>({
    show: false,
    vagaId: null,
    action: null,
  });

  // Buscar vagas da instituição
  const fetchVagas = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get('/vagas/minhas');
      setVagas(response.data);
    } catch (err) {
      logger.error('Erro ao buscar vagas:', err);
      setError('Não foi possível carregar suas vagas. Tente recarregar a página.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchVagas();
  }, []);

  // Ações de vaga
  const handleVagaAction = (vagaId: number, action: 'pausar' | 'fechar' | 'excluir' | 'retomar', title?: string) => {
    setModalState({ show: true, vagaId, action, title });
  };

  const confirmarVagaAction = async () => {
    const { vagaId, action } = modalState;
    if (!vagaId || !action) return;

    setModalState({ show: false, vagaId: null, action: null });

    try {
      if (action === 'excluir') {
        await api.delete(`/vagas/${vagaId}`);
        setVagas(prev => prev.filter(v => v.id !== vagaId));
        toast.success('Vaga excluída com sucesso!');
      } else if (action === 'retomar') {
        // CORREÇÃO P19: Implementar retomada de vaga pausada
        await api.put(`/vagas/${vagaId}/reativar`);
        setVagas(prev => prev.map(v =>
          v.id === vagaId ? { ...v, status: 'ATIVA' } : v
        ));
        toast.success('Vaga reativada com sucesso!');
      } else {
        await api.put(`/vagas/${vagaId}/${action}`);
        setVagas(prev => prev.map(v =>
          v.id === vagaId
            ? { ...v, status: action.toUpperCase() as 'PAUSADA' | 'FECHADA' }
            : v
        ));
        toast.success(`Vaga ${action === 'pausar' ? 'pausada' : 'fechada'} com sucesso!`);
      }
    } catch (e) {
      logger.error(`Erro ao ${action} vaga:`, e);
      toast.error(`Falha ao ${action} a vaga.`);
    }
  };

  const getModalConfig = () => {
    const { action, title } = modalState;

    if (action === 'pausar') {
      return {
        title: 'Pausar Vaga',
        message: `Deseja pausar a vaga "${title}"? Você pode reativá-la depois.`,
        confirmText: 'Sim, pausar',
        type: 'warning' as const,
      };
    }

    // CORREÇÃO P19: Adicionar configuração modal para retomar vaga
    if (action === 'retomar') {
      return {
        title: 'Retomar Vaga',
        message: `Deseja reativar a vaga "${title}"? Ela voltará a ficar visível para candidatos.`,
        confirmText: 'Sim, reativar',
        type: 'success' as const,
      };
    }

    if (action === 'fechar') {
      return {
        title: 'Fechar Vaga',
        message: `Deseja fechar a vaga "${title}"? Esta ação não pode ser desfeita.`,
        confirmText: 'Sim, fechar',
        type: 'danger' as const,
      };
    }

    if (action === 'excluir') {
      return {
        title: 'Excluir Vaga',
        message: `Deseja excluir permanentemente a vaga "${title}"? Esta ação não pode ser desfeita.`,
        confirmText: 'Sim, excluir',
        type: 'danger' as const,
      };
    }

    return {
      title: '',
      message: '',
      confirmText: 'Confirmar',
      type: 'danger' as const,
    };
  };

  // Renderização
  if (loading) {
    return (
      <div className="page-wrapper">
        <Header />
        <main className="container py-xl">
          <div className="text-center py-xl">
            <Loader2 className="icon-spin text-brand-color mb-sm mx-auto" size={48} />
            <p className="text-info">Carregando vagas...</p>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  const modalConfig = getModalConfig();

  return (
    <div className="page-wrapper">
      <Header />
      <main className="container py-xl">
        {/* Cabeçalho */}
        <header className="flex-group-md-row mb-xl" style={{ justifyContent: 'space-between', alignItems: 'center' }}>
          <div>
            <h1 className="heading-primary mb-xs">Minhas Vagas</h1>
            <p className="text-muted">Gerencie todas as vagas publicadas pela sua instituição</p>
          </div>
          <Link to="/vagas/criar" className="btn-primary btn-icon">
            <PlusCircle size={20} />
            Nova Vaga
          </Link>
        </header>

        {/* Mensagem de erro */}
        {error && (
          <div className="alert alert-error mb-lg">
            <p>{error}</p>
          </div>
        )}

        {/* Lista de Vagas */}
        {vagas.length === 0 ? (
          <div className="card text-center py-xl">
            <Frown size={64} className="mx-md mb-md text-muted" />
            <h2 className="title-lg mb-sm">Nenhuma vaga publicada</h2>
            <p className="text-muted mb-lg">
              Sua instituição ainda não publicou nenhuma vaga. Comece criando sua primeira oportunidade!
            </p>
            <Link to="/vagas/criar" className="btn-primary btn-icon">
              <PlusCircle size={20} />
              Criar Primeira Vaga
            </Link>
          </div>
        ) : (
          <div className="space-y-md">
            {vagas.map((vaga) => {
              const status = vagaStatusMap[vaga.status] || vagaStatusMap.FECHADA;

              return (
                <div key={vaga.id} className="card">
                  <div className="flex-group-md-row" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    {/* Informações da Vaga */}
                    <div className="flex-1">
                      <div className="flex-group-item mb-sm">
                        <Link
                          to={`/vagas/${vaga.id}`}
                          className="btn-link-clean heading-secondary"
                        >
                          {vaga.titulo}
                        </Link>
                        <div className={`badge ${status.cls} flex-group-item ml-sm`} style={{ gap: '0.25rem' }}>
                          {status.icon} {status.label}
                        </div>
                      </div>

                      <div className="grid-2-col-lg gap-sm mb-sm">
                        <div className="flex-group-item text-sm text-muted">
                          <Briefcase size={16} />
                          <span>{vaga.tipo} • {vaga.modalidade}</span>
                        </div>

                        <div className="flex-group-item text-sm text-muted">
                          <MapPin size={16} />
                          <span>{vaga.cidade}, {vaga.estado}</span>
                        </div>

                        {vaga.remuneracao && (
                          <div className="flex-group-item text-sm text-muted">
                            <DollarSign size={16} />
                            <span>
                              {new Intl.NumberFormat('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                              }).format(vaga.remuneracao)}
                            </span>
                          </div>
                        )}

                        <div className="flex-group-item text-sm text-muted">
                          <Calendar size={16} />
                          <span>Publicado em {new Date(vaga.data_criacao).toLocaleDateString('pt-BR')}</span>
                        </div>
                      </div>

                      {vaga.numero_propostas !== undefined && (
                        <p className="text-sm text-brand title-md">
                          {vaga.numero_propostas} proposta(s) recebida(s)
                        </p>
                      )}
                    </div>

                    {/* Ações */}
                    <div className="flex-actions-start">
                      <Link
                        to={`/vagas/${vaga.id}/editar`}
                        className="btn-secondary btn-sm btn-icon"
                        aria-label={`Editar vaga ${vaga.titulo}`}
                      >
                        <Edit size={16} />
                        <span className="hidden-sm">Editar</span>
                      </Link>

                      {vaga.status === 'ATIVA' && (
                        <button
                          onClick={() => handleVagaAction(vaga.id, 'pausar', vaga.titulo)}
                          className="btn-warning btn-sm btn-icon"
                          aria-label="Pausar vaga"
                        >
                          <Pause size={16} />
                          <span className="hidden-sm">Pausar</span>
                        </button>
                      )}

                      {/* CORREÇÃO P19: Botão para retomar vaga pausada */}
                      {vaga.status === 'PAUSADA' && (
                        <button
                          onClick={() => handleVagaAction(vaga.id, 'retomar', vaga.titulo)}
                          className="btn-primary btn-sm btn-icon"
                          aria-label="Retomar vaga"
                        >
                          <Play size={16} />
                          <span className="hidden-sm">Retomar</span>
                        </button>
                      )}

                      {vaga.status !== 'FECHADA' && (
                        <button
                          onClick={() => handleVagaAction(vaga.id, 'fechar', vaga.titulo)}
                          className="btn-error btn-sm btn-icon"
                          aria-label="Fechar vaga"
                        >
                          <XCircle size={16} />
                          <span className="hidden-sm">Fechar</span>
                        </button>
                      )}

                      <button
                        onClick={() => handleVagaAction(vaga.id, 'excluir', vaga.titulo)}
                        className="btn-error btn-sm btn-icon"
                        aria-label="Excluir vaga"
                      >
                        <XCircle size={16} />
                        <span className="hidden-sm">Excluir</span>
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </main>
      <Footer />

      {/* Modal de Confirmação */}
      <ConfirmModal
        isOpen={modalState.show}
        onClose={() => setModalState({ show: false, vagaId: null, action: null })}
        onConfirm={confirmarVagaAction}
        title={modalConfig.title}
        message={modalConfig.message}
        confirmText={modalConfig.confirmText}
        cancelText="Cancelar"
        type={modalConfig.type}
      />
    </div>
  );
};

export default MinhasVagasPage;
