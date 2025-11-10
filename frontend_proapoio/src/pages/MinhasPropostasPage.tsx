import React from 'react';
import { useEffect, useMemo, useRef, useState } from 'react'
import api from '../services/api'
import { Link } from 'react-router-dom'
import { CheckCircle, XCircle, Send, Clock, User, Briefcase, Mail, Phone, Loader2, AlertTriangle, ArrowLeft, Zap } from 'lucide-react';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ConfirmModal from '../components/ConfirmModal';
import { useToast } from '../components/Toast';
import { parseApiError } from '../utils/errorHandler';
import { logger } from '../utils/logger';

// ===================================
// TIPOS DE DADOS
// ===================================

interface EntidadePublica {
  id: number
  nome?: string // nome_fantasia (Instituição) ou nome_completo (Candidato)
  // Adicionado URL de perfil para Link
  perfilUrl?: string 
}

interface VagaResumo {
  id: number
  titulo?: string
  instituicao?: EntidadePublica
}

interface Proposta {
  id: number
  mensagem: string
  status: 'pendente' | 'aceita' | 'recusada' | 'cancelada'
  vaga?: VagaResumo
  candidato?: EntidadePublica
  instituicao?: EntidadePublica
  created_at?: string
  visualizada?: boolean
}

interface Contatos {
  email?: string
  telefone?: string
  nomeCompleto?: string
}

// ===================================
// MAPAS E UTILITÁRIOS
// ===================================

// Mapeamento de Status para Badge (Refatorado para usar classes globais)
const statusMap = {
  aceita: { label: 'Aceita', cls: 'badge-success', icon: <CheckCircle size={14} /> },
  recusada: { label: 'Recusada', cls: 'badge-error', icon: <XCircle size={14} /> },
  cancelada: { label: 'Cancelada', cls: 'badge-gray', icon: <XCircle size={14} /> },
  pendente: { label: 'Em Análise', cls: 'badge-yellow', icon: <Clock size={14} /> },
  visualizada: { label: 'Visualizada', cls: 'badge-info', icon: <Clock size={14} /> },
  nova: { label: 'Nova', cls: 'badge-brand', icon: <Zap size={14} /> }, // Novo badge para propostas novas
  enviada: { label: 'Enviada', cls: 'badge-gray', icon: <Send size={14} /> }
} as const;

/**
 * @function getPropostaStatus
 * @description Retorna o badge correto baseado no status e na aba (contexto de visualização).
 */
const getPropostaStatus = (p: Proposta, tab: 'enviadas' | 'recebidas') => {
  if (p.status !== 'pendente') {
    return statusMap[p.status];
  }
  
  if (tab === 'enviadas') {
    // Candidato: Pendente -> Enviada ou Visualizada
    return p.visualizada ? statusMap.visualizada : statusMap.enviada;
  }
  
  // Instituição: Pendente -> Nova ou Visualizada
  return p.visualizada ? statusMap.visualizada : statusMap.nova;
};

/**
 * @function formatRelativa
 * @description Formata a data de forma relativa.
 */
function formatRelativa(iso?: string) {
  if (!iso) return null;
  const d = new Date(iso);
  const diff = Date.now() - d.getTime();
  const dias = Math.floor(diff / 86400000);
  if (dias <= 0) return 'hoje';
  if (dias === 1) return 'há 1 dia';
  return `há ${dias} dias`;
}

// ===================================
// CARD DA PROPOSTA
// ===================================

interface PropostaCardProps {
    proposta: Proposta;
    tab: 'enviadas' | 'recebidas';
    contatos: Contatos | undefined;
    mutatingId: number | null;
    aceitar: (id: number) => void;
    recusar: (id: number) => void;
    cancelar: (id: number) => void;
}

