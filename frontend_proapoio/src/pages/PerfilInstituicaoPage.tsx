import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../services/api'
import { useAuth } from '../contexts/AuthContext'

export default function PerfilInstituicaoPage() {
  const { user, token } = useAuth()
  const navigate = useNavigate()

  const [vagasCount, setVagasCount] = useState(0)
  const [propostasCount, setPropostasCount] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [revealSensitive, setRevealSensitive] = useState(false)
  const liveRef = useRef<HTMLDivElement>(null)

  const isInst = useMemo(() => user?.tipo_usuario === 'instituicao', [user])

  useEffect(() => {
    async function carregar() {
      setLoading(true)
      setError(null)
      try {
        const [vagasRes, propRes] = await Promise.all([
          api.get('/vagas/minhas'),
          api.get('/propostas', { params: { tipo: 'recebidas' } }),
        ])
        const vagasLista = vagasRes.data?.data ?? vagasRes.data
        const propLista = propRes.data?.data ?? propRes.data
        setVagasCount(Array.isArray(vagasLista) ? vagasLista.length : 0)
        setPropostasCount(Array.isArray(propLista) ? propLista.length : 0)
      } catch {
        setError('Erro ao carregar dados.')
      } finally {
        setLoading(false)
      }
    }
    if (token && isInst) carregar()
  }, [token, isInst])

  if (!token) {
    return (
      <main className="p-4 max-w-4xl mx-auto">
        <p>É necessário estar logado para acessar o painel da instituição.</p>
      </main>
    )
  }

  if (!isInst) {
    return (
      <main className="p-4 max-w-4xl mx-auto">
        <p>Este painel é exclusivo para instituições.</p>
      </main>
    )
  }

  if (loading) {
    return (
      <main className="p-4 max-w-5xl mx-auto" aria-busy="true">
        <div className="animate-pulse space-y-4">
          <div className="h-6 bg-zinc-200 rounded w-1/3" />
          <div className="grid grid-cols-2 gap-4">
            <div className="h-24 bg-zinc-200 rounded" />
            <div className="h-24 bg-zinc-200 rounded" />
          </div>
          <div className="h-48 bg-zinc-200 rounded" />
        </div>
      </main>
    )
  }

  function maskCNPJ(cnpj?: string) {
    if (!cnpj) return '—'
    const s = String(cnpj).replace(/\D/g, '')
    if (s.length < 14) return `${s.slice(0, 2)}.***.***/****-**`
    return `${s.slice(0, 2)}.${s.slice(2, 5)}.${s.slice(5, 8)}/${s.slice(8, 12)}-${s.slice(12, 14)}`
  }

  return (
    <main className="p-4 max-w-5xl mx-auto space-y-6" aria-labelledby="titulo-painel-inst">
      <div ref={liveRef} className="sr-only" aria-live="polite" />

      <h1 id="titulo-painel-inst" className="text-2xl font-extrabold">Painel da instituição</h1>

      {/* Métricas */}
      <section aria-label="Métricas" className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <article className="p-4 border rounded bg-white shadow-sm">
          <h2 className="text-sm text-zinc-600">Vagas publicadas</h2>
          <p className="text-3xl font-extrabold">{vagasCount}</p>
          <div className="mt-2 flex gap-3">
            <button onClick={() => navigate('/vagas/nova')} className="text-blue-700 underline text-sm">Nova vaga</button>
            <button onClick={() => navigate('/vagas')} className="text-blue-700 underline text-sm">Gerenciar vagas</button>
          </div>
        </article>
        <article className="p-4 border rounded bg-white shadow-sm">
          <h2 className="text-sm text-zinc-600">Propostas recebidas</h2>
          <p className="text-3xl font-extrabold">{propostasCount}</p>
          <button onClick={() => navigate('/propostas')} className="mt-2 text-blue-700 underline text-sm">Ver propostas</button>
        </article>
      </section>

      {/* Dados institucionais */}
      <section className="p-4 border rounded bg-white shadow-sm" aria-label="Dados da instituição">
        <h2 className="font-semibold mb-2">Dados da instituição</h2>
        <dl className="grid sm:grid-cols-2 gap-y-1">
          <div><dt className="text-sm text-zinc-600">Nome/Razão Social</dt><dd>{user?.razao_social || user?.name || user?.nome || '—'}</dd></div>
          <div><dt className="text-sm text-zinc-600">Nome fantasia</dt><dd>{user?.nome_fantasia || '—'}</dd></div>
          <div>
            <dt className="text-sm text-zinc-600">CNPJ</dt>
            <dd>
              {revealSensitive ? (user?.cnpj || '—') : maskCNPJ(user?.cnpj)}
              <button type="button" className="ml-2 text-xs underline" onClick={() => setRevealSensitive((v) => !v)} aria-pressed={revealSensitive}>
                {revealSensitive ? 'Ocultar' : 'Mostrar'}
              </button>
            </dd>
          </div>
          <div><dt className="text-sm text-zinc-600">INEP</dt><dd>{user?.codigo_inep || '—'}</dd></div>
          <div><dt className="text-sm text-zinc-600">Email</dt><dd>{user?.email || '—'}</dd></div>
        </dl>
      </section>
    </main>
  )
}
