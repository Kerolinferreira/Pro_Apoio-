import { useEffect, useMemo, useRef, useState } from 'react'
import api from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import { Link } from 'react-router-dom'

interface Experiencia {
  id: number
  tipo_deficiencia: string
  descricao?: string
}

interface Candidato {
  id: number
  nome: string
  cidade: string
  escolaridade: string
  nome_curso?: string
  experiencias?: Experiencia[]
}

interface TipoDef {
  id: string
  nome: string
}

export default function BuscarCandidatosPage() {
  const { token, user } = useAuth()

  // Filtros
  const [q, setQ] = useState('')
  const [cidade, setCidade] = useState('')
  const [escolaridade, setEscolaridade] = useState('')
  const [tipoDef, setTipoDef] = useState('')

  // Dados
  const [candidatos, setCandidatos] = useState<Candidato[]>([])
  const [tiposDef, setTiposDef] = useState<TipoDef[]>([])

  // Estados UI
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)
  const [openProposalFor, setOpenProposalFor] = useState<number | null>(null)
  const [proposalMsg, setProposalMsg] = useState('')
  const [sendingProposal, setSendingProposal] = useState(false)
  const [successMap, setSuccessMap] = useState<Record<number, boolean>>({})

  const liveRef = useRef<HTMLDivElement>(null)

  const isInst = !!token && user?.tipo_usuario === 'instituicao'

  // Debounce dos filtros
  const debounceKey = useMemo(() => `${q}|${cidade}|${escolaridade}|${tipoDef}`, [q, cidade, escolaridade, tipoDef])

  useEffect(() => {
    if (!isInst) return
    let cleanup = false

    async function loadTipos() {
      try {
        const r = await api.get('/tipos-deficiencia')
        const arr = r.data?.data ?? r.data
        if (!cleanup) setTiposDef(Array.isArray(arr) ? arr : [])
      } catch {
        // ignora
      }
    }
    loadTipos()

    return () => {
      cleanup = true
    }
  }, [isInst])

  useEffect(() => {
    if (!isInst) return
    setLoading(true)
    setError(null)
    setPage(1)
    const timer = setTimeout(async () => {
      try {
        const resp = await api.get('/candidatos', {
          params: { q, cidade, escolaridade, tipo_deficiencia: tipoDef, page: 1 },
        })
        const payload = resp.data?.data ?? resp.data
        const items: Candidato[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
        setCandidatos(items)
        setHasMore(!!(payload?.hasMore ?? (items.length >= 10)))
      } catch {
        setCandidatos([])
        setError('Falha ao buscar candidatos.')
      } finally {
        setLoading(false)
      }
    }, 350)
    return () => clearTimeout(timer)
  }, [debounceKey, isInst])

  async function loadMore() {
    const next = page + 1
    setPage(next)
    try {
      const resp = await api.get('/candidatos', { params: { q, cidade, escolaridade, tipo_deficiencia: tipoDef, page: next } })
      const payload = resp.data?.data ?? resp.data
      const items: Candidato[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
      setCandidatos((prev) => [...prev, ...items])
      setHasMore(!!(payload?.hasMore ?? (items.length >= 10)))
    } catch {
      setHasMore(false)
    }
  }

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg
      setTimeout(() => { if (liveRef.current) liveRef.current.textContent = '' }, 1500)
    }
  }

  async function enviarProposta(candidatoId: number) {
    if (!proposalMsg.trim()) {
      announce('Escreva uma mensagem para a proposta.')
      return
    }
    setSendingProposal(true)
    try {
      await api.post('/propostas', { candidato_id: candidatoId, mensagem: proposalMsg })
      setSuccessMap((m) => ({ ...m, [candidatoId]: true }))
      setProposalMsg('')
      announce(`Proposta enviada para o candidato ${candidatoId}.`)
    } catch {
      announce('Não foi possível enviar a proposta.')
    } finally {
      setSendingProposal(false)
    }
  }

  if (!isInst) {
    return (
      <main className="p-4 max-w-3xl mx-auto">
        <h1 className="text-2xl font-extrabold mb-2">Buscar candidatos</h1>
        <p>É necessário estar logado como instituição para acessar a lista de candidatos.</p>
        <p className="mt-2 text-sm text-zinc-600">Você pode <Link to="/login" className="underline">entrar</Link> ou <Link to="/register" className="underline">criar uma conta</Link>.</p>
      </main>
    )
  }

  return (
    <main className="p-4 max-w-6xl mx-auto" aria-labelledby="titulo-busca">
      <div ref={liveRef} className="sr-only" aria-live="polite" />

      <header className="mb-4">
        <h1 id="titulo-busca" className="text-2xl font-extrabold">Buscar candidatos</h1>
        <p className="text-sm text-zinc-600">Filtre por palavra‑chave, cidade, escolaridade e tipo de deficiência. Contatos não são exibidos.</p>
      </header>

      {/* Filtros */}
      <form className="grid gap-3 md:grid-cols-4" role="search" aria-label="Filtros de candidatos" onSubmit={(e) => e.preventDefault()}>
        <div>
          <label htmlFor="q" className="block text-sm font-medium">Palavra‑chave</label>
          <input id="q" type="text" value={q} onChange={(e) => setQ(e.target.value)} className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" placeholder="nome, curso, habilidade" />
        </div>
        <div>
          <label htmlFor="cidade" className="block text-sm font-medium">Cidade</label>
          <input id="cidade" type="text" value={cidade} onChange={(e) => setCidade(e.target.value)} className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" placeholder="ex.: Recife" />
        </div>
        <div>
          <label htmlFor="escolaridade" className="block text-sm font-medium">Escolaridade</label>
          <input id="escolaridade" type="text" value={escolaridade} onChange={(e) => setEscolaridade(e.target.value)} className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" placeholder="ex.: Superior incompleto" />
        </div>
        <div>
          <label htmlFor="tipoDef" className="block text-sm font-medium">Tipo de deficiência</label>
          <select id="tipoDef" value={tipoDef} onChange={(e) => setTipoDef(e.target.value)} className="mt-1 border p-2 w-full rounded bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
            <option value="">Todas</option>
            {tiposDef.map((t) => (
              <option key={t.id} value={t.id}>{t.nome}</option>
            ))}
          </select>
        </div>
      </form>

      {/* Lista */}
      <section className="mt-4" aria-label="Resultados">
        {loading && (
          <ul className="space-y-2" aria-busy="true">
            {Array.from({ length: 4 }).map((_, i) => (
              <li key={i} className="border rounded p-3 animate-pulse">
                <div className="h-4 w-1/3 bg-zinc-200 rounded mb-2" />
                <div className="h-3 w-1/4 bg-zinc-200 rounded mb-2" />
                <div className="h-3 w-1/2 bg-zinc-200 rounded" />
              </li>
            ))}
          </ul>
        )}

        {error && !loading && (
          <div className="rounded border border-red-200 bg-red-50 p-3 text-red-800">{error}</div>
        )}

        {!loading && !error && candidatos.length === 0 && (
          <div className="rounded border p-6 text-center">Nenhum candidato encontrado.</div>
        )}

        {!loading && !error && candidatos.length > 0 && (
          <ul className="space-y-3" role="list">
            {candidatos.map((c) => (
              <li key={c.id} className="border p-3 rounded bg-white shadow-sm">
                <article aria-labelledby={`cand-${c.id}`}>
                  <header className="flex items-start justify-between gap-2">
                    <h2 id={`cand-${c.id}`} className="font-semibold text-zinc-900">{c.nome}</h2>
                    <span className="text-xs text-zinc-600">{c.cidade}</span>
                  </header>
                  <p className="text-sm text-zinc-700 mt-1">{c.escolaridade}{c.nome_curso ? ` — ${c.nome_curso}` : ''}</p>

                  {/* Resumo de experiências por tipo de deficiência */}
                  {Array.isArray(c.experiencias) && c.experiencias.length > 0 && (
                    <div className="mt-2">
                      <p className="text-sm font-medium">Experiências relacionadas</p>
                      <ul className="mt-1 flex flex-wrap gap-1">
                        {c.experiencias.slice(0, 4).map((e) => (
                          <li key={e.id} className="text-xs inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 ring-1 ring-zinc-200">
                            {e.tipo_deficiencia}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}

                  {/* Ação: Fazer proposta inline */}
                  <div className="mt-3">
                    {successMap[c.id] ? (
                      <div className="rounded border border-green-200 bg-green-50 p-2 text-green-800 text-sm">Proposta enviada. Aguarde a resposta.</div>
                    ) : openProposalFor === c.id ? (
                      <div aria-label={`Formulário de proposta para ${c.nome}`}> 
                        <label htmlFor={`msg-${c.id}`} className="block text-sm font-medium">Mensagem</label>
                        <textarea id={`msg-${c.id}`} className="mt-1 w-full border rounded p-2" rows={3} value={proposalMsg} onChange={(e) => setProposalMsg(e.target.value)} placeholder="Apresente a oportunidade e requisitos essenciais." />
                        <div className="mt-2 flex gap-2">
                          <button disabled={sendingProposal} onClick={() => enviarProposta(c.id)} className="rounded bg-blue-700 text-white px-3 py-1.5 text-sm disabled:opacity-60">Enviar proposta</button>
                          <button type="button" onClick={() => { setOpenProposalFor(null); setProposalMsg('') }} className="rounded bg-zinc-200 px-3 py-1.5 text-sm">Cancelar</button>
                        </div>
                      </div>
                    ) : (
                      <button onClick={() => setOpenProposalFor(c.id)} className="rounded bg-blue-700 text-white px-3 py-1.5 text-sm">Fazer proposta</button>
                    )}
                  </div>
                </article>
              </li>
            ))}
          </ul>
        )}

        {!loading && hasMore && (
          <div className="mt-4 flex justify-center">
            <button onClick={loadMore} className="rounded bg-blue-700 text-white px-4 py-2 text-sm">Carregar mais</button>
          </div>
        )}
      </section>
    </main>
  )
}
