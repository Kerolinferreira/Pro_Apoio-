import React from 'react';
import { Link } from 'react-router-dom';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { Briefcase, UserPlus, Search, Send, Clock, MapPin } from 'lucide-react'; // Ícones para Recursos

/**
 * @component RecursoCard
 * @description Card modularizado para exibir recursos específicos da Instituição.
 */
interface RecursoCardProps {
    id: string;
    title: string;
    description: string;
    linkTo: string;
    linkLabel: string;
    icon: React.ReactNode;
}

const RecursoCard: React.FC<RecursoCardProps> = ({ id, title, description, linkTo, linkLabel, icon }) => (
    <article
        className="card-simple card-feature" // card-simple com padding ajustado
        aria-labelledby={id}
    >
        <div className="text-brand-color mb-xs">{icon}</div> {/* Ícone */}
        
        <h3 id={id} className="title-md mb-xs">
            {title}
        </h3>
        <p className="text-sm text-muted">{description}</p>
        
        <div className="mt-md">
            <Link
                to={linkTo}
                className="btn-link text-sm" // Classe global para link de texto
                aria-label={linkLabel}
            >
                {linkLabel}
            </Link>
        </div>
    </article>
);


/**
 * @component ParaInstituicoesPage
 * @description Página de destino dedicada a Instituições.
 * Refatorada para usar classes globais e melhorar a semântica.
 */
