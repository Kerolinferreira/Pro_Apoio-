import { Link } from 'react-router-dom'

export default function ParaCandidatosPage() {
  return (
    <main
      id="conteudo"
      role="main"
      className="mx-auto max-w-7xl px-4 py-10"
      aria-labelledby="titulo-para-candidatos"
    >
      <div id="live-para-candidatos" className="sr-only" aria-live="polite" aria-atomic="true" />

      <header className="text-center mb-8">
        <h1 id="titulo-para-candidatos" className="text-3xl sm:text-4xl font-extrabold">
          Para candidatos
        </h1>
        <p className="mt-3 text-lg text-zinc-700 max-w-3xl mx-auto">
          Encontre vagas alinhadas à sua experiência e envie propostas com segurança.
        </p>
      </header>

      {/* Chamadas principais */}
      <section
        className="flex flex-col sm:flex-row items-center justify-center gap-3"
        aria-labelledby="cta-candidatos"
      >
        <h2 id="cta-candidatos" className="sr-only">
          Ações principais para candidatos
        </h2>

        <Link
          to="/register/candidato"
          className="w-full sm:w-auto rounded-lg bg-blue-700 text-white px-6 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 text-center"
          aria-label="Criar conta de candidato"
        >
          Criar conta de candidato
        </Link>

        <Link
          to="/vagas"
          className="w-full sm:w-auto rounded-lg bg-white text-blue-700 px-6 py-3 font-semibold shadow ring-1 ring-inset ring-blue-700/30 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 text-center"
          aria-label="Explorar vagas disponíveis"
        >
          Explorar vagas
        </Link>
      </section>

      {/* Fluxo do candidato */}
      <section className="mt-12" aria-labelledby="fluxo-candidato">
        <h2 id="fluxo-candidato" className="text-2xl font-bold text-center mb-8">
          Seu fluxo em quatro passos
        </h2>
        <ol className="grid gap-6 md:grid-cols-4 list-decimal [counter-reset:step]">
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">1. Perfil</h3>
            <p>Cadastre-se e descreva suas experiências relevantes.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">2. Busque</h3>
            <p>Use filtros por localização e tipo de necessidade.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">3. Salve e proponha</h3>
            <p>Salve vagas e envie propostas formais para iniciar a conversa.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">4. Confirme</h3>
            <p>Ao aceitar, os contatos são liberados e o processo avança.</p>
          </li>
        </ol>
      </section>

      {/* Recursos */}
      <section className="mt-12" aria-labelledby="recursos-candidato">
        <h2 id="recursos-candidato" className="text-2xl font-bold text-center mb-8">
          Recursos para você
        </h2>
        <div className="grid gap-6 md:grid-cols-3">
          <article
            className="p-5 border rounded-lg bg-white shadow-sm"
            aria-labelledby="recurso-salvos"
          >
            <h3 id="recurso-salvos" className="font-semibold text-lg">
              Vagas salvas
            </h3>
            <p className="mt-2">Organize oportunidades e receba atualizações.</p>
            <div className="mt-3">
              <Link
                to="/vagas-salvas"
                className="underline text-blue-700 hover:no-underline"
                aria-label="Ver vagas salvas"
              >
                Ver vagas salvas
              </Link>
            </div>
          </article>

          <article
            className="p-5 border rounded-lg bg-white shadow-sm"
            aria-labelledby="recurso-propostas"
          >
            <h3 id="recurso-propostas" className="font-semibold text-lg">
              Propostas
            </h3>
            <p className="mt-2">Envie, acompanhe status e gerencie respostas.</p>
            <div className="mt-3">
              <Link
                to="/propostas"
                className="underline text-blue-700 hover:no-underline"
                aria-label="Acessar minhas propostas"
              >
                Acessar minhas propostas
              </Link>
            </div>
          </article>

          <article
            className="p-5 border rounded-lg bg-white shadow-sm"
            aria-labelledby="recurso-perfil"
          >
            <h3 id="recurso-perfil" className="font-semibold text-lg">
              Perfil do candidato
            </h3>
            <p className="mt-2">Mantenha suas experiências atualizadas.</p>
            <div className="mt-3">
              <Link
                to="/perfil/candidato"
                className="underline text-blue-700 hover:no-underline"
                aria-label="Editar meu perfil de candidato"
              >
                Editar meu perfil
              </Link>
            </div>
          </article>
        </div>
      </section>

      {/* FAQ */}
      <section className="mt-12" aria-labelledby="faq-candidato">
        <h2 id="faq-candidato" className="text-2xl font-bold text-center mb-8">
          Perguntas frequentes
        </h2>
        <div className="grid gap-4">
          <details className="p-4 border rounded-lg bg-white shadow-sm">
            <summary className="font-semibold cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
              Meus dados ficam públicos?
            </summary>
            <p className="mt-2">
              Não. O contato só é liberado após a aceitação de uma proposta.
            </p>
          </details>

          <details className="p-4 border rounded-lg bg-white shadow-sm">
            <summary className="font-semibold cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
              Posso desfazer uma remoção de vaga salva?
            </summary>
            <p className="mt-2">Sim. Use o botão desfazer após remover uma vaga salva.</p>
          </details>

          <details className="p-4 border rounded-lg bg-white shadow-sm">
            <summary className="font-semibold cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700">
              Como aumento minha compatibilidade?
            </summary>
            <p className="mt-2">
              Descreva experiências objetivamente e mantenha o perfil atualizado.
            </p>
          </details>
        </div>
      </section>

      {/* Atalhos finais */}
      <nav className="mt-12 text-center" aria-label="Atalhos finais">
        <Link
          to="/register/candidato"
          className="underline text-blue-700 hover:no-underline"
          aria-label="Criar conta de candidato"
        >
          Criar conta
        </Link>
        <span className="mx-2" aria-hidden="true">
          |
        </span>
        <Link
          to="/vagas"
          className="underline text-blue-700 hover:no-underline"
          aria-label="Explorar vagas disponíveis"
        >
          Explorar vagas
        </Link>
      </nav>
    </main>
  )
}
