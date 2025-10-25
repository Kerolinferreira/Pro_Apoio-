import { Link } from 'react-router-dom'

type Props = {
  id?: number
  title: string
  institution: string
  city: string
  regime: string
}

/** Card resumido de vaga com acessibilidade NVDA e foco clicável */
export default function VagaCard({ id, title, institution, city, regime }: Props) {
  return (
    <article
      className="border rounded p-3 mb-2 bg-white shadow-sm focus-within:ring-2 focus-within:ring-blue-600"
      tabIndex={0}
      aria-labelledby={`vaga-${id || title.replace(/\\s+/g, '-')}`}
    >
      <h3 id={`vaga-${id || title.replace(/\\s+/g, '-')}`} className="font-semibold text-zinc-900">
        {title}
      </h3>
      <p className="text-sm text-zinc-700">
        {institution} • {city}
      </p>
      <p className="text-sm text-zinc-700">Regime: {regime}</p>
      {id && (
        <Link
          to={`/vagas/${id}`}
          className="mt-2 inline-block text-blue-700 underline text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
        >
          Ver detalhes
        </Link>
      )}
    </article>
  )
}
