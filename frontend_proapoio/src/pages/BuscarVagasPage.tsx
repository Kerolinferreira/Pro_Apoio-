import { useEffect, useMemo, useRef, useState } from 'react'
import api from '../services/api'
import VagaCard from '../components/VagaCard'

interface Vaga {
  id: number
  titulo_vaga: string
  cidade: string
  regime_contratacao: string
  instituicao: { nome_fantasia: string }
}

export default function BuscarVagasPage() {
  const [q, setQ] = useState('')
  const [cidade, setCidade] = useState('')
  const [regime, setRegime] = useState('')
  const [vagas, setVagas] = useState<Vaga[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)

  const liveRef = useRef<HTMLDivElement>(null)

  // debounce dos filtros
  const debounceKey = useMemo(() => `${q}|${cidade}|${regime}`, [q, cidade, regime])

  useEffect(() => {
    setLoading(true)
    setError(null)
    setPage(1)
    const timer = setTimeout(async () => {
      try {
        const resp = await api.get('/vagas', { params: { q, cidade, regime, page: 1 } })
        const payload = resp.data?.data ?? resp.data
        const items: Vaga[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
        setVagas(items)
        setHasMore(!!(payload?.hasMore ?? (items.length >= 10)))
      } catch {
        setError('Falha ao buscar vagas.')
        setVagas([])
      } finally {
        setLoading(false)
      }
    }, 350)
    return () => clearTimeout(timer)
  }, [debounceKey])

  async function loadMore() {
    const next = page + 1
    setPage(next)
    try {
      const resp = await api.get('/vagas', { params: { q, cidade, regime, page: next } })
      const payload = resp.data?.data ?? resp.data
      const items: Vaga[] = Array.isArray(payload?.items) ? payload.items : Array.isArray(payload) ? payload : []
      setVagas((prev) => [...prev, ...items])
      setHasMore(!!(payload?.hasMore ?? (items.length >= 10)))
    } catch {
      setHasMore(false)
    }
  }

  return (
    <main className="p-4 max-w-6xl mx-auto" aria-labelledby="titulo-busca-vagas">
      <div ref={liveRef} className="sr-only" aria-live="polite" />

      <header className="mb-4">
        <h1 id="titulo-busca-vagas" className="text-2xl font-extrabold">Buscar vagas</h1>
        <p className="text-sm text-zinc-600">Pesquise oportunidades por palavra-chave, cidade ou regime de contratação.</p>
      </header>

      {/* Filtros */}
      <form className="grid gap-3 md:grid-cols-3" role="search" aria-label="Filtros de vagas" onSubmit={(e) => e.preventDefault()}>
        <div>
          <label htmlFor="q" className="block text-sm font-medium">Palavra-chave</label>
          <input id="q" type="text" value={q} onChange={(e) => setQ(e.target.value)} className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" placeholder="cargo, área ou instituição" />
        </div>
        <div>
          <label htmlFor="cidade" className="block text-sm font-medium">Cidade</label>
          <input id="cidade" type="text" value={cidade} onChange={(e) => setCidade(e.target.value)} className="mt-1 border p-2 w-full rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700" placeholder="ex.: Recife" />
        </div>
        <div>
          <label htmlFor="regime" className="block text-sm font-medium">Regime</label>
          <select id="regime" value={regime} onChange={(e) => setRegime(e.target.value)} className="mt-1 border p-2 w-full rounded bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
            <option value="">Todos</option>
            <option value="CLT">CLT</option>
            <option value="ESTAGIO">Estágio</option>
            <option value="MEI">MEI</option>
            <option value="TEMPORARIO">Temporário</option>
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
                <div className="h-3 w-1/2 bg-zinc-200 rounded" />
              </li>
            ))}
          </ul>
        )}

        {error && !loading && (
          <div className="rounded border border-red-200 bg-red-50 p-3 text-red-800">{error}</div>
        )}

        {!loading && !error && vagas.length === 0 && (
          <div className="rounded border p-6 text-center">Nenhuma vaga encontrada.</div>
        )}

        {!loading && !error && vagas.length > 0 && (
          <ul className="space-y-3" role="list">
            {vagas.map((v) => (
              <li key={v.id}>
                <VagaCard
                  title={v.titulo_vaga}
                  institution={v.instituicao?.nome_fantasia || ''}
                  city={v.cidade}
                  regime={v.regime_contratacao}
                />
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