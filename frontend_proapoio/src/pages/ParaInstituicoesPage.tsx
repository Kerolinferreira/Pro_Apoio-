import { Link } from 'react-router-dom'

export default function ParaInstituicoesPage() {
  return (
    <main
      id="conteudo"
      role="main"
      className="mx-auto max-w-7xl px-4 py-10"
      aria-labelledby="titulo-para-instituicoes"
    >
      <div id="live-para-instituicoes" className="sr-only" aria-live="polite" aria-atomic="true" />

      <header className="text-center mb-8">
        <h1 id="titulo-para-instituicoes" className="text-3xl sm:text-4xl font-extrabold">
          Para instituições
        </h1>
        <p className="mt-3 text-lg text-zinc-700 max-w-3xl mx-auto">
          Encontre agentes de apoio com experiência comprovada e gerencie propostas com segurança.
        </p>
      </header>

      {/* Chamadas principais */}
      <section
        className="flex flex-col sm:flex-row items-center justify-center gap-3"
        aria-labelledby="cta-instituicoes"
      >
        <h2 id="cta-instituicoes" className="sr-only">
          Ações principais para instituições
        </h2>

        <Link
          to="/register/instituicao"
          className="w-full sm:w-auto rounded-lg bg-blue-700 text-white px-6 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 text-center"
          aria-label="Criar conta institucional"
        >
          Criar conta de instituição
        </Link>

        <Link
          to="/candidatos"
          className="w-full sm:w-auto rounded-lg bg-white text-blue-700 px-6 py-3 font-semibold shadow ring-1 ring-inset ring-blue-700/30 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 text-center"
          aria-label="Ir para busca de candidatos"
        >
          Buscar candidatos
        </Link>
      </section>

      {/* Fluxo da instituição */}
      <section className="mt-12" aria-labelledby="fluxo-instituicao">
        <h2 id="fluxo-instituicao" className="text-2xl font-bold text-center mb-8">
          Seu fluxo em quatro passos
        </h2>
        <ol className="grid gap-6 md:grid-cols-4 list-decimal [counter-reset:step]">
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">1. Perfil institucional</h3>
            <p>Cadastre sua instituição e defina necessidades e preferências.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">2. Busque candidatos</h3>
            <p>Use filtros por cidade e tipo de experiência para encontrar perfis compatíveis.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">3. Envie propostas</h3>
            <p>Inicie a conversa com propostas padronizadas. Contatos só são liberados após aceitação.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">4. Acompanhe</h3>
            <p>Gerencie status das candidaturas e avance com segurança após o aceite.</p>
          </li>
        </ol>
      </section>

      {/* Recursos */}
      <section className="mt-12" aria-labelledby="recursos-instituicao">
        <h2 id="recursos-instituicao" className="text-2xl font-bold text-center mb-8">
          Recursos para sua instituição
        </h2>
        <div className="grid gap-6 md:grid-cols-3">
          <article
            className="p-5 border rounded-lg bg-white shadow-sm"
            aria-labelledby="recurso-perfil-inst"
          >
            <h3 id="recurso-perfil-inst" className="font-semibold text-lg">
              Perfil institucional
            </h3>
            <p className="mt-2">
              Centralize informações e requisitos de apoio em um só lugar.
            </p>
            <div className="mt-3">
              <Link
                to="/perfil/instituicao"
                className="underline text-blue-700 hover:no-underline"
                aria-label="Editar perfil institucional"
              >
                Editar perfil institucional
              </Link>
            </div>
          </article>

          <article
            className="p-5 border rounded-lg bg-white shadow-sm"
            aria-labelledby="recurso-busca-cand"
          >
            <h3 id="recurso-busca-cand" className="font-semibold text-lg">
              Busca de candidatos
            </h3>
            <p className="mt-2">
              Filtre por localização e experiência para rapidez e precisão.
            </p>
            <div className="mt-3">
              <Link
                to="/candidatos"
                className="underline text-blue-700 hover:no-underline"
                aria-label="Abrir busca de candidatos"
              >
                Abrir busca de candidatos
              </Link>
            </div>
          </article>

          <article
            className="p-5 border rounded-lg bg-white shadow-sm"
            aria-labelledby="recurso-propostas"
          >
            <h3 id="recurso-propostas" className="font-semibold text-lg">
              Propostas e status
            </h3>
            <p className="mt-2">
              Envie propostas e acompanhe respostas em um só painel.
            </p>
            <div className="mt-3">
              <Link
                to="/propostas"
                className="underline text-blue-700 hover:no-underline"
                aria-label="Gerenciar propostas enviadas"
              >
                Gerenciar propostas
              </Link>
            </div>
          </article>
        </div>
      </section>

      {/* FAQ */}
      <section className="mt-12" aria-labelledby="faq-instituicao">
        <h2 id="faq-instituicao" className="text-2xl font-bold text-center mb-8">
          Perguntas frequentes
        </h2>
        <div className="grid gap-4">
          <details className="p-4 border rounded-lg bg-white shadow-sm">
            <summary className="font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
              Os contatos dos candidatos ficam visíveis?
            </summary>
            <p className="mt-2">
              Não. Os contatos só são liberados após a aceitação de uma proposta.
            </p>
          </details>

          <details className="p-4 border rounded-lg bg-white shadow-sm">
            <summary className="font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
              Posso ver o perfil público do candidato?
            </summary>
            <p className="mt-2">
              Sim. Use a busca e abra o perfil público quando disponível.
            </p>
          </details>

          <details className="p-4 border rounded-lg bg-white shadow-sm">
            <summary className="font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
              Como melhorar a compatibilidade?
            </summary>
            <p className="mt-2">
              Detalhe claramente as necessidades dos alunos no perfil institucional.
            </p>
          </details>
        </div>
      </section>

      {/* Atalhos finais */}
      <nav className="mt-12 text-center" aria-label="Atalhos finais">
        <Link
          to="/register/instituicao"
          className="underline text-blue-700 hover:no-underline"
          aria-label="Ir para criar conta institucional"
        >
          Criar conta
        </Link>
        <span className="mx-2" aria-hidden="true">
          |
        </span>
        <Link
          to="/candidatos"
          className="underline text-blue-700 hover:no-underline"
          aria-label="Ir para busca de candidatos"
        >
          Buscar candidatos
        </Link>
      </nav>
    </main>
  )
}
