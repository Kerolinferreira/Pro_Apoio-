import { Link } from 'react-router-dom'

/**
 * @component HeaderBase
 * @description Header simplificado da Landing Page (Não usa o componente Header.tsx de áreas logadas).
 * Refatorado para usar classes globais e evitar repetição de link styles.
 */
const HeaderBase: React.FC = () => {
    return (
        <header role="banner" className="header-primary">
            <div className="container header-container">
                <Link
                    to="/"
                    aria-label="Página inicial ProApoio"
                    className="logo-link"
                >
                    <span aria-hidden className="logo-icon" />
                    <span className="logo-text">ProApoio</span>
                </Link>

                <nav aria-label="Navegação principal" className="main-nav">
                    {/* Links de navegação */}
                    <Link to="/como-funciona" className="nav-link hidden-sm">
                        Como Funciona
                    </Link>
                    <Link to="/para-candidatos" className="nav-link hidden-sm">
                        Para Candidatos
                    </Link>
                    <Link to="/para-instituicoes" className="nav-link hidden-sm">
                        Para Instituições
                    </Link>

                    {/* Ações principais */}
                    <Link to="/login" className="nav-link">
                        Entrar
                    </Link>
                    {/* CTA principal: Cadastre-se */}
                    <Link
                        to="/register"
                        className="btn-secondary-inverted" // Classe para botão de contraste no Header
                        aria-label="Cadastre-se"
                    >
                        Cadastre-se
                    </Link>
                </nav>
            </div>
        </header>
    );
};

/**
 * @component FooterBase
 * @description Footer simplificado da Landing Page.
 */
const FooterBase: React.FC = () => {
    const year = new Date().getFullYear();
    return (
        <footer className="footer-primary">
            <div className="container footer-content">
                <p className="text-sm">&copy; {year} ProApoio. Todos os direitos reservados.</p>
                
            </div>
        </footer>
    );
};

/**
 * @component HomePage
 * @description Landing Page para visitantes não autenticados, com foco na proposta de valor e CTAs.
 */
export default function HomePage() {
    return (
        // page-wrapper garante min-height e layout flex vertical
        <div className="page-wrapper"> 
            
            {/* Componente Header simplificado da Landing */}
            <HeaderBase />

            {/* Skip link (Mantido para acessibilidade) */}
            <a
                href="#conteudo"
                className="skip-link"
            >
                Pular para o conteúdo principal
            </a>

            {/* Main Content */}
            <main id="conteudo" className="main-content">
                
                {/* Hero Section */}
                <section className="hero-section">
                    <div className="container hero-content">
                        {/* heading-primary para o título principal */}
                        <h1 className="heading-primary hero-title">
                            Conecte instituições e agentes de apoio para estudantes com deficiência
                        </h1>
                        <p className="text-lg text-muted hero-subtitle">
                            O ProApoio aproxima instituições de ensino com alunos com deficiência a agentes de apoio.
                            Publique oportunidades, encontre perfis compatíveis e finalize propostas com segurança.
                        </p>

                        {/* CTAs duplos */}
                        <div className="hero-actions">
                            <Link
                                to="/register/candidato"
                                className="btn-primary btn-lg" // btn-lg para destaque
                                aria-label="Sou Candidato, Quero Ajudar"
                            >
                                Sou Candidato, Quero Ajudar
                            </Link>
                            <Link
                                to="/register/instituicao"
                                className="btn-secondary btn-lg" // btn-lg para destaque
                                aria-label="Sou Instituição, Preciso de Apoio"
                            >
                                Sou Instituição, Preciso de Apoio
                            </Link>
                        </div>
                    </div>
                </section>

                {/* Seção Como Funciona (Passos) */}
                <section className="section-padding">
                    <div className="container">
                        <h2 className="heading-secondary text-center mb-lg">Como Funciona</h2>
                        
                        {/* Layout de 3 colunas para passos */}
                        <ol className="steps-grid">
                            <li className="card-simple">
                                <h3 className="title-md mb-xs">1. Cadastre-se</h3>
                                <p className="text-sm text-muted">Escolha perfil de instituição ou agente de apoio. Complete dados essenciais, além de informações sobre sua experiência ou as necessidades de seus alunos.</p>
                            </li>
                            <li className="card-simple">
                                <h3 className="title-md mb-xs">2. Encontre</h3>
                                <p className="text-sm text-muted">Instituições publicam oportunidades. Candidatos salvam vagas e demonstram interesse.</p>
                            </li>
                            <li className="card-simple">
                                <h3 className="title-md mb-xs">3. Conecte-se</h3>
                                <p className="text-sm text-muted">Envie propostas, receba candidaturas e avance pelo fluxo seguro sem expor contatos antes da aceitação.</p>
                            </li>
                        </ol>
                        
                        {/* CTA adicional para a página completa de "Como Funciona" */}
                        <div className="text-center mt-xl">
                            <Link to="/como-funciona" className="btn-secondary">
                                Ver Detalhes do Funcionamento
                            </Link>
                        </div>
                    </div>
                </section>
            </main>

            {/* Componente Footer simplificado da Landing */}
            <FooterBase />
        </div>
    );
}