export default function ParaInstituicoesPage() {
    return (
        <div className="page-wrapper">
            <Header />
            <main
                id="conteudo"
                role="main"
                className="container py-lg"
                aria-labelledby="titulo-para-instituicoes"
            >
                <div id="live-para-instituicoes" className="sr-only" aria-live="polite" aria-atomic="true" />

                <header className="text-center mb-xl">
                    <h1 id="titulo-para-instituicoes" className="heading-primary mb-md">
                        Para Sua Instituição
                    </h1>
                    <p className="text-lg text-muted max-w-2xl mx-auto">
                        Encontre agentes de apoio com experiência comprovada e gerencie o processo de contratação com segurança e eficiência.
                    </p>
                </header>

                {/* Chamadas principais (CTAs) */}
                <section
                    className="flex-actions-center mb-xl" // Alinha ao centro, usa gap e flex-col/row responsivo
                    aria-labelledby="cta-instituicoes"
                >
                    <h2 id="cta-instituicoes" className="sr-only">Ações principais</h2>

                    <Link
                        to="/register/instituicao"
                        className="btn-primary btn-lg" // Botão Primário, tamanho grande
                        aria-label="Criar conta institucional"
                    >
                        Criar Conta de Instituição
                    </Link>

                    <Link
                        to="/buscar-candidatos"
                        className="btn-secondary btn-lg" // Botão Secundário, tamanho grande
                        aria-label="Ir para busca de candidatos"
                    >
                        Buscar Agentes de Apoio
                    </Link>
                </section>

                {/* Fluxo da instituição */}
                <section className="section-padding-sm" aria-labelledby="fluxo-instituicao">
                    <h2 id="fluxo-instituicao" className="heading-secondary text-center mb-lg">
                        Seu fluxo em quatro passos
                    </h2>
                    
                    {/* steps-grid-4 define o layout de 4 colunas em desktop */}
                    <ol className="steps-grid-4"> 
                        <li className="card-simple card-step">
                            <Briefcase size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">1. Perfil Institucional</h3>
                            <p className="text-sm text-muted">Cadastre-se com CNPJ e defina as necessidades específicas dos alunos.</p>
                        </li>
                        <li className="card-simple card-step">
                            <MapPin size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">2. Publique Vagas e Busque</h3>
                            <p className="text-sm text-muted">Crie vagas detalhadas e utilize a busca avançada para encontrar candidatos por experiência e localização.</p>
                        </li>
                        <li className="card-simple card-step">
                            <Send size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">3. Gerencie Propostas</h3>
                            <p className="text-sm text-muted">Receba propostas diretas de candidatos e envie convites de forma padronizada e segura.</p>
                        </li>
                        <li className="card-simple card-step">
                            <Clock size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">4. Avance</h3>
                            <p className="text-sm text-muted">Acompanhe o status (Aceita/Recusada) e libere contatos apenas após o aceite final.</p>
                        </li>
                    </ol>
                </section>

                {/* Recursos */}
                <section className="section-padding-sm" aria-labelledby="recursos-instituicao">
                    <h2 id="recursos-instituicao" className="heading-secondary text-center mb-lg">
                        Recursos para sua instituição
                    </h2>
                    <div className="grid-3-col">
                        <RecursoCard
                            id="recurso-perfil-inst"
                            title="Gerenciamento de Perfil"
                            description="Mantenha o perfil e as informações de contato da sua instituição sempre atualizadas."
                            linkTo="/perfil/instituicao"
                            linkLabel="Editar Perfil"
                            icon={<Briefcase size={32} />}
                        />
                        <RecursoCard
                            id="recurso-busca-cand"
                            title="Busca Otimizada de Agentes"
                            description="Filtre rapidamente agentes de apoio por deficiência, escolaridade e localização."
                            linkTo="/buscar-candidatos"
                            linkLabel="Abrir Busca"
                            icon={<Search size={32} />}
                        />
                        <RecursoCard
                            id="recurso-propostas"
                            title="Painel de Propostas"
                            description="Gerencie todas as propostas enviadas e recebidas em um único e organizado painel."
                            linkTo="/minhas-propostas"
                            linkLabel="Gerenciar Propostas"
                            icon={<Send size={32} />}
                        />
                    </div>
                </section>

                {/* FAQ */}
                <section className="section-padding-sm" aria-labelledby="faq-instituicao">
                    <h2 id="faq-instituicao" className="heading-secondary text-center mb-lg">
                        Perguntas Frequentes
                    </h2>
                    <div className="faq-grid">
                        <details className="card-simple card-faq">
                            <summary className="font-semibold cursor-pointer summary-focus">
                                Os contatos dos candidatos ficam visíveis?
                            </summary>
                            <p className="mt-xs text-sm text-muted">
                                Não. Os contatos são liberados apenas para a Instituição após uma proposta ser mutuamente aceita (seja enviada por você ou pelo Candidato).
                            </p>
                        </details>

                        <details className="card-simple card-faq">
                            <summary className="font-semibold cursor-pointer summary-focus">
                                Posso ver o perfil público do agente de apoio?
                            </summary>
                            <p className="mt-xs text-sm text-muted">
                                Sim. Ao usar a função "Buscar Agentes de Apoio", você tem acesso ao perfil público resumido, incluindo experiências e habilidades.
                            </p>
                        </details>

                        <details className="card-simple card-faq">
                            <summary className="font-semibold cursor-pointer summary-focus">
                                Como melhoro a compatibilidade entre a vaga e os candidatos?
                            </summary>
                            <p className="mt-xs text-sm text-muted">
                                Detalhe claramente as necessidades específicas dos alunos, as exigências de horário e o regime de contratação nas suas publicações de vaga.
                            </p>
                        </details>
                    </div>
                </section>

                {/* Atalhos finais */}
                <nav className="text-center mt-xl" aria-label="Atalhos finais de cadastro e busca">
                    <Link
                        to="/register/instituicao"
                        className="btn-link text-lg"
                        aria-label="Ir para criar conta institucional"
                    >
                        Criar Conta de Instituição
                    </Link>
                    <span className="mx-md text-muted" aria-hidden="true">
                        |
                    </span>
                    <Link
                        to="/buscar-candidatos"
                        className="btn-link text-lg"
                        aria-label="Ir para busca de candidatos"
                    >
                        Buscar Agentes de Apoio
                    </Link>
                </nav>
            </main>
            <Footer />
        </div>
    );
}