const PropostaCard: React.FC<PropostaCardProps> = React.memo(({
    proposta: p,
    tab,
    contatos,
    mutatingId,
    aceitar,
    recusar,
    cancelar,
}) => {
    const badge = getPropostaStatus(p, tab);
    const titulo = p.vaga?.titulo || `Proposta #${p.id}`;
    
    // Define a entidade (quem é o alvo ou remetente)
    const entidade: EntidadePublica | undefined = tab === 'enviadas' ? p.instituicao : p.candidato;
    const labelEntidade = tab === 'enviadas' ? 'Destino' : 'Remetente';
    
    // Define o URL de perfil (se existir)
    const perfilUrl = tab === 'enviadas' 
        ? `/instituicoes/${entidade?.id}` 
        : `/candidatos/${entidade?.id}`;
    
    const isMutating = mutatingId === p.id;

    return (
        <li className="card-simple card-proposta" aria-busy={isMutating}>
            <article aria-labelledby={`h-prop-${p.id}`}>
                {/* Cabeçalho e Status */}
                <div className="flex-group-md-row" style={{ justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    
                    <div className="flex-group" style={{ gap: '0.25rem', flexGrow: 1 }}>
                        {/* Título da Vaga */}
                        <h2 id={`h-prop-${p.id}`} className="title-md text-base-color">
                            {titulo}
                        </h2>
                        
                        {/* Remetente/Destino */}
                        <p className="text-sm text-muted flex-group-item">
                            {tab === 'recebidas' ? <User size={16} className="mr-xs" /> : <Briefcase size={16} className="mr-xs" />}
                            {labelEntidade}: 
                            {entidade ? (
                                <Link to={perfilUrl} className="btn-link ml-xs" style={{ whiteSpace: 'nowrap' }}>
                                    {entidade.nome || 'Perfil'}
                                </Link>
                            ) : (
                                <span className="ml-xs">Não Informado</span>
                            )}
                        </p>
                    </div>

                    {/* Badge de Status */}
                    <div className={`badge ${badge.cls} flex-group-item`} style={{ gap: '0.25rem' }}>
                        {badge.icon}
                        {badge.label}
                    </div>
                </div>
                
                {/* Data e Link da Vaga */}
                <div className="section-divider mt-md pt-sm">
                    <p className="text-sm text-muted">
                        {tab === 'recebidas' ? 'Recebida' : 'Enviada'} {formatRelativa(p.created_at)}
                    </p>
                    {p.vaga?.id && (
                        <p className="text-sm mt-xs">
                            <Link to={`/vagas/${p.vaga.id}`} className="btn-link text-sm" aria-label={`Ver detalhes da vaga ${titulo}`}>
                                Ver Detalhes da Vaga
                            </Link>
                        </p>
                    )}
                </div>

                {/* Mensagem */}
                <div className="mt-md">
                    <p className="text-sm text-muted"><span className="font-semibold">Mensagem:</span></p>
                    <p className="text-base mt-xs text-base-color mensagem-proposta">{p.mensagem}</p>
                </div>

                {/* Ações (Aceitar / Recusar / Cancelar) */}
                {p.status === 'pendente' && (
                    <div className="mt-md pt-sm section-divider flex-actions-start">
                        {/* AÇÕES DE RECEPTOR: Recebidas e Pendente */}
                        {tab === 'recebidas' && (
                            <>
                                <button
                                    onClick={() => aceitar(p.id)}
                                    className="btn-primary btn-sm"
                                    disabled={isMutating}
                                >
                                    {isMutating ? <Loader2 size={16} className="icon-spin mr-xs" /> : <CheckCircle size={16} className="mr-xs" />}
                                    {isMutating ? 'Processando...' : 'Aceitar'}
                                </button>
                                <button
                                    onClick={() => recusar(p.id)}
                                    className="btn-secondary btn-sm btn-warning"
                                    disabled={isMutating}
                                >
                                    Recusar
                                </button>
                            </>
                        )}

                        {/* AÇÃO DE INICIADOR: Enviadas e Pendente */}
                        {tab === 'enviadas' && (
                            <button
                                onClick={() => cancelar(p.id)}
                                className="btn-secondary btn-sm btn-error"
                                disabled={isMutating}
                            >
                                {isMutating ? <Loader2 size={16} className="icon-spin mr-xs" /> : <XCircle size={16} className="mr-xs" />}
                                Cancelar Proposta
                            </button>
                        )}
                    </div>
                )}
                
                {/* Contatos (Apenas se Aceita) */}
                {p.status === 'aceita' && contatos && (
                    <div className="mt-md pt-sm section-divider info-box-contact">
                        <p className="title-md text-success-color mb-xs">Contatos Liberados!</p>
                        <p className="text-sm text-muted mb-xs">
                            Parabéns! Suas informações de contato foram compartilhadas.
                            Por favor, finalize a comunicação para a contratação fora da plataforma.
                        </p>
                        <ul className="space-y-xs text-base-color">
                            {contatos.nomeCompleto && <li className="flex-group-item"><User size={16} className="mr-xs" /> Nome: <span className="font-semibold ml-xs">{contatos.nomeCompleto}</span></li>}
                            {contatos.email && <li className="flex-group-item"><Mail size={16} className="mr-xs" /> Email: <span className="font-semibold ml-xs">{contatos.email}</span></li>}
                            {contatos.telefone && <li className="flex-group-item"><Phone size={16} className="mr-xs" /> Telefone: <span className="font-semibold ml-xs">{contatos.telefone}</span></li>}
                        </ul>
                    </div>
                )}
            </article>
        </li>
    );
});


// ===================================
// PÁGINA PRINCIPAL
// ===================================

const MinhasPropostasPage: React.FC = () => {
  // Aba padrão: Recebidas (Instituição) ou Enviadas (Candidato).
  // Se houver AuthContext, o valor inicial deve ser derivado do tipo de usuário.
  // Como não temos o AuthContext aqui, mantemos 'recebidas' (para simular Instituição).
  const [tab, setTab] = useState<'enviadas' | 'recebidas'>('recebidas') 
  const [propostas, setPropostas] = useState<Proposta[]>([])
  const [contacts, setContacts] = useState<Record<number, Contatos>>({})
  const [loading, setLoading] = useState<boolean>(false)
  const [error, setError] = useState<string | null>(null)
  const [page, setPage] = useState<number>(1)
  const [hasMore, setHasMore] = useState<boolean>(false)
  const [mutatingId, setMutatingId] = useState<number | null>(null)

  // Estados para modais de confirmação
  const [modalState, setModalState] = useState<{ show: boolean; type: 'aceitar' | 'recusar' | 'cancelar' | null; id: number | null }>({
    show: false,
    type: null,
    id: null
  })
  const toast = useToast()

  const liveRef = useRef<HTMLDivElement>(null)

  // Acessibilidade das abas (tablist)
  const tabs: Array<{ key: 'enviadas' | 'recebidas'; label: string; desc: string }> = useMemo(() => [
    { key: 'recebidas', label: 'Propostas Recebidas', desc: 'Propostas enviadas a você' },
    { key: 'enviadas', label: 'Propostas Enviadas', desc: 'Propostas que você iniciou' },
  ], []);


  // --- EFEITO DE BUSCA ---
  useEffect(() => {
    let abort = new AbortController()
    async function fetchPropostas(reset = true) {
      setLoading(true)
      setError(null)
      try {
        // GET /propostas?tipo={tab} [cite: Documentação final.docx]
        const response = await api.get('/propostas', {
          params: { tipo: tab, page: reset ? 1 : page },
          signal: abort.signal as any,
        })
        const payload = response.data?.data ?? response.data
        const items: Proposta[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
        const nextHasMore: boolean = !!(payload?.hasMore ?? (items.length >= 10))
        setHasMore(nextHasMore)
        setPropostas((prev) => (reset ? items : [...prev, ...items]))
        setLoading(false)
        
        // Announce results
        if (liveRef.current && items.length > 0 && reset) {
            liveRef.current.textContent = `${items.length} propostas carregadas na aba ${tab === 'recebidas' ? 'Recebidas' : 'Enviadas'}.`
        }

      } catch (e: any) {
        if (e?.name !== 'CanceledError' && e?.message !== 'canceled') {
          logger.error('Falha ao carregar propostas:', e);

          // Usa o helper para parsear erros da API
          const { generalMessage } = parseApiError(e);
          const status = e?.response?.status;
          let message = generalMessage;

          if (status === 401) {
            message = 'Sessão expirada. Por favor, faça login novamente.';
          } else if (status === 403) {
            message = 'Você não tem permissão para visualizar essas propostas.';
          } else if (!e.response) {
            message = 'Não foi possível conectar ao servidor. Verifique sua conexão com a internet.';
          }

          setError(message);
          setPropostas([]);
          setLoading(false);
          if (liveRef.current) liveRef.current.textContent = `Erro: ${message}`;
        }
      } 
    }
    
    // Reset e busca ao mudar a aba
    setPage(1)
    setContacts({})
    fetchPropostas(true)
    return () => abort.abort()
  }, [tab])

  // --- PAGINAÇÃO ---
  async function loadMore() {
    const next = page + 1
    setPage(next)
    setLoading(true); // Controla o loading do botão
    try {
      const response = await api.get('/propostas', { params: { tipo: tab, page: next } })
      const payload = response.data?.data ?? response.data
      const items: Proposta[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
      setHasMore(!!(payload?.hasMore ?? (items.length >= 10)))
      setPropostas((prev) => [...prev, ...items])
      if (liveRef.current) liveRef.current.textContent = `${items.length} propostas adicionais carregadas.`;
    } catch (err) {
      logger.error('Erro ao carregar mais propostas:', err);
      toast.error('Não foi possível carregar mais propostas. Tente novamente.');
      setHasMore(false)
    } finally {
        setLoading(false);
    }
  }

  // --- AÇÕES ---

  async function aceitar(id: number) {
    setModalState({ show: true, type: 'aceitar', id })
  }

  async function confirmarAceitar() {
    const id = modalState.id
    if (!id) return

    setMutatingId(id)
    setModalState({ show: false, type: null, id: null })

    try {
      // PUT /propostas/{id}/aceitar [cite: Documentação final.docx]
      await api.put(`/propostas/${id}/aceitar`)
      setPropostas((prev) => prev.map((p) => (p.id === id ? { ...p, status: 'aceita' } : p)))
      toast.success('Proposta aceita com sucesso! Contatos disponíveis.')
      announce(`Proposta ${id} aceita. Contatos carregados.`)

      // Busca e armazena os contatos após aceite
      // GET /propostas/{id} (doc permite pegar detalhes/contatos) [cite: Documentação final.docx]
      const resp = await api.get(`/propostas/${id}`)
      const c: Contatos | undefined = resp.data?.contatos // Supondo que contatos venham em 'contatos'
      if (c) setContacts((prev) => ({ ...prev, [id]: c }))
    } catch (err) {
      logger.error('Erro ao aceitar proposta:', err);

      // Usa o helper para parsear erros da API
      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;

      if (status === 404) {
        toast.error('Proposta não encontrada. Ela pode ter sido removida.');
      } else if (status === 403) {
        toast.error('Você não tem permissão para aceitar esta proposta.');
      } else if (status === 409) {
        toast.error('Esta proposta já foi processada anteriormente.');
      } else {
        toast.error(generalMessage || 'Não foi possível aceitar a proposta. Tente novamente.');
      }

      announce(`Erro ao aceitar proposta ${id}.`)
    } finally {
      setMutatingId(null)
    }
  }

  async function recusar(id: number) {
    setModalState({ show: true, type: 'recusar', id })
  }

  async function confirmarRecusar() {
    const id = modalState.id
    if (!id) return

    setMutatingId(id)
    setModalState({ show: false, type: null, id: null })

    try {
      // PUT /propostas/{id}/recusar [cite: Documentação final.docx]
      await api.put(`/propostas/${id}/recusar`)

      // Se for "Recebidas", remove da lista (arquiva)
      setPropostas((prev) =>
        tab === 'recebidas' ? prev.filter((p) => p.id !== id) : prev.map((p) => (p.id === id ? { ...p, status: 'recusada' } : p))
      )
      toast.success('Proposta recusada.')
      announce(`Proposta ${id} recusada.`)
    } catch (err) {
      logger.error('Erro ao recusar proposta:', err);

      // Usa o helper para parsear erros da API
      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;

      if (status === 404) {
        toast.error('Proposta não encontrada. Ela pode ter sido removida.');
      } else if (status === 403) {
        toast.error('Você não tem permissão para recusar esta proposta.');
      } else if (status === 409) {
        toast.error('Esta proposta já foi processada anteriormente.');
      } else {
        toast.error(generalMessage || 'Não foi possível recusar a proposta. Tente novamente.');
      }

      announce(`Erro ao recusar proposta ${id}.`)
    } finally {
      setMutatingId(null)
    }
  }

  async function cancelar(id: number) {
    setModalState({ show: true, type: 'cancelar', id })
  }

  async function confirmarCancelar() {
    const id = modalState.id
    if (!id) return

    setMutatingId(id)
    setModalState({ show: false, type: null, id: null })

    try {
      // DELETE /propostas/{id} [cite: Documentação final.docx]
      await api.delete(`/propostas/${id}`)
      setPropostas((prev) => prev.filter((p) => p.id !== id)) // Remove da lista
      toast.success('Proposta cancelada e removida.')
      announce(`Proposta ${id} cancelada e removida.`)
    } catch (err) {
      logger.error('Erro ao cancelar proposta:', err);

      // Usa o helper para parsear erros da API
      const { generalMessage } = parseApiError(err);
      const status = (err as any)?.response?.status;

      if (status === 404) {
        toast.error('Proposta não encontrada. Ela pode ter sido removida.');
      } else if (status === 403) {
        toast.error('Você não tem permissão para cancelar esta proposta.');
      } else if (status === 409) {
        toast.error('Não é possível cancelar uma proposta que já foi aceita ou recusada.');
      } else {
        toast.error(generalMessage || 'Não foi possível cancelar a proposta. Tente novamente.');
      }
      announce(`Não foi possível cancelar a proposta ${id}.`)
    } finally {
      setMutatingId(null)
    }
  }

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg
    }
  }
  
  // --- RENDERIZAÇÃO ---
  
  const isInitialLoading = loading && page === 1;

  return (
    <div className="page-wrapper">
      <Header />
      <main className="container py-lg">
        
        {/* Título e Live Region */}
        <header className="mb-md">
            <h1 className="heading-secondary">Minhas Propostas</h1>
            <p className="text-sm text-muted">Contatos só aparecem após a proposta ser aceita.</p>
        </header>

        {/* Live region para feedback de acessibilidade */}
        <div ref={liveRef} role="status" aria-live="polite" className="sr-only" />

        {/* Abas acessíveis */}
        <div role="tablist" aria-label="Tipo de propostas" className="tabs-container mb-lg">
          {tabs.map((t, i) => (
            <button
              key={t.key}
              role="tab"
              aria-selected={tab === t.key}
              aria-controls={`painel-${t.key}`}
              id={`aba-${t.key}`}
              onClick={() => setTab(t.key)}
              // Navegação por setas para acessibilidade (opcional)
              onKeyDown={(e) => {
                  if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                      const next = e.key === 'ArrowRight' ? (i + 1) % tabs.length : (i - 1 + tabs.length) % tabs.length
                      const nextTab = tabs[next]
                      if (nextTab) {
                          setTab(nextTab.key)
                          const el = document.getElementById(`aba-${nextTab.key}`)
                          el?.focus()
                      }
                  }
              }}
              className={tab === t.key ? 'tab-button tab-active' : 'tab-button'}
            >
              {t.label}
            </button>
          ))}
        </div>

        {/* Painel da aba */}
        <section
          role="tabpanel"
          id={`painel-${tab}`}
          aria-labelledby={`aba-${tab}`}
          className="mt-4"
        >
          
          {/* Esqueleto de Carregamento Inicial */}
          {isInitialLoading && (
            <ul className="space-y-md" aria-busy="true">
              {Array.from({ length: 3 }).map((_, i) => (
                <li key={i} className="placeholder-card">
                    <div className="placeholder-line placeholder-animate" style={{ width: '40%' }} />
                    <div className="placeholder-line placeholder-animate" style={{ width: '70%', height: '0.75rem', marginTop: 'var(--spacing-xs)' }} />
                    <div className="placeholder-line placeholder-animate" style={{ width: '50%', height: '0.75rem', marginTop: 'var(--spacing-xs)' }} />
                </li>
              ))}
            </ul>
          )}

          {/* Estado de Erro */}
          {error && !isInitialLoading && (
            <div className="alert alert-error">
              <AlertTriangle size={20} className="mr-sm" />
              {error} <button onClick={() => setTab(tab)} className="btn-link">Tentar novamente</button>
            </div>
          )}

          {/* Estado Vazio */}
          {!loading && !error && propostas.length === 0 && (
            <div className="alert alert-warning text-center">
              <p className="font-semibold">Não há propostas {tab === 'enviadas' ? 'enviadas' : 'recebidas'}.</p>
              {tab === 'enviadas' ? (
                <p className="text-sm text-muted mt-xs">Você pode <Link to="/vagas" className="btn-link text-sm">explorar vagas</Link> e enviar uma proposta.</p>
              ) : (
                <p className="text-sm text-muted mt-xs">Quando alguém enviar uma proposta, ela aparecerá aqui.</p>
              )}
            </div>
          )}

          {/* Lista de Propostas */}
          {!isInitialLoading && !error && propostas.length > 0 && (
            <ul className="space-y-md" role="list">
              {propostas.map((p) => (
                <PropostaCard
                    key={p.id}
                    proposta={p}
                    tab={tab}
                    contatos={contacts[p.id]}
                    mutatingId={mutatingId}
                    aceitar={aceitar}
                    recusar={recusar}
                    cancelar={cancelar}
                />
              ))}
            </ul>
          )}

          {/* Carregar Mais (Paginação) */}
          {!isInitialLoading && hasMore && (
            <div className="mt-lg flex-actions-center">
              <button
                onClick={loadMore}
                className="btn-primary"
                disabled={loading && page > 1}
              >
                {loading && page > 1 ? <Loader2 size={16} className="icon-spin mr-xs" /> : null}
                {loading && page > 1 ? 'Carregando…' : 'Carregar mais propostas'}
              </button>
            </div>
          )}
        </section>
      </main>
      <Footer />

      {/* Modais de Confirmação */}
      <ConfirmModal
        isOpen={modalState.show && modalState.type === 'aceitar'}
        onClose={() => setModalState({ show: false, type: null, id: null })}
        onConfirm={confirmarAceitar}
        title="Aceitar Proposta"
        message="Você tem certeza que deseja aceitar esta proposta? Suas informações de contato serão compartilhadas com o candidato."
        confirmText="Sim, aceitar"
        cancelText="Cancelar"
        type="success"
        isLoading={mutatingId === modalState.id}
      />

      <ConfirmModal
        isOpen={modalState.show && modalState.type === 'recusar'}
        onClose={() => setModalState({ show: false, type: null, id: null })}
        onConfirm={confirmarRecusar}
        title="Recusar Proposta"
        message="Confirmar recusa desta proposta? Esta ação não pode ser desfeita."
        confirmText="Sim, recusar"
        cancelText="Cancelar"
        type="warning"
        isLoading={mutatingId === modalState.id}
      />

      <ConfirmModal
        isOpen={modalState.show && modalState.type === 'cancelar'}
        onClose={() => setModalState({ show: false, type: null, id: null })}
        onConfirm={confirmarCancelar}
        title="Cancelar Proposta"
        message="Confirmar cancelamento da proposta? Ela será removida permanentemente."
        confirmText="Sim, cancelar"
        cancelText="Não cancelar"
        type="danger"
        isLoading={mutatingId === modalState.id}
      />
    </div>
  )
}
export default MinhasPropostasPage;