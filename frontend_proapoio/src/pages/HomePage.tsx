import { Link } from 'react-router-dom'

/**
 * Landing Page para visitantes não autenticados.
 */
export default function HomePage() {
  const year = new Date().getFullYear()

  return (
    <div className="min-h-screen flex flex-col bg-white text-zinc-900">
      {/* Skip link */}
      <a
        href="#conteudo"
        className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-yellow-300 focus:px-3 focus:py-2"
      >
        Pular para o conteúdo principal
      </a>

      {/* Header */}
      <header role="banner" className="bg-blue-700 text-white">
        <div className="mx-auto max-w-7xl px-4 py-4 flex items-center justify-between">
          <Link
            to="/"
            aria-label="Página inicial ProApoio"
            className="inline-flex items-center gap-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
          >
            <span aria-hidden className="inline-block w-2 h-6 bg-white/80 rounded" />
            <span className="text-xl font-extrabold tracking-tight">ProApoio</span>
          </Link>

          <nav aria-label="Navegação principal" className="flex items-center gap-2 sm:gap-4">
            <Link
              to="/como-funciona"
              className="hidden sm:block underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
              Como Funciona
            </Link>
            <Link
              to="/para-candidatos"
              className="hidden sm:block underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
              Para Candidatos
            </Link>
            <Link
              to="/para-instituicoes"
              className="hidden sm:block underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
              Para Instituições
            </Link>

            <Link
              to="/login"
              className="underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
              Entrar
            </Link>
            {/* CTA principal: Cadastre-se */}
            <Link
              to="/cadastro"
              className="rounded-md bg-white text-blue-700 px-3 py-2 font-medium shadow hover:bg-white/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
              aria-label="Cadastre-se"
            >
              Cadastre-se
            </Link>
          </nav>
        </div>
      </header>

      {/* Main */}
      <main id="conteudo" className="flex-1">
        {/* Hero */}
        <section className="bg-gray-100">
          <div className="mx-auto max-w-7xl px-4 py-16 text-center">
            <h1 className="text-3xl sm:text-4xl font-extrabold leading-tight">
              Conecte instituições e agentes de apoio para estudantes com deficiência
            </h1>
            <p className="mt-4 mx-auto max-w-2xl text-lg">
              O ProApoio aproxima instituições de ensino com alunos com deficiência a agentes de apoio.
              Publique oportunidades, encontre perfis compatíveis e finalize propostas com segurança.
            </p>

            {/* CTAs duplos conforme documentação */}
            <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
              <Link
                to="/cadastro?tipo=candidato"
                className="w-full sm:w-auto rounded-lg bg-blue-700 text-white px-6 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                aria-label="Sou Candidato, Quero Ajudar"
              >
                Sou Candidato, Quero Ajudar
              </Link>
              <Link
                to="/cadastro?tipo=instituicao"
                className="w-full sm:w-auto rounded-lg bg-white text-blue-700 px-6 py-3 font-semibold shadow ring-1 ring-inset ring-blue-700/30 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                aria-label="Sou Instituição, Preciso de Apoio"
              >
                Sou Instituição, Preciso de Apoio
              </Link>
            </div>

          </div>
        </section>

        {/* Como funciona */}
        <section className="py-16 px-4">
          <div className="mx-auto max-w-7xl">
            <h2 className="text-2xl font-bold text-center">Como funciona</h2>
            <ol className="mt-8 grid gap-6 md:grid-cols-3 list-decimal [counter-reset:step]">
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Cadastre-se</h3>
                <p>Escolha perfil de instituição ou agente de apoio. Complete dados essenciais, além de informações sobre sua experiência ou as necessidades de seus alunos.</p>
              </li>
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Encontre</h3>
                <p>Instituições publicam oportunidades. Candidatos salvam vagas e demonstram interesse.</p>
              </li>
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Conecte-se</h3>
                <p>Envie propostas, receba candidaturas e avance pelo fluxo seguro sem expor contatos antes da aceitação.</p>
              </li>
            </ol>
          </div>
        </section>

      </main>

      {/* Footer */}
      <footer className="bg-zinc-100 border-t">
        <div className="mx-auto max-w-7xl px-4 py-6 text-sm flex items-center justify-between">
          <p>&copy; {year} ProApoio</p>
          <nav aria-label="Rodapé" className="flex gap-4">
            <Link to="/sobre" className="underline-offset-4 hover:underline">Sobre Nós</Link>
            <Link to="/contato" className="underline-offset-4 hover:underline">Contato</Link>
            <Link to="/termos" className="underline-offset-4 hover:underline">Termos de Uso</Link>
            <Link to="/privacidade" className="underline-offset-4 hover:underline">Política de Privacidade</Link>
          </nav>
        </div>
      </footer>
    </div>
  )
}
