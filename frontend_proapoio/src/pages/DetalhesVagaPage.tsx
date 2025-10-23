import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import api from '../services/api'

interface Vaga {
  id: number
  titulo_vaga: string
  descricao: string
  cidade: string
  regime_contratacao: string
  requisitos?: string
  instituicao: { nome_fantasia: string }
}

export default function DetalhesVagaPage() {
  const { id } = useParams()
  const [vaga, setVaga] = useState<Vaga | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    async function fetchVaga() {
      try {
        const resp = await api.get(`/vagas/${id}`)
        setVaga(resp.data.data || resp.data)
      } catch {
        setError('Falha ao carregar detalhes da vaga.')
      } finally {
        setLoading(false)
      }
    }
    fetchVaga()
  }, [id])

  if (loading) return <p className="p-4">Carregando vaga…</p>
  if (error) return <p className="p-4 text-red-600">{error}</p>
  if (!vaga) return <p className="p-4">Vaga não encontrada.</p>

  return (
    <main className="max-w-3xl mx-auto p-4" aria-labelledby="titulo-vaga">
      <h1 id="titulo-vaga" className="text-2xl font-extrabold mb-2">
        {vaga.titulo_vaga}
      </h1>
      <p className="text-zinc-700 mb-1">
        <span className="font-medium">Instituição:</span> {vaga.instituicao?.nome_fantasia}
      </p>
      <p className="text-zinc-700 mb-1">
        <span className="font-medium">Cidade:</span> {vaga.cidade}
      </p>
      <p className="text-zinc-700 mb-3">
        <span className="font-medium">Regime de contratação:</span> {vaga.regime_contratacao}
      </p>
      <section className="mb-3">
        <h2 className="font-semibold text-lg mb-1">Descrição</h2>
        <p>{vaga.descricao}</p>
      </section>
      {vaga.requisitos && (
        <section>
          <h2 className="font-semibold text-lg mb-1">Requisitos</h2>
          <p>{vaga.requisitos}</p>
        </section>
      )}
    </main>
  )
}
