import { useEffect, useMemo, useRef, useState } from 'react'
import api from '../services/api'
import { Link } from 'react-router-dom'

interface EntidadePublica {
  id: number
  nome?: string
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
}

interface Contatos {
  email?: string
  telefone?: string
  // Não exibir CPF por ser altamente sensível
}

export default function MinhasPropostasPage() {
  const [tab, setTab] = useState<'enviadas' | 'recebidas'>('enviadas')
  const [propostas, setPropostas] = useState<Proposta[]>([])
  const [contacts, setContacts] = useState<Record<number, Contatos>>({})
  const [loading, setLoading] = useState<boolean>(false)
  const [error, setError] = useState<string | null>(null)
  const [page, setPage] = useState<number>(1)
  const [hasMore, setHasMore] = useState<boolean>(false)
  const [mutatingId, setMutatingId] = useState<number | null>(null)

  const liveRef = useRef<HTMLDivElement>(null)

  // Cores e rótulos de status consistentes
  const statusBadge = useMemo(
    () => ({
      pendente: { label: 'Pendente', cls: 'bg-yellow-100 text-yellow-800 ring-yellow-300' },
      aceita: { label: 'Aceita', cls: 'bg-green-100 text-green-800 ring-green-300' },
      recusada: { label: 'Recusada', cls: 'bg-orange-100 text-orange-800 ring-orange-300' },
      cancelada: { label: 'Cancelada', cls: 'bg-gray-100 text-gray-800 ring-gray-300' },
    }),
    []
  )

  useEffect(() => {
    let abort = new AbortController()
    async function fetchPropostas(reset = true) {
      setLoading(true)
      setError(null)
      try {
        const response = await api.get('/propostas', {
          params: { tipo: tab, page: reset ? 1 : page },
          signal: abort.signal as any,
        })
        const payload = response.data?.data ?? response.data
        const items: Proposta[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
        const nextHasMore: boolean = !!(payload?.hasMore ?? (items.length >= 10))
        setHasMore(nextHasMore)
        setPropostas((prev) => (reset ? items : [...prev, ...items]))
      } catch (e: any) {
        if (e?.name !== 'CanceledError' && e?.message !== 'canceled') {
          setError('Falha ao carregar propostas.')
          setPropostas([])
        }
      } finally {
        setLoading(false)
      }
    }
    setPage(1)
    setContacts({})
    fetchPropostas(true)
    return () => abort.abort()
  }, [tab])

  async function loadMore() {
    const next = page + 1
    setPage(next)
    try {
      const response = await api.get('/propostas', { params: { tipo: tab, page: next } })
      const payload = response.data?.data ?? response.data
      const items: Proposta[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
      setHasMore(!!(payload?.hasMore ?? (items.length >= 10)))
      setPropostas((prev) => [...prev, ...items])
    } catch {
      setHasMore(false)
    }
  }

  // Ações com otimização e anúncio em aria-live
  async function aceitar(id: number) {
    setMutatingId(id)
    try {
      await api.put(`/propostas/${id}/aceitar`)
      setPropostas((prev) => prev.map((p) => (p.id === id ? { ...p, status: 'aceita' } : p)))
      announce(`Proposta ${id} aceita.`)
      const resp = await api.get(`/propostas/${id}`)
      const c: Contatos | undefined = resp.data?.contatos
      if (c) setContacts((prev) => ({ ...prev, [id]: c }))
    } catch {
      announce(`Não foi possível aceitar a proposta ${id}.`)
    } finally {
      setMutatingId(null)
    }
  }

  async function recusar(id: number) {
    setMutatingId(id)
    try {
      await api.put(`/propostas/${id}/recusar`)
      setPropostas((prev) => prev.map((p) => (p.id === id ? { ...p, status: 'recusada' } : p)))
      announce(`Proposta ${id} recusada.`)
    } catch {
      announce(`Não foi possível recusar a proposta ${id}.`)
    } finally {
      setMutatingId(null)
    }
  }

  async function cancelar(id: number) {
    setMutatingId(id)
    try {
      await api.delete(`/propostas/${id}`)
      setPropostas((prev) => prev.filter((p) => p.id !== id))
      announce(`Proposta ${id} cancelada e removida da lista.`)
    } catch {
      announce(`Não foi possível cancelar a proposta ${id}.`)
    } finally {
      setMutatingId(null)
    }
  }

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg
      // limpa a mensagem depois para não confundir leitores
      setTimeout(() => {
        if (liveRef.current) liveRef.current.textContent = ''
      }, 1500)
    }
  }

  // Acessibilidade das abas (tablist)
  const tabs: Array<{ key: 'enviadas' | 'recebidas'; label: string; desc: string }> = [
    { key: 'enviadas', label: 'Enviadas', desc: 'Propostas enviadas por você' },
    { key: 'recebidas', label: 'Recebidas', desc: 'Propostas que você recebeu' },
  ]

  return (
    <div className="p-4 mx-auto max-w-5xl">
      <div
        ref={liveRef}
        role="status"
        aria-live="polite"
        className="sr-only"
      />

      <header className="mb-4">
        <h1 className="text-2xl font-extrabold">Minhas propostas</h1>
        <p className="text-sm text-zinc-600">Gerencie solicitações e respostas. Contatos só aparecem após aceitação.</p>
      </header>

      {/* Abas acessíveis */}
      <div role="tablist" aria-label="Tipo de propostas" className="inline-flex rounded-lg ring-1 ring-zinc-300 overflow-hidden">
        {tabs.map((t, i) => (
          <button
            key={t.key}
            role="tab"
            aria-selected={tab === t.key}
            aria-controls={`painel-${t.key}`}
            id={`aba-${t.key}`}
            onClick={() => setTab(t.key)}
            onKeyDown={(e) => {
              if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                const next = e.key === 'ArrowRight' ? (i + 1) % tabs.length : (i - 1 + tabs.length) % tabs.length
                setTab(tabs[next].key)
                const el = document.getElementById(`aba-${tabs[next].key}`)
                el?.focus()
              }
            }}
            className={`px-4 py-2 text-sm font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 ${
              tab === t.key ? 'bg-blue-700 text-white' : 'bg-white text-zinc-800'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>
      <p className="sr-only" id="descricao-abas">Use setas esquerda e direita para alternar.</p>

      {/* Painel da aba */}
      <section
        role="tabpanel"
        id={`painel-${tab}`}
        aria-labelledby={`aba-${tab}`}
        className="mt-4"
      >
        {/* Estados de carregamento e erro */}
        {loading && (
          <ul className="space-y-2" aria-busy="true">
            {Array.from({ length: 3 }).map((_, i) => (
              <li key={i} className="animate-pulse border rounded p-3">
                <div className="h-4 bg-zinc-200 rounded w-1/3 mb-2" />
                <div className="h-3 bg-zinc-200 rounded w-1/2 mb-2" />
                <div className="h-3 bg-zinc-200 rounded w-2/3" />
              </li>
            ))}
          </ul>
        )}

        {error && !loading && (
          <div className="rounded border border-red-200 bg-red-50 p-3 text-red-800">
            {error} <button onClick={() => setTab(tab)} className="underline">Tentar novamente</button>
          </div>
        )}

        {!loading && !error && propostas.length === 0 && (
          <div className="rounded border p-6 text-center">
            <p>Não há propostas {tab}.</p>
            {tab === 'enviadas' ? (
              <p className="text-sm text-zinc-600 mt-1">Você pode <Link to="/vagas" className="underline">explorar vagas</Link> e enviar uma proposta.</p>
            ) : (
              <p className="text-sm text-zinc-600 mt-1">Quando alguém enviar uma proposta, ela aparecerá aqui.</p>
            )}
          </div>
        )}

        {!loading && !error && propostas.length > 0 && (
          <ul className="space-y-3" role="list">
            {propostas.map((p) => {
              const badge = statusBadge[p.status]
              const titulo = p.vaga?.titulo || `Proposta #${p.id}`
              const origem = tab === 'enviadas' ? p.instituicao?.nome || 'Instituição' : p.candidato?.nome || 'Candidato'
              return (
                <li key={p.id} className="border rounded p-3 bg-white shadow-sm">
                  <article aria-labelledby={`h-prop-${p.id}`}>
                    <div className="flex items-start justify-between gap-2">
                      <h2 id={`h-prop-${p.id}`} className="font-semibold text-zinc-900">
                        {titulo}
                      </h2>
                      <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ring-1 ${badge.cls}`}>
                        <span className="inline-block w-1.5 h-1.5 rounded-full bg-current" aria-hidden />
                        {badge.label}
                      </span>
                    </div>
                    <p className="text-sm text-zinc-600 mt-1">Origem/destino: {origem}</p>
                    {p.created_at && (<p className="text-xs text-zinc-500 mt-0.5">Criada em {new Date(p.created_at).toLocaleDateString()}</p>)}
                    <p className="mt-2"><span className="font-medium">Mensagem:</span> {p.mensagem}</p>

                    {/* Ações */}
                    <div className="mt-3 flex flex-wrap gap-2" aria-label={`Ações para proposta ${p.id}`}>
                      {p.status === 'pendente' && (
                        <>
                          <button
                            onClick={() => aceitar(p.id)}
                            className="rounded bg-green-700 text-white px-3 py-1.5 text-sm disabled:opacity-60"
                            disabled={mutatingId === p.id}
                          >
                            Aceitar
                          </button>
                          <button
                            onClick={() => recusar(p.id)}
                            className="rounded bg-amber-700 text-white px-3 py-1.5 text-sm disabled:opacity-60"
                            disabled={mutatingId === p.id}
                          >
                            Recusar
                          </button>
                        </>
                      )}
                      <button
                        onClick={() => cancelar(p.id)}
                        className="rounded bg-red-700 text-white px-3 py-1.5 text-sm disabled:opacity-60"
                        disabled={mutatingId === p.id}
                      >
                        Cancelar
                      </button>
                    </div>

                    {/* Contatos apenas quando aceita e carregados. Não mostrar CPF. */}
                    {p.status === 'aceita' && contacts[p.id] && (
                      <div className="mt-3 border-t pt-2" aria-label="Informações de contato após aceitação">
                        <p className="font-semibold">Contatos liberados</p>
                        {contacts[p.id].email && <p>Email: {contacts[p.id].email}</p>}
                        {contacts[p.id].telefone && <p>Telefone: {contacts[p.id].telefone}</p>}
                      </div>
                    )}
                  </article>
                </li>
              )
            })}
          </ul>
        )}

        {!loading && hasMore && (
          <div className="mt-4 flex justify-center">
            <button
              onClick={loadMore}
              className="rounded bg-blue-700 text-white px-4 py-2 text-sm"
            >
              Carregar mais
            </button>
          </div>
        )}
      </section>
    </div>
  )
}
