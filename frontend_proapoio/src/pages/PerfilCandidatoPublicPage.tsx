import React, { useEffect, useRef, useState, useId } from 'react';
import { useParams } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { LoadingSpinner, ErrorAlert, Button } from '../components/ui';

interface CandidatoPublico {
  id: number;
  nome_completo: string;
  escolaridade: string;
  bio: string;
  deficiencias: { nome: string }[];
  experiencias_pessoais: { descricao: string }[];
  cidade?: string;
  estado?: string;
  foto_url?: string;
  // Campos sensíveis NÃO devem existir aqui; se vierem, não renderizamos
  email?: string;
  telefone?: string;
  cpf?: string;
}

interface VagaResumo {
  id: number;
  titulo: string;
  status: 'ABERTA' | 'PAUSADA' | 'FECHADA' | string;
}

interface PropostaResumo {
  id: number;
  vaga_id: number;
  candidato_id: number;
  status: 'Enviada' | 'Visualizada' | 'Aceita' | 'Recusada';
  created_at: string;
  iniciador: 'INSTITUICAO' | 'CANDIDATO';
}

const PerfilCandidatoPublicPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [candidato, setCandidato] = useState<CandidatoPublico | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Lateral fixa: vagas da instituição e proposta
  const [vagasAbertas, setVagasAbertas] = useState<VagaResumo[]>([]);
  const [vagaSelecionada, setVagaSelecionada] = useState<number | ''>('');
  const [enviando, setEnviando] = useState(false);
  const [mensagem, setMensagem] = useState('');
  const [propostaJaEnviadaEm, setPropostaJaEnviadaEm] = useState<string | null>(null);

  // ARIA live regions
  const [statusMsg, setStatusMsg] = useState<string>('');
  const statusRegionRef = useRef<HTMLDivElement>(null);

  // Acessibilidade: foco no título após carregar
  const h1Ref = useRef<HTMLHeadingElement>(null);
  const selectId = useId();
  const textareaId = useId();

  useEffect(() => {
    const carregar = async () => {
      if (!id) {
        setError('ID do candidato não fornecido.');
        setLoading(false);
        return;
      }
      try {
        // Perfil público
        const perfil = await api.get(`/candidatos/${id}`);
        setCandidato(perfil.data);

        // Lista de vagas ABERTAS da instituição logada
        // Preferência por endpoint com filtro de escopo próprio. Ajuste conforme seu backend.
        const vagasResp = await api.get('/vagas', {
          params: { minhas: 1, status: 'aberta' }
        });
        const vagasFiltradas: VagaResumo[] = (vagasResp.data || []).filter(
          (v: VagaResumo) => String(v.status).toUpperCase() === 'ABERTA'
        );
        setVagasAbertas(vagasFiltradas);

        // Verificar se JÁ existe proposta enviada por esta instituição a este candidato
        // Usa o contrato: GET /propostas?tipo=enviadas&candidatoId={id}
        const propostasEnv = await api.get('/propostas', {
          params: { tipo: 'enviadas', candidatoId: id }
        });
        const lista: PropostaResumo[] = propostasEnv.data || [];
        const enviadaMaisRecente = lista
          .filter(p => p.iniciador === 'INSTITUICAO')
          .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())[0];

        if (enviadaMaisRecente) {
          setPropostaJaEnviadaEm(enviadaMaisRecente.created_at);
        }

        setLoading(false);
        // Mover foco ao título para leitura pelo NVDA
        setTimeout(() => h1Ref.current?.focus(), 0);
      } catch (err) {
        console.error('Erro ao carregar perfil:', err);
        setError('Não foi possível carregar o perfil do candidato.');
        setLoading(false);
      }
    };

    carregar();
  }, [id]);

  const enviarProposta = async () => {
    if (!candidato || vagaSelecionada === '' || !mensagem.trim()) return;
    try {
      setEnviando(true);
      setStatusMsg('Enviando proposta...');
      // Idempotência: backend deve rejeitar duplicadas para mesmo candidato+vaga em aberto
      const resp = await api.post('/propostas', {
        candidato_id: candidato.id,
        vaga_id: vagaSelecionada,
        mensagem
      });
      // Atualiza indicador “já enviado”
      const createdAt = resp?.data?.created_at || new Date().toISOString();
      setPropostaJaEnviadaEm(createdAt);
      setStatusMsg('Proposta enviada com sucesso.');
    } catch (err) {
      console.error('Erro ao enviar proposta:', err);
      setStatusMsg('Falha ao enviar a proposta.');
    } finally {
      setEnviando(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-screen" role="status" aria-live="polite">
        <LoadingSpinner />
      </div>
    );
  }

  if (error) {
    return <ErrorAlert message={error} />;
  }

  if (!candidato) {
    return <ErrorAlert message="Perfil não encontrado." />;
  }

  // Segurança de privacidade: NÃO renderizar contato antes de aceite
  const camposSensiveisPresentes =
    !!(candidato as any).email || !!(candidato as any).telefone || !!(candidato as any).cpf;

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main id="conteudo-principal" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" role="main">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          {/* Coluna principal */}
          <article
            className="lg:col-span-8 bg-white shadow-lg rounded-lg p-6"
            role="article"
            aria-labelledby="titulo-perfil"
          >
            <div className="flex items-center gap-6">
              <div className="w-24 h-24 bg-gray-200 rounded-full overflow-hidden flex-shrink-0" aria-hidden="true">
                {candidato.foto_url ? (
                  <img
                    src={candidato.foto_url}
                    alt={`Foto de ${candidato.nome_completo}`}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <svg className="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M24 20.993V24H0v-3.007a12 12 0 0112-12c4.418 0 8.582 1.993 12 5.007zM12 9a6 6 0 100-12 6 6 0 000 12z" />
                  </svg>
                )}
              </div>
              <div>
                <h1
                  id="titulo-perfil"
                  ref={h1Ref}
                  tabIndex={-1}
                  className="text-3xl font-bold text-gray-900"
                >
                  {candidato.nome_completo}
                </h1>
                <p className="text-gray-600">
                  {candidato.cidade || 'Cidade não informada'}
                  {candidato.estado ? `, ${candidato.estado}` : ''}
                </p>
              </div>
            </div>

            <section className="mt-8" aria-labelledby="sec-sobre">
              <h2 id="sec-sobre" className="text-xl font-semibold text-gray-900">
                Sobre mim
              </h2>
              <p className="mt-2 text-gray-700 whitespace-pre-wrap">
                {candidato.bio || 'Nenhuma biografia fornecida.'}
              </p>
            </section>

            <section className="mt-8" aria-labelledby="sec-deficiencias">
              <h2 id="sec-deficiencias" className="text-xl font-semibold text-gray-900">
                Deficiências e condições
              </h2>
              <div className="mt-3">
                <div className="text-sm text-gray-600">Escolaridade</div>
                <div className="text-gray-800">{candidato.escolaridade || 'Não informado'}</div>
              </div>

              <div className="mt-4">
                <div className="text-sm text-gray-600">Deficiências associadas</div>
                {candidato.deficiencias && candidato.deficiencias.length > 0 ? (
                  <ul className="mt-2 flex flex-wrap gap-2" role="list" aria-label="Lista de deficiências associadas">
                    {candidato.deficiencias.map((d, index) => (
                      <li
                        key={index}
                        role="listitem"
                        className="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full"
                      >
                        {d.nome}
                      </li>
                    ))}
                  </ul>
                ) : (
                  <p className="mt-2 text-gray-700">Nenhuma deficiência listada.</p>
                )}
              </div>
            </section>

            <section className="mt-8" aria-labelledby="sec-exp-pessoais">
              <h2 id="sec-exp-pessoais" className="text-xl font-semibold text-gray-900">
                Experiências pessoais relevantes
              </h2>
              {candidato.experiencias_pessoais && candidato.experiencias_pessoais.length > 0 ? (
                <ul className="mt-3 space-y-2 text-gray-700" role="list" aria-label="Lista de experiências pessoais">
                  {candidato.experiencias_pessoais.map((exp, index) => (
                    <li key={index} role="listitem">
                      {exp.descricao}
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="mt-2 text-gray-700">Nenhuma experiência pessoal relevante listada.</p>
              )}
            </section>

            {/* Privacidade: bloquear exibição de contato antes de aceite */}
            {camposSensiveisPresentes && (
              <section className="mt-8" aria-labelledby="sec-privacidade">
                <h2 id="sec-privacidade" className="text-xl font-semibold text-gray-900">
                  Privacidade
                </h2>
                <p className="mt-2 text-gray-700">
                  Informações de contato são protegidas e só ficam visíveis após uma proposta ser aceita por ambas as partes.
                </p>
              </section>
            )}
          </article>

          {/* Coluna lateral fixa */}
          <aside
            className="lg:col-span-4"
            aria-labelledby="sec-cta-interesse"
          >
            <div className="sticky top-6 bg-white shadow-lg rounded-lg p-6" role="complementary" aria-labelledby="sec-cta-interesse">
              <h2 id="sec-cta-interesse" className="text-lg font-semibold text-gray-900">
                Interessado neste perfil?
              </h2>

              {/* Caso já exista proposta enviada pela instituição a este candidato */}
              {propostaJaEnviadaEm ? (
                <p className="mt-2 text-sm text-gray-700" aria-live="polite">
                  Você já enviou uma proposta em {new Date(propostaJaEnviadaEm).toLocaleDateString()}.
                </p>
              ) : (
                <p className="mt-2 text-sm text-gray-600">
                  Selecione uma vaga aberta da sua instituição e envie um convite formal.
                </p>
              )}

              <div className="mt-4">
                <label htmlFor={selectId} className="block text-sm font-medium text-gray-700">
                  Convidar para a vaga de…
                </label>
                <select
                  id={selectId}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                  value={vagaSelecionada}
                  onChange={(e) => setVagaSelecionada(e.target.value ? Number(e.target.value) : '')}
                  aria-describedby={vagasAbertas.length === 0 ? 'ajuda-vagas' : undefined}
                >
                  <option value="">Selecione uma vaga aberta</option>
                  {vagasAbertas.map((v) => (
                    <option key={v.id} value={v.id}>
                      {v.titulo}
                    </option>
                  ))}
                </select>
                {vagasAbertas.length === 0 && (
                  <p id="ajuda-vagas" className="mt-2 text-sm text-gray-600">
                    Você não possui vagas abertas. Cadastre ou reative uma vaga para enviar propostas.
                  </p>
                )}
              </div>

              <div className="mt-4">
                <label htmlFor={textareaId} className="block text-sm font-medium text-gray-700">
                  Mensagem da proposta
                </label>
                <textarea
                  id={textareaId}
                  className="mt-1 block w-full min-h-[96px] rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"
                  placeholder="Apresente a vaga e o interesse no perfil."
                  value={mensagem}
                  onChange={(e) => setMensagem(e.target.value)}
                />
              </div>

              <div className="mt-5">
                <Button
                  variant="primary"
                  onClick={enviarProposta}
                  disabled={
                    !!propostaJaEnviadaEm ||
                    enviando ||
                    vagaSelecionada === '' ||
                    mensagem.trim().length === 0
                  }
                  aria-disabled={
                    !!propostaJaEnviadaEm ||
                    enviando ||
                    vagaSelecionada === '' ||
                    mensagem.trim().length === 0
                  }
                  aria-describedby="ajuda-envio"
                >
                  {enviando ? 'Enviando…' : 'Enviar Proposta'}
                </Button>
                <p id="ajuda-envio" className="mt-2 text-xs text-gray-600">
                  O contato direto só será liberado após o candidato aceitar a proposta.
                </p>
              </div>

              <div
                ref={statusRegionRef}
                className="mt-3 text-sm text-gray-700"
                role="status"
                aria-live="polite"
              >
                {statusMsg}
              </div>
            </div>
          </aside>
        </div>
      </main>
      <Footer />
    </div>
  );
};

export default PerfilCandidatoPublicPage;
