import { Link } from 'react-router-dom'

/**
 * Nota: Este arquivo implementa a Landing Page (página inicial) para visitantes não autenticados.
 *
 * Desvio Corrigido:
 * O cabeçalho (Header) agora inclui os links de navegação "Como Funciona", "Para Candidatos"
 * e "Para Instituições", conforme a documentação, para o visitante não logado.
 * * Desvio Corrigido:
 * Os CTAs da Seção Principal (Hero) agora usam os textos segmentados por público, 
 * conforme especificado na documentação: "Sou Candidato, Quero Ajudar" e 
 * "Sou Instituição, Preciso de Apoio", e direcionam para o registro correto.
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
            <Link
              to="/register"
              className="rounded-md bg-white text-blue-700 px-3 py-2 font-medium shadow hover:bg-white/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
              aria-label="Cadastrar nova conta"
            >
              Cadastrar
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
              O ProApoio aproxima instituições de ensino, alunos com deficiência e agentes de apoio. 
              Publique oportunidades acessíveis, encontre perfis compatíveis e finalize propostas com segurança.
            </p>

            {/* INÍCIO DA CORREÇÃO: CTAs da Seção Principal (Hero) com textos segmentados e rotas de registro específicas */}
            <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
              <Link
                to="/register/candidato"
                className="w-full sm:w-auto rounded-lg bg-blue-700 text-white px-6 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                aria-label="Sou Candidato, Quero Ajudar"
              >
                Sou Candidato, Quero Ajudar
              </Link>
              <Link
                to="/register/instituicao"
                className="w-full sm:w-auto rounded-lg bg-white text-blue-700 px-6 py-3 font-semibold shadow ring-1 ring-inset ring-blue-700/30 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                aria-label="Sou Instituição, Preciso de Apoio"
              >
                Sou Instituição, Preciso de Apoio
              </Link>
            </div>
            {/* FIM DA CORREÇÃO */}

            {/* Indicadores de confiança */}
            <ul className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm" aria-label="Indicadores de confiança">
              <li className="px-3 py-2 rounded bg-white shadow ring-1 ring-zinc-200">Acessibilidade por padrão</li>
              <li className="px-3 py-2 rounded bg-white shadow ring-1 ring-zinc-200">Dados sensíveis protegidos</li>
              <li className="px-3 py-2 rounded bg-white shadow ring-1 ring-zinc-200">Compatibilidade prioriza experiência</li>
            </ul>
          </div>
        </section>

        {/* Como funciona */}
        <section className="py-16 px-4">
          <div className="mx-auto max-w-7xl">
            <h2 className="text-2xl font-bold text-center">Como funciona</h2>
            <ol className="mt-8 grid gap-6 md:grid-cols-3 list-decimal [counter-reset:step]">
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Cadastre-se</h3>
                <p>Escolha perfil de instituição ou agente de apoio. Complete dados essenciais e preferências de acessibilidade.</p>
              </li>
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Explore</h3>
                <p>Instituições publicam oportunidades. Agentes salvam vagas e demonstram interesse.</p>
              </li>
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Conecte-se</h3>
                <p>Envie propostas, receba candidaturas e avance pelo fluxo seguro sem expor contatos antes da aceitação.</p>
              </li>
            </ol>
          </div>
        </section>

        {/* Vantagens */}
        <section className="bg-gray-50 py-16 px-4">
          <div className="mx-auto max-w-7xl">
            <h2 className="text-2xl font-bold text-center">Vantagens</h2>
            <ul className="mt-8 grid gap-6 md:grid-cols-3" role="list">
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Inclusão</h3>
                <p>Arquitetura acessível desde o início. Suporte a leitores de tela e navegação por teclado.</p>
              </li>
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Transparência</h3>
                <p>Perfis claros. Dados de contato ocultos até a proposta ser aceita.</p>
              </li>
              <li className="p-4 border rounded-lg bg-white shadow-sm">
                <h3 className="font-semibold mb-2">Agilidade</h3>
                <p>Cadastro simplificado com integrações como CEP e CNPJ para pré-preenchimento.</p>
              </li>
            </ul>
          </div>
        </section>

        {/* Depoimentos */}
        <section className="py-16 px-4">
          <div className="mx-auto max-w-5xl">
            <h2 className="text-2xl font-bold text-center">Depoimentos</h2>
            <div className="mt-8 grid md:grid-cols-2 gap-6">
              <blockquote className="p-4 border rounded-lg bg-white shadow-sm">
                <p className="italic">“Consegui minha primeira oportunidade de estágio pelo ProApoio. Rápido e acessível.”</p>
                <cite className="mt-2 block font-semibold not-italic">Ana — estudante de TI</cite>
              </blockquote>
              <blockquote className="p-4 border rounded-lg bg-white shadow-sm">
                <p className="italic">“Publicamos vagas e recebemos candidatos qualificados. Fluxo seguro e simples.”</p>
                <cite className="mt-2 block font-semibold not-italic">José — coordenação institucional</cite>
              </blockquote>
            </div>
          </div>
        </section>

        {/* CTA final */}
        <section aria-label="Chamada final" className="bg-blue-700 text-white">
          <div className="mx-auto max-w-7xl px-4 py-12 text-center">
            <h2 className="text-2xl font-extrabold">Pronto para começar?</h2>
            <div className="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
              <Link
                to="/register"
                className="w-full sm:w-auto rounded-lg bg-white text-blue-700 px-6 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
              >
                Criar conta gratuita
              </Link>
              <Link
                to="/login"
                className="w-full sm:w-auto rounded-lg bg-blue-600 text-white px-6 py-3 font-semibold shadow ring-1 ring-inset ring-white/30 hover:bg-blue-600/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
              >
                Acessar minha conta
              </Link>
            </div>
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer role="contentinfo" className="bg-blue-900 text-white">
        <div className="mx-auto max-w-7xl px-4 py-8">
          <p className="text-center">© {year} ProApoio. Todos os direitos reservados.</p>
          <nav aria-label="Rodapé" className="mt-4 flex items-center justify-center gap-4 text-sm">
            <Link to="/acessibilidade" className="underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Acessibilidade</Link>
            <Link to="/privacidade" className="underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Privacidade</Link>
            <Link to="/contato" className="underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Contato</Link>
          </nav>
        </div>
      </footer>
    </div>
  )
}
