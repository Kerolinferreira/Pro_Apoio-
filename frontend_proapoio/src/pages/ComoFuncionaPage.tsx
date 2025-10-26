import React from 'react';
import Header from '../components/Header';
import Footer from '../components/Footer';

/**
 * @component ComoFuncionaPage
 * @description Página informativa explicando o fluxo de trabalho da plataforma para ambos os usuários: Candidato (Agente de Apoio) e Instituição.
 * O design e layout são baseados em classes semânticas do global.css, removendo o CSS utilitário.
 */
const ComoFuncionaPage: React.FC = () => {
  return (
    <>
      <Header />
      <main className="container py-xl">
        {/* Título Principal */}
        <h1 className="heading-primary text-center mb-lg">Como Funciona o Pro Apoio?</h1>

        <p className="text-center text-muted mb-xl">
          Conectamos Agentes de Apoio (Candidatos) a Instituições que buscam profissionais qualificados para inclusão.
        </p>

        {/* ================================== */}
        {/* Seção 1: Fluxo para Agentes de Apoio (Candidatos) */}
        {/* ================================== */}
        <section className="mb-xl">
          <h2 className="heading-secondary mb-lg" style={{ color: 'var(--color-brand)' }}>
            Para você, Agente de Apoio
          </h2>

          <div className="flex-group space-y-md">
            {/* Passo 1 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">1. Cadastro e Perfil Completo</h3>
              <p className="text-sm text-muted">
                Crie seu perfil detalhado. Informe suas experiências profissionais, habilidades (como Libras e Braile) e tipos de deficiência com os quais você tem familiaridade. Quanto mais completo o perfil, maior a chance de ser encontrado.
              </p>
            </div>
            
            {/* Passo 2 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">2. Busque Vagas e Salve Oportunidades</h3>
              <p className="text-sm text-muted">
                Use a busca avançada para encontrar vagas que correspondem à sua escolaridade e localização. Salve suas vagas favoritas para acompanhar o status e decidir quando enviar sua proposta.
              </p>
            </div>
            
            {/* Passo 3 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">3. Envie sua Proposta</h3>
              <p className="text-sm text-muted">
                Ao encontrar a vaga ideal, envie uma proposta diretamente à Instituição, demonstrando seu interesse. Você pode anexar documentos e incluir uma mensagem personalizada.
              </p>
            </div>

            {/* Passo 4 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">4. Receba Convites e Notificações</h3>
              <p className="text-sm text-muted">
                Instituições podem entrar em contato com você se o seu perfil for compatível. Acompanhe o status das suas propostas e receba notificações sobre novas oportunidades e respostas.
              </p>
            </div>
          </div>
        </section>

        {/* ================================== */}
        {/* Seção 2: Fluxo para Instituições */}
        {/* ================================== */}
        <section>
          <h2 className="heading-secondary mb-lg" style={{ color: 'var(--color-secondary)' }}>
            Para sua Instituição
          </h2>
          
          <div className="flex-group space-y-md">
            {/* Passo 1 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">1. Publique Vagas Detalhadas</h3>
              <p className="text-sm text-muted">
                Cadastre as vagas com todos os detalhes importantes: regime, cidade, requisitos e expectativas. Vagas claras atraem candidatos mais alinhados.
              </p>
            </div>
            
            {/* Passo 2 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">2. Encontre Profissionais Específicos</h3>
              <p className="text-sm text-muted">
                Utilize a busca de candidatos para filtrar Agentes de Apoio por escolaridade, localização e experiência com tipos específicos de deficiência, acelerando o processo de recrutamento.
              </p>
            </div>
            
            {/* Passo 3 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">3. Gerencie Propostas</h3>
              <p className="text-sm text-muted">
                Receba e gerencie todas as propostas em um só lugar. Aceite, recuse ou interaja com os candidatos que se candidataram à sua vaga.
              </p>
            </div>

            {/* Passo 4 */}
            <div className="card-simple">
              <h3 className="title-lg mb-xs">4. Notificação e Contratação</h3>
              <p className="text-sm text-muted">
                Após aceitar uma proposta, o candidato é notificado e vocês podem prosseguir com os trâmites de contratação fora da plataforma.
              </p>
            </div>
          </div>
        </section>

        <p className="text-center text-muted mt-xl">
          Pronto para começar? Escolha seu perfil e junte-se à nossa rede!
        </p>

      </main>
      <Footer />
    </>
  );
};

export default ComoFuncionaPage;
