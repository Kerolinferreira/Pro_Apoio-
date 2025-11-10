import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import Header from '../components/Header';
import { Briefcase, Search, Heart, MessageSquare, User, FileText, TrendingUp } from 'lucide-react';

/**
 * @component FooterBase
 * @description Footer compartilhado para páginas autenticadas.
 */
const FooterBase: React.FC = () => {
    const year = new Date().getFullYear();
    return (
        <footer className="footer-primary">
            <div className="container footer-content">
                <p className="text-sm">&copy; {year} ProApoio. Todos os direitos reservados.</p>
                <div className="flex-group-item gap-md">
                    <Link to="/acessibilidade" className="text-sm link-muted">
                        Acessibilidade
                    </Link>
                    <Link to="/privacidade" className="text-sm link-muted">
                        Política de Privacidade
                    </Link>
                    <Link to="/termos" className="text-sm link-muted">
                        Termos de Uso
                    </Link>
                    <Link to="/contato" className="text-sm link-muted">
                        Contato
                    </Link>
                </div>
            </div>
        </footer>
    );
};

/**
 * @component DashboardPage
 * @description Página inicial para usuários autenticados (Candidato ou Instituição).
 * Exibe painel personalizado com atalhos rápidos e resumo de atividades.
 */
export default function DashboardPage() {
    const { user } = useAuth();

    // Se não estiver autenticado, não renderiza nada (o redirecionamento será feito no App.tsx)
    if (!user) {
        return null;
    }

    const isCandidato = user.tipo_usuario === 'candidato';

    return (
        <div className="page-wrapper">
            {/* Header com navegação adaptada ao tipo de usuário */}
            <Header />

            {/* Skip link para acessibilidade */}
            <a href="#conteudo" className="skip-link">
                Pular para o conteúdo principal
            </a>

            {/* Main Content */}
            <main id="conteudo" className="main-content">

                {/* Hero Section Personalizado */}
                <section className="hero-section-dashboard">
                    <div className="container hero-content">
                        <h1 className="heading-primary">
                            Bem-vindo(a) ao ProApoio!
                        </h1>
                        <p className="text-lg text-muted">
                            {isCandidato
                                ? "Encontre oportunidades e acompanhe suas candidaturas."
                                : "Encontre agentes de apoio e gerencie suas propostas."}
                        </p>

                        {/* CTAs principais baseados no tipo de usuário */}
                        <div className="hero-actions">
                            {isCandidato ? (
                                <>
                                    <Link
                                        to="/vagas"
                                        className="btn-primary btn-lg"
                                        aria-label="Explorar Vagas"
                                    >
                                        <Search size={20} />
                                        Explorar Vagas
                                    </Link>
                                    <Link
                                        to="/minhas-propostas"
                                        className="btn-secondary btn-lg"
                                        aria-label="Ver Minhas Candidaturas"
                                    >
                                        <MessageSquare size={20} />
                                        Ver Minhas Candidaturas
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link
                                        to="/vagas/criar"
                                        className="btn-primary btn-lg"
                                        aria-label="Publicar Nova Vaga"
                                    >
                                        <FileText size={20} />
                                        Publicar Nova Vaga
                                    </Link>
                                    <Link
                                        to="/candidatos"
                                        className="btn-secondary btn-lg"
                                        aria-label="Buscar Candidatos"
                                    >
                                        <Search size={20} />
                                        Buscar Candidatos
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </section>

                {/* Seção de Atalhos Rápidos */}
                <section className="section-padding">
                    <div className="container">
                        <h2 className="heading-secondary mb-lg">Acesso Rápido</h2>

                        {/* Grid de cards de atalhos */}
                        <div className="dashboard-grid">
                            {isCandidato ? (
                                <>
                                    {/* Card: Buscar Vagas */}
                                    <Link to="/vagas" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <Search size={32} />
                                        </div>
                                        <h3 className="title-md">Buscar Vagas</h3>
                                        <p className="text-sm text-muted">
                                            Explore oportunidades disponíveis e encontre a vaga ideal para você.
                                        </p>
                                    </Link>

                                    {/* Card: Vagas Salvas */}
                                    <Link to="/vagas-salvas" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <Heart size={32} />
                                        </div>
                                        <h3 className="title-md">Vagas Salvas</h3>
                                        <p className="text-sm text-muted">
                                            Acesse rapidamente as vagas que você salvou para revisar depois.
                                        </p>
                                    </Link>

                                    {/* Card: Minhas Candidaturas */}
                                    <Link to="/minhas-propostas" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <MessageSquare size={32} />
                                        </div>
                                        <h3 className="title-md">Minhas Candidaturas</h3>
                                        <p className="text-sm text-muted">
                                            Acompanhe o status das suas candidaturas e propostas recebidas.
                                        </p>
                                    </Link>

                                    {/* Card: Meu Perfil */}
                                    <Link to="/perfil/candidato" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <User size={32} />
                                        </div>
                                        <h3 className="title-md">Meu Perfil</h3>
                                        <p className="text-sm text-muted">
                                            Mantenha seu perfil atualizado para aumentar suas chances.
                                        </p>
                                    </Link>
                                </>
                            ) : (
                                <>
                                    {/* Card: Publicar Vaga */}
                                    <Link to="/vagas/criar" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <FileText size={32} />
                                        </div>
                                        <h3 className="title-md">Publicar Vaga</h3>
                                        <p className="text-sm text-muted">
                                            Crie uma nova oportunidade e encontre agentes de apoio qualificados.
                                        </p>
                                    </Link>

                                    {/* Card: Buscar Candidatos */}
                                    <Link to="/candidatos" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <Search size={32} />
                                        </div>
                                        <h3 className="title-md">Buscar Candidatos</h3>
                                        <p className="text-sm text-muted">
                                            Explore perfis de agentes de apoio e encontre o candidato ideal.
                                        </p>
                                    </Link>

                                    {/* Card: Minhas Vagas */}
                                    <Link to="/vagas/minhas" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <Briefcase size={32} />
                                        </div>
                                        <h3 className="title-md">Minhas Vagas</h3>
                                        <p className="text-sm text-muted">
                                            Gerencie as oportunidades que você publicou e suas candidaturas.
                                        </p>
                                    </Link>

                                    {/* Card: Minhas Propostas */}
                                    <Link to="/minhas-propostas" className="dashboard-card">
                                        <div className="dashboard-card-icon">
                                            <MessageSquare size={32} />
                                        </div>
                                        <h3 className="title-md">Minhas Propostas</h3>
                                        <p className="text-sm text-muted">
                                            Acompanhe propostas enviadas e candidaturas recebidas.
                                        </p>
                                    </Link>
                                </>
                            )}
                        </div>

                        {/* Seção de Resumo/Estatísticas (placeholder para futuras implementações) */}
                        <div className="dashboard-stats mt-xl">
                            <div className="card-simple">
                                <div className="flex-group-item gap-md align-center">
                                    <TrendingUp size={24} className="text-primary" />
                                    <div>
                                        <h3 className="title-md">Resumo de Atividades</h3>
                                        <p className="text-sm text-muted mt-xs">
                                            {isCandidato
                                                ? "Acompanhe suas candidaturas, propostas recebidas e vagas salvas em um só lugar."
                                                : "Acompanhe suas vagas publicadas, propostas enviadas e candidatos interessados."}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Seção de Ajuda/Suporte */}
                <section className="section-padding bg-subtle">
                    <div className="container text-center">
                        <h2 className="heading-secondary mb-md">Precisa de Ajuda?</h2>
                        <p className="text-base text-muted mb-lg">
                            Nossa equipe está pronta para auxiliar você em qualquer dúvida ou dificuldade.
                        </p>
                        <Link to="/suporte" className="btn-secondary">
                            Acessar Suporte
                        </Link>
                    </div>
                </section>
            </main>

            {/* Footer */}
            <FooterBase />
        </div>
    );
}
