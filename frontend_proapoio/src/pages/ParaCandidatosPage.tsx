import React from 'react';
import { Link } from 'react-router-dom';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { UserPlus, Search, Heart, CheckCircle, Zap, MessageSquare, Briefcase, Send, User } from 'lucide-react'; // Ícones para Recursos

/**
 * @component RecursoCard
 * @description Card modularizado para exibir recursos específicos do candidato.
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
 * @component ParaCandidatosPage
 * @description Página de destino dedicada a Agentes de Apoio.
 * Refatorada para usar classes globais e melhorar a semântica.
 */
export default function ParaCandidatosPage() {
    return (
        <div className="page-wrapper">
            <Header />
            <main
                id="conteudo"
                role="main"
                className="container py-lg"
                aria-labelledby="titulo-para-candidatos"
            >
                <div id="live-para-candidatos" className="sr-only" aria-live="polite" aria-atomic="true" />

                <header className="text-center mb-xl">
                    <h1 id="titulo-para-candidatos" className="heading-primary mb-md">
                        Para Agentes de Apoio
                    </h1>
                    <p className="text-lg text-muted max-w-2xl mx-auto">
                        Encontre vagas alinhadas à sua experiência e envie propostas com segurança para as instituições parceiras.
                    </p>
                </header>

                {/* Chamadas principais (CTAs) */}
                <section
                    className="flex-actions-center mb-xl" // Alinha ao centro, usa gap e flex-col/row responsivo
                    aria-labelledby="cta-candidatos"
                >
                    <h2 id="cta-candidatos" className="sr-only">Ações principais</h2>

                    <Link
                        to="/register/candidato"
                        className="btn-primary btn-lg" // Botão Primário, tamanho grande
                        aria-label="Criar conta de candidato"
                    >
                        Criar Conta de Agente de Apoio
                    </Link>

                    <Link
                        to="/buscar-vagas"
                        className="btn-secondary btn-lg" // Botão Secundário, tamanho grande
                        aria-label="Explorar vagas disponíveis"
                    >
                        Explorar Vagas
                    </Link>
                </section>

                {/* Fluxo do candidato */}
                <section className="section-padding-sm" aria-labelledby="fluxo-candidato">
                    <h2 id="fluxo-candidato" className="heading-secondary text-center mb-lg">
                        Seu fluxo em quatro passos
                    </h2>
                    
                    {/* steps-grid define o layout de 4 colunas em desktop */}
                    <ol className="steps-grid steps-grid-4"> 
                        <li className="card-simple card-step">
                            <Zap size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">1. Perfil Completo</h3>
                            <p className="text-sm text-muted">Cadastre-se e descreva suas experiências relevantes e habilidades em Libras ou Braile.</p>
                        </li>
                        <li className="card-simple card-step">
                            <Search size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">2. Busque Vagas</h3>
                            <p className="text-sm text-muted">Use filtros avançados por localização, regime e tipo de deficiência para encontrar a vaga ideal.</p>
                        </li>
                        <li className="card-simple card-step">
                            <Send size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">3. Proponha</h3>
                            <p className="text-sm text-muted">Salve vagas e envie propostas formais e personalizadas para iniciar a conversa com a Instituição.</p>
                        </li>
                        <li className="card-simple card-step">
                            <CheckCircle size={24} className="icon-step" />
                            <h3 className="title-md mb-xs">4. Confirme Contato</h3>
                            <p className="text-sm text-muted">Ao ter sua proposta aceita, os contatos são liberados e o processo avança para a contratação.</p>
                        </li>
                    </ol>
                </section>

                {/* Recursos */}
                <section className="section-padding-sm" aria-labelledby="recursos-candidato">
                    <h2 id="recursos-candidato" className="heading-secondary text-center mb-lg">
                        Recursos para você
                    </h2>
                    <div className="grid-3-col">
                        <RecursoCard
                            id="recurso-salvos"
                            title="Vagas Salvas"
                            description="Organize oportunidades favoritas e acompanhe atualizações de status."
                            linkTo="/vagas-salvas"
                            linkLabel="Ver Vagas Salvas"
                            icon={<Heart size={32} />}
                        />
                        <RecursoCard
                            id="recurso-propostas"
                            title="Gerenciamento de Propostas"
                            description="Envie, acompanhe status (Aceita/Recusada) e gerencie respostas em um só lugar."
                            linkTo="/minhas-propostas"
                            linkLabel="Acessar Minhas Propostas"
                            icon={<MessageSquare size={32} />}
                        />
                        <RecursoCard
                            id="recurso-perfil"
                            title="Perfil Atualizado"
                            description="Mantenha suas experiências e certificações atualizadas para atrair mais instituições."
                            linkTo="/perfil/candidato"
                            linkLabel="Editar Meu Perfil"
                            icon={<User size={32} />}
                        />
                    </div>
                </section>

                {/* FAQ */}
                <section className="section-padding-sm" aria-labelledby="faq-candidato">
                    <h2 id="faq-candidato" className="heading-secondary text-center mb-lg">
                        Perguntas Frequentes
                    </h2>
                    <div className="faq-grid">
                        <details className="card-simple card-faq">
                            <summary className="font-semibold cursor-pointer summary-focus">
                                Meus dados de contato ficam públicos?
                            </summary>
                            <p className="mt-xs text-sm text-muted">
                                Não. O contato (email e telefone) só é liberado para a Instituição após você aceitar uma proposta enviada por ela, ou após a Instituição aceitar sua proposta. Sua privacidade é prioridade.
                            </p>
                        </details>

                        <details className="card-simple card-faq">
                            <summary className="font-semibold cursor-pointer summary-focus">
                                Posso desfazer uma remoção de vaga salva?
                            </summary>
                            <p className="mt-xs text-sm text-muted">Sim. Após remover, um botão "Desfazer" temporário aparece para reverter a ação.</p>
                        </details>

                        <details className="card-simple card-faq">
                            <summary className="font-semibold cursor-pointer summary-focus">
                                Como aumento minhas chances de contratação?
                            </summary>
                            <p className="mt-xs text-sm text-muted">
                                Descreva suas experiências objetivamente, destaque suas habilidades específicas (Libras, Braile, etc.) e mantenha seu perfil sempre atualizado.
                            </p>
                        </details>
                    </div>
                </section>

                {/* Atalhos finais */}
                <nav className="text-center mt-xl" aria-label="Atalhos finais de cadastro e vagas">
                    <Link
                        to="/register/candidato"
                        className="btn-link text-lg"
                        aria-label="Criar conta de candidato"
                    >
                        Criar Conta de Agente de Apoio
                    </Link>
                    <span className="mx-md text-muted" aria-hidden="true">
                        |
                    </span>
                    <Link
                        to="/vagas"
                        className="btn-link text-lg"
                        aria-label="Explorar vagas disponíveis"
                    >
                        Explorar Vagas
                    </Link>
                </nav>
            </main>
            <Footer />
        </div>
    );
}