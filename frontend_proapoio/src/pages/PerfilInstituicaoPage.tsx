import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../contexts/AuthContext';

type VagaItem = {
  id: number;
  titulo: string;
  status: 'ABERTA' | 'PAUSADA' | 'FECHADA' | string;
  propostas_recebidas?: number;
  propostas_novas?: number;
  created_at?: string;
};

export default function PerfilInstituicaoPage() {
  const { user, token } = useAuth();
  const navigate = useNavigate();

  const [vagasAtivas, setVagasAtivas] = useState<VagaItem[]>([]);
  const [vagasCount, setVagasCount] = useState(0);
  const [propostasCount, setPropostasCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [revealSensitive, setRevealSensitive] = useState(false);

  // Acessibilidade: regiões vivas e foco
  const liveRef = useRef<HTMLDivElement>(null);
  const h1Ref = useRef<HTMLHeadingElement>(null);

  const isInst = useMemo(() => user?.tipo_usuario === 'instituicao', [user]);

  useEffect(() => {
    async function carregar() {
      setLoading(true);
      setError(null);
      try {
        const [vagasRes, propRes] = await Promise.all([
          api.get('/vagas/minhas'), // lista completa para contagem e widget
          api.get('/propostas', { params: { tipo: 'recebidas' } }),
        ]);

        const vagasLista: VagaItem[] = (vagasRes.data?.data ?? vagasRes.data) || [];
        setVagasCount(Array.isArray(vagasLista) ? vagasLista.length : 0);

        // Widget "Minhas Vagas Ativas" (até 5)
        const ativas = vagasLista
          .filter((v) => String(v.status).toUpperCase() === 'ABERTA')
          .slice(0, 5);
        setVagasAtivas(ativas);

        const propLista = propRes.data?.data ?? propRes.data;
        setPropostasCount(Array.isArray(propLista) ? propLista.length : 0);
      } catch {
        setError('Erro ao carregar dados.');
      } finally {
        setLoading(false);
        setTimeout(() => h1Ref.current?.focus(), 0);
      }
    }
    if (token && isInst) carregar();
  }, [token, isInst]);

  function announce(msg: string) {
    if (!liveRef.current) return;
    liveRef.current.textContent = msg;
    setTimeout(() => {
      if (liveRef.current?.textContent === msg) liveRef.current.textContent = '';
    }, 2000);
  }

  if (!token) {
    return (
      <main className="p-4 max-w-4xl mx-auto" role="main">
        <p>É necessário estar logado para acessar o painel da instituição.</p>
      </main>
    );
  }

  if (!isInst) {
    return (
      <main className="p-4 max-w-4xl mx-auto" role="main">
        <p>Este painel é exclusivo para instituições.</p>
      </main>
    );
  }

  if (loading) {
    return (
      <main className="p-4 max-w-5xl mx-auto" aria-busy="true" role="main">
        <div className="animate-pulse space-y-4" aria-hidden="true">
          <div className="h-6 bg-zinc-200 rounded w-1/3" />
          <div className="grid grid-cols-2 gap-4">
            <div className="h-24 bg-zinc-200 rounded" />
            <div className="h-24 bg-zinc-200 rounded" />
          </div>
          <div className="h-48 bg-zinc-200 rounded" />
        </div>
      </main>
    );
  }

  function maskCNPJ(cnpj?: string) {
    if (!cnpj) return '—';
    const s = String(cnpj).replace(/\D/g, '');
    if (s.length < 14) return `${s.slice(0, 2)}.***.***/****-**`;
    return `${s.slice(0, 2)}.${s.slice(2, 5)}.${s.slice(5, 8)}/${s.slice(8, 12)}-${s.slice(12, 14)}`;
  }

  // Ações da vaga conforme documentação: pausar/reativar/fechar, editar, ver propostas
  async function atualizarStatus(vaga: VagaItem, acao: 'pausar' | 'fechar' | 'reativar') {
    try {
      setBusyId(vaga.id);
      if (acao === 'pausar') {
        await api.put(`/vagas/${vaga.id}/pausar`);
      } else if (acao === 'fechar') {
        await api.put(`/vagas/${vaga.id}/fechar`);
      } else {
        // reativar: convenção — pausar alterna, então chamamos /vagas/{id}/pausar se status for PAUSADA
        await api.put(`/vagas/${vaga.id}/pausar`, { reativar: true });
      }
      // Atualiza localmente
      setVagasAtivas((prev) =>
        prev
          .map((v) =>
            v.id === vaga.id
              ? {
                  ...v,
                  status:
                    acao === 'fechar'
                      ? 'FECHADA'
                      : acao === 'pausar'
                      ? 'PAUSADA'
                      : 'ABERTA',
                }
              : v
          )
          // se fechou/pausou, pode sair do widget de "Ativas"
          .filter((v) => String(v.status).toUpperCase() === 'ABERTA')
      );
      // Recontagem simples
      setVagasCount((n) => n);
      announce(
        acao === 'fechar'
          ? 'Vaga fechada.'
          : acao === 'pausar'
          ? 'Vaga pausada.'
          : 'Vaga reativada.'
      );
    } catch {
      announce('Falha ao atualizar a vaga.');
    } finally {
      setBusyId(null);
    }
  }

  return (
    <main className="p-4 max-w-5xl mx-auto space-y-6" aria-labelledby="titulo-painel-inst" role="main">
      <div ref={liveRef} className="sr-only" aria-live="polite" />

      <h1
        id="titulo-painel-inst"
        className="text-2xl font-extrabold"
        ref={h1Ref}
        tabIndex={-1}
      >
        Painel da instituição
      </h1>

      {error && (
        <div role="alert" className="p-3 rounded bg-red-100 text-red-800">
          {error}
        </div>
      )}

      {/* Métricas */}
      <section aria-labelledby="titulo-metricas" className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <h2 id="titulo-metricas" className="sr-only">
          Métricas
        </h2>

        <article className="p-4 border rounded bg-white shadow-sm" role="article" aria-labelledby="card-vagas">
          <h3 id="card-vagas" className="text-sm text-zinc-600">
            Vagas publicadas
          </h3>
          <p className="text-3xl font-extrabold" aria-live="polite">
            {vagasCount}
          </p>
          <div className="mt-2 flex gap-3">
            <button
              onClick={() => navigate('/vagas/nova')}
              className="text-blue-700 underline text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 rounded"
            >
              + Cadastrar nova vaga
            </button>
            <button
              onClick={() => navigate('/vagas')}
              className="text-blue-700 underline text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 rounded"
            >
              Gerenciar vagas
            </button>
          </div>
        </article>

        <article className="p-4 border rounded bg-white shadow-sm" role="article" aria-labelledby="card-propostas">
          <h3 id="card-propostas" className="text-sm text-zinc-600">
            Propostas recebidas
          </h3>
          <p className="text-3xl font-extrabold" aria-live="polite">
            {propostasCount}
          </p>
          <button
            onClick={() => navigate('/propostas')}
            className="mt-2 text-blue-700 underline text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 rounded"
          >
            Ver propostas
          </button>
        </article>
      </section>

      {/* Widget: Minhas Vagas Ativas (conforme documentação do dashboard) */}
      <section
        className="p-4 border rounded bg-white shadow-sm"
        aria-labelledby="titulo-vagas-ativas"
      >
        <h2 id="titulo-vagas-ativas" className="font-semibold mb-2">
          Minhas vagas ativas
        </h2>

        {vagasAtivas.length === 0 ? (
          <div className="rounded border p-4 bg-zinc-50">
            <p className="text-sm text-zinc-800">Você não possui vagas ativas.</p>
            <button
              onClick={() => navigate('/vagas/nova')}
              className="mt-2 text-blue-700 underline text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 rounded"
            >
              + Cadastrar nova vaga
            </button>
          </div>
        ) : (
          <ul role="list" className="divide-y">
            {vagasAtivas.map((v) => (
              <li key={v.id} role="listitem" className="py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-medium text-zinc-900">{v.titulo}</span>
                    <span
                      className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800"
                      aria-label="Status da vaga: aberta"
                    >
                      Aberta
                    </span>
                  </div>
                  <p className="text-sm text-zinc-700">
                    {typeof v.propostas_recebidas === 'number'
                      ? `Propostas: ${v.propostas_recebidas}${v.propostas_novas ? ` (${v.propostas_novas} novas)` : ''}`
                      : 'Propostas: —'}
                    {v.created_at ? ` • Criada em ${new Date(v.created_at).toLocaleDateString()}` : ''}
                  </p>
                </div>

                <div className="flex gap-2">
                  <button
                    onClick={() => navigate(`/vagas/${v.id}/propostas`)}
                    className="rounded bg-zinc-200 px-3 py-1.5 text-sm hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-blue-600"
                    aria-label={`Ver propostas da vaga ${v.titulo}`}
                  >
                    Ver propostas
                  </button>
                  <button
                    onClick={() => navigate(`/vagas/${v.id}/editar`)}
                    className="rounded bg-zinc-200 px-3 py-1.5 text-sm hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-blue-600"
                    aria-label={`Editar vaga ${v.titulo}`}
                  >
                    Editar
                  </button>
                  <button
                    onClick={async () => {
                      if (!confirm('Pausar esta vaga? Ela deixará de aparecer nas buscas e não aceitará novas propostas.')) return;
                      await atualizarStatus(v, 'pausar');
                    }}
                    disabled={busyId === v.id}
                    aria-busy={busyId === v.id}
                    className="rounded bg-amber-100 text-amber-900 px-3 py-1.5 text-sm hover:bg-amber-200 disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-amber-600"
                    aria-label={`Pausar vaga ${v.titulo}`}
                  >
                    Pausar
                  </button>
                  <button
                    onClick={async () => {
                      if (!confirm('Fechar esta vaga? A ação é final e arquiva o processo.')) return;
                      await atualizarStatus(v, 'fechar');
                    }}
                    disabled={busyId === v.id}
                    aria-busy={busyId === v.id}
                    className="rounded bg-zinc-900 text-white px-3 py-1.5 text-sm hover:bg-black disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-zinc-900"
                    aria-label={`Fechar vaga ${v.titulo}`}
                  >
                    Fechar
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}

        <div className="mt-3">
          <button
            onClick={() => navigate('/vagas')}
            className="text-blue-700 underline text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 rounded"
          >
            Ir para “Gerenciamento de Vagas”
          </button>
        </div>
      </section>

      {/* Dados institucionais */}
      <section className="p-4 border rounded bg-white shadow-sm" aria-labelledby="titulo-dados-inst">
        <h2 id="titulo-dados-inst" className="font-semibold mb-2">
          Dados da instituição
        </h2>
        <dl className="grid sm:grid-cols-2 gap-y-2">
          <div>
            <dt className="text-sm text-zinc-600">Nome/Razão Social</dt>
            <dd>{user?.razao_social || user?.name || user?.nome || '—'}</dd>
          </div>
          <div>
            <dt className="text-sm text-zinc-600">Nome fantasia</dt>
            <dd>{user?.nome_fantasia || '—'}</dd>
          </div>
          <div>
            <dt className="text-sm text-zinc-600">CNPJ</dt>
            <dd>
              {revealSensitive ? (user?.cnpj || '—') : maskCNPJ(user?.cnpj)}
              <button
                type="button"
                className="ml-2 text-xs underline focus:outline-none focus:ring-2 focus:ring-blue-600 rounded"
                onClick={() => setRevealSensitive((v) => !v)}
                aria-pressed={revealSensitive}
                aria-label={revealSensitive ? 'Ocultar CNPJ' : 'Mostrar CNPJ'}
              >
                {revealSensitive ? 'Ocultar' : 'Mostrar'}
              </button>
            </dd>
          </div>
          <div>
            <dt className="text-sm text-zinc-600">INEP</dt>
            <dd>{user?.codigo_inep || '—'}</dd>
          </div>
          <div>
            <dt className="text-sm text-zinc-600">E-mail</dt>
            <dd>{user?.email || '—'}</dd>
          </div>
        </dl>
      </section>
    </main>
  );
}
