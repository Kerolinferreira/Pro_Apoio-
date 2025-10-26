import { Link } from 'react-router-dom'

export default function ComoFuncionaPage() {
  return (
    <main id="conteudo" className="mx-auto max-w-7xl px-4 py-10" aria-labelledby="titulo-como-funciona">
      {/* Região viva para leitores de tela, caso precise anunciar algo no futuro */}
      <div className="sr-only" aria-live="polite" aria-atomic="true" />

      <header className="text-center">
        <h1 id="titulo-como-funciona" className="text-3xl sm:text-4xl font-extrabold">
          Como funciona o ProApoio
        </h1>
        <p className="mt-3 text-lg text-zinc-700">
          Conecte instituições e agentes de apoio com foco em segurança, compatibilidade e propostas formais.
        </p>
      </header>

      {/* CTA duplo segmentado */}
      <section className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3" aria-label="Chamadas para ação">
        <Link
          to="/register/candidato"
          className="w-full sm:w-auto rounded-lg bg-blue-700 text-white px-6 py-3 font-semibold shadow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 text-center"
          aria-label="Sou Candidato, Quero Ajudar"
        >
          Sou Candidato, Quero Ajudar
        </Link>
        <Link
          to="/register/instituicao"
          className="w-full sm:w-auto rounded-lg bg-white text-blue-700 px-6 py-3 font-semibold shadow ring-1 ring-inset ring-blue-700/30 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 text-center"
          aria-label="Sou Instituição, Preciso de Apoio"
        >
          Sou Instituição, Preciso de Apoio
        </Link>
      </section>

      {/* Fluxo em 4 passos */}
      <section className="mt-12" aria-labelledby="secao-fluxo">
        <h2 id="secao-fluxo" className="text-2xl font-bold text-center">Fluxo simples em quatro passos</h2>
        <ol className="mt-8 grid gap-6 md:grid-cols-4 list-decimal [counter-reset:step]">
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">1. Cadastre-se</h3>
            <p>Escolha ser instituição ou candidato. Informe dados essenciais e, quando aplicável, experiências.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">2. Encontre</h3>
            <p>Instituições publicam vagas. Candidatos buscam e salvam oportunidades compatíveis.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">3. Envie proposta</h3>
            <p>A interação acontece por propostas formais. Nada de contato direto antes da aceitação.</p>
          </li>
          <li className="p-4 border rounded-lg bg-white shadow-sm">
            <h3 className="font-semibold mb-2">4. Conecte-se</h3>
            <p>Após a aceitação, os contatos são liberados e o processo segue com segurança.</p>
          </li>
        </ol>
      </section>

      {/* Benefícios por público */}
      <section className="mt-12" aria-labelledby="secao-beneficios">
        <h2 id="secao-beneficios" className="text-2xl font-bold text-center">Benefícios por público</h2>
        <div className="mt-8 grid gap-6 md:grid-cols-2">
          <article className="p-5 border rounded-lg bg-white shadow-sm" aria-labelledby="benef-candidatos">
            <h3 id="benef-candidatos" className="font-semibold text-lg">Para candidatos</h3>
            <ul className="mt-3 list-disc ps-5 space-y-1">
              <li>Trabalho com propósito e foco em inclusão educacional.</li>
              <li>Propostas de instituições verificadas.</li>
              <li>Proteção de dados pessoais até a aceitação.</li>
            </ul>
            <div className="mt-4">
              <Link to="/vagas" className="underline text-blue-700 hover:no-underline">Explorar vagas</Link>
            </div>
          </article>

          <article className="p-5 border rounded-lg bg-white shadow-sm" aria-labelledby="benef-instituicoes">
            <h3 id="benef-instituicoes" className="font-semibold text-lg">Para instituições</h3>
            <ul className="mt-3 list-disc ps-5 space-y-1">
              <li>Perfis qualificados e filtragem por experiência.</li>
              <li>Processo padronizado de propostas e status.</li>
              <li>Gestão centralizada de vagas e candidatos.</li>
            </ul>
            <div className="mt-4">
              <Link to="/candidatos" className="underline text-blue-700 hover:no-underline">Buscar candidatos</Link>
            </div>
          </article>
        </div>
      </section>

      {/* Depoimentos opcional */}
      <section className="mt-12" aria-labelledby="secao-depoimentos">
        <h2 id="secao-depoimentos" className="text-2xl font-bold text-center">Depoimentos</h2>
        <div className="mt-8 grid gap-6 md:grid-cols-2">
          <blockquote className="p-5 border rounded-lg bg-white shadow-sm">
            <p className="italic">“O fluxo de propostas trouxe segurança para iniciar conversas.”</p>
            <footer className="mt-2 text-sm text-zinc-600">Coordenadora pedagógica</footer>
          </blockquote>
          <blockquote className="p-5 border rounded-lg bg-white shadow-sm">
            <p className="italic">“Consegui achar vagas alinhadas com minha experiência.”</p>
            <footer className="mt-2 text-sm text-zinc-600">Agente de apoio</footer>
          </blockquote>
        </div>
      </section>

      {/* Atalhos finais */}
      <nav className="mt-12 text-center" aria-label="Atalhos finais">
        <Link to="/register" className="underline text-blue-700 hover:no-underline">Criar conta</Link>
        <span className="mx-2" aria-hidden="true">|</span>
        <Link to="/login" className="underline text-blue-700 hover:no-underline">Entrar</Link>
      </nav>
    </main>
  )
}
