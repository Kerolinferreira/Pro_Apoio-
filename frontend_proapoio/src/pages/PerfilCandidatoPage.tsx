import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../services/api'
import { useAuth } from '../contexts/AuthContext'

interface ExperienciaNova {
  idade_aluno: string
  tempo_experiencia: string
  candidatar_mesma_deficiencia: boolean
  comentario: string
}

export default function PerfilCandidatoPage() {
  const { user, token } = useAuth()
  const navigate = useNavigate()

  const [profile, setProfile] = useState<any>(null)
  const [vagasCount, setVagasCount] = useState(0)
  const [propostasCount, setPropostasCount] = useState(0)
  const [experienciasNovas, setExperienciasNovas] = useState<ExperienciaNova[]>([
    { idade_aluno: '', tempo_experiencia: '', candidatar_mesma_deficiencia: false, comentario: '' },
  ])

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const [success, setSuccess] = useState<string>('')
  const [revealSensitive, setRevealSensitive] = useState(false)
  const liveRef = useRef<HTMLDivElement>(null)

  const isCandidato = useMemo(() => user?.tipo_usuario === 'candidato', [user])

  useEffect(() => {
    async function carregarDados() {
      setLoading(true)
      setError(null)
      try {
        const [resp, vagasRes, propRes] = await Promise.all([
          api.get('/profile/me'),
          api.get('/candidatos/me/vagas-salvas'),
          api.get('/propostas', { params: { tipo: 'enviadas' } }),
        ])
        setProfile(resp.data)
        const vagasLista = vagasRes.data?.data ?? vagasRes.data
        setVagasCount(Array.isArray(vagasLista) ? vagasLista.length : 0)
        const propLista = propRes.data?.data ?? propRes.data
        setPropostasCount(Array.isArray(propLista) ? propLista.length : 0)
      } catch (err: any) {
        setError('Não foi possível carregar os dados do perfil.')
      } finally {
        setLoading(false)
      }
    }
    if (token && isCandidato) carregarDados()
  }, [token, isCandidato])

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg
      setTimeout(() => { if (liveRef.current) liveRef.current.textContent = '' }, 1600)
    }
  }

  function handleExperienciaChange(index: number, field: keyof ExperienciaNova, value: any) {
    setExperienciasNovas((prev) => {
      const copia = [...prev]
      copia[index] = { ...copia[index], [field]: value }
      return copia
    })
  }

  function adicionarExperiencia() {
    setExperienciasNovas((prev) => [
      ...prev,
      { idade_aluno: '', tempo_experiencia: '', candidatar_mesma_deficiencia: false, comentario: '' },
    ])
  }

  function removerExperiencia(i: number) {
    setExperienciasNovas((prev) => prev.filter((_, idx) => idx !== i))
  }

  function validarExperiencias(): string | null {
    if (experienciasNovas.length === 0) return 'Informe ao menos uma experiência.'
    for (let i = 0; i < experienciasNovas.length; i++) {
      const e = experienciasNovas[i]
      const idade = e.idade_aluno ? Number(e.idade_aluno) : NaN
      if (!e.tempo_experiencia.trim()) return `Experiência ${i + 1}: tempo de experiência é obrigatório.`
      if (e.idade_aluno && (Number.isNaN(idade) || idade < 0 || idade > 120)) return `Experiência ${i + 1}: idade do aluno inválida.`
      if (e.comentario.length > 1000) return `Experiência ${i + 1}: comentário muito longo.`
    }
    return null
  }

  async function salvarExperiencias() {
    const erro = validarExperiencias()
    if (erro) {
      setSuccess('')
      setError(erro)
      announce(erro)
      return
    }
    setSaving(true)
    setError('')
    setSuccess('')
    try {
      const payload = experienciasNovas.map((exp) => ({
        idade_aluno: exp.idade_aluno ? Number(exp.idade_aluno) : null,
        tempo_experiencia: exp.tempo_experiencia || null,
        candidatar_mesma_deficiencia: !!exp.candidatar_mesma_deficiencia,
        comentario: exp.comentario || null,
      }))
      await api.post('/profile/experiencia-pro', { experiencias: payload })
      setExperienciasNovas([
        { idade_aluno: '', tempo_experiencia: '', candidatar_mesma_deficiencia: false, comentario: '' },
      ])
      const updated = await api.get('/profile/me')
      setProfile(updated.data)
      const ok = 'Experiências salvas com sucesso.'
      setSuccess(ok)
      announce(ok)
    } catch {
      const msg = 'Erro ao salvar experiências. Tente novamente.'
      setError(msg)
      announce(msg)
    } finally {
      setSaving(false)
    }
  }

  if (!token) {
    return (
      <main className="p-4 max-w-3xl mx-auto">
        <p>É necessário estar logado para acessar o painel do candidato.</p>
      </main>
    )
  }

  if (!isCandidato) {
    return (
      <main className="p-4 max-w-3xl mx-auto">
        <p>Este painel é exclusivo para candidatos.</p>
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

  return (
    <main className="p-4 max-w-5xl mx-auto space-y-6" aria-labelledby="titulo-painel">
      <div ref={liveRef} className="sr-only" aria-live="polite" />

      <h1 id="titulo-painel" className="text-2xl font-extrabold">Painel do candidato</h1>

      {/* Métricas */}
      <section aria-label="Métricas" className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <article className="p-4 border rounded bg-white shadow-sm">
          <h2 className="text-sm text-zinc-600">Vagas salvas</h2>
          <p className="text-3xl font-extrabold">{vagasCount}</p>
          <button onClick={() => navigate('/vagas-salvas')} className="mt-2 text-blue-700 underline text-sm">Ver vagas</button>
        </article>
        <article className="p-4 border rounded bg-white shadow-sm">
          <h2 className="text-sm text-zinc-600">Propostas enviadas</h2>
          <p className="text-3xl font-extrabold">{propostasCount}</p>
          <button onClick={() => navigate('/propostas')} className="mt-2 text-blue-700 underline text-sm">Ver propostas</button>
        </article>
      </section>

      {/* Perfil básico */}
      <section className="p-4 border rounded bg-white shadow-sm" aria-label="Dados pessoais">
        <h2 className="font-semibold mb-2">Dados pessoais</h2>
        <dl className="grid sm:grid-cols-2 gap-y-1">
          <div><dt className="text-sm text-zinc-600">Nome</dt><dd>{profile?.nome_completo || user?.name || '—'}</dd></div>
          <div><dt className="text-sm text-zinc-600">Escolaridade</dt><dd>{profile?.escolaridade || '—'}</dd></div>
          <div>
            <dt className="text-sm text-zinc-600">CPF</dt>
            <dd>
              {revealSensitive ? (profile?.cpf || '—') : (profile?.cpf ? `${String(profile.cpf).slice(0,3)}***.***-**` : '—')}
              <button type="button" className="ml-2 text-xs underline" onClick={() => setRevealSensitive((v) => !v)} aria-pressed={revealSensitive}>
                {revealSensitive ? 'Ocultar' : 'Mostrar'}
              </button>
            </dd>
          </div>
          <div>
            <dt className="text-sm text-zinc-600">Telefone</dt>
            <dd>
              {revealSensitive ? (profile?.telefone || '—') : (profile?.telefone ? `${String(profile.telefone).slice(0,4)}***-***` : '—')}
            </dd>
          </div>
          <div><dt className="text-sm text-zinc-600">Curso</dt><dd>{profile?.nome_curso || '—'}</dd></div>
          <div><dt className="text-sm text-zinc-600">Instituição de ensino</dt><dd>{profile?.nome_instituicao_ensino || '—'}</dd></div>
        </dl>
      </section>

      {/* Experiências novas */}
      <section className="p-4 border rounded bg-white shadow-sm" aria-labelledby="h-exp">
        <h2 id="h-exp" className="font-semibold mb-2">Adicionar experiências profissionais</h2>
        <p className="text-sm text-zinc-600 mb-3">Informe pelo menos uma experiência. Campos marcados como obrigatórios devem ser preenchidos.</p>

        {experienciasNovas.map((exp, index) => (
          <fieldset key={index} className="border p-3 mb-3 rounded space-y-2">
            <legend className="text-sm font-medium">Experiência {index + 1}</legend>
            <div>
              <label htmlFor={`idade-${index}`} className="block">Idade do aluno (opcional)</label>
              <input
                id={`idade-${index}`}
                type="number"
                min={0}
                max={120}
                value={exp.idade_aluno}
                onChange={(e) => handleExperienciaChange(index, 'idade_aluno', e.target.value)}
                className="border p-2 w-full rounded"
              />
            </div>
            <div>
              <label htmlFor={`tempo-${index}`} className="block">Tempo de experiência <span className="text-red-600">*</span></label>
              <input
                id={`tempo-${index}`}
                type="text"
                required
                placeholder="ex.: 6 meses, 2 anos"
                value={exp.tempo_experiencia}
                onChange={(e) => handleExperienciaChange(index, 'tempo_experiencia', e.target.value)}
                className="border p-2 w-full rounded"
              />
            </div>
            <div className="flex items-center gap-2">
              <input
                id={`mesma-def-${index}`}
                type="checkbox"
                checked={exp.candidatar_mesma_deficiencia}
                onChange={(e) => handleExperienciaChange(index, 'candidatar_mesma_deficiencia', e.target.checked)}
              />
              <label htmlFor={`mesma-def-${index}`}>Aceitaria trabalhar com a mesma deficiência?</label>
            </div>
            <div>
              <label htmlFor={`coment-${index}`} className="block">Comentário (opcional)</label>
              <textarea
                id={`coment-${index}`}
                value={exp.comentario}
                onChange={(e) => handleExperienciaChange(index, 'comentario', e.target.value)}
                className="border p-2 w-full rounded"
                rows={3}
                maxLength={1000}
              />
            </div>

            {experienciasNovas.length > 1 && (
              <button
                type="button"
                onClick={() => removerExperiencia(index)}
                className="bg-zinc-200 px-3 py-1 rounded text-sm"
                aria-label={`Remover experiência ${index + 1}`}
              >
                Remover
              </button>
            )}
          </fieldset>
        ))}

        <div className="flex items-center gap-2">
          <button type="button" onClick={adicionarExperiencia} className="bg-zinc-200 px-3 py-1 rounded text-sm">+ Adicionar outra experiência</button>
          <button type="button" onClick={salvarExperiencias} disabled={saving} className="bg-green-700 text-white px-4 py-2 rounded disabled:opacity-60" aria-busy={saving}>{saving ? 'Salvando…' : 'Salvar experiências'}</button>
        </div>

        {error && (
          <div className="mt-3 rounded border border-red-200 bg-red-50 p-2 text-red-800" role="alert">{error}</div>
        )}
        {success && (
          <div className="mt-3 rounded border border-green-200 bg-green-50 p-2 text-green-800" role="status">{success}</div>
        )}
      </section>
    </main>
  )
}
