import React, { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { Button, LoadingSpinner, ErrorAlert } from '../components/ui';

interface VagaSalva {
  id: number;
  vaga: {
    id: number;
    titulo_vaga: string;
    cidade: string;
    regime_contratacao: string;
    instituicao: { nome_fantasia: string };
  };
}

export default function VagasSalvasPage() {
  const [vagas, setVagas] = useState<VagaSalva[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [removingId, setRemovingId] = useState<number | null>(null);
  const [lastRemoved, setLastRemoved] = useState<VagaSalva | null>(null);
  const liveRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    let mounted = true;
    async function fetchVagas() {
      setLoading(true);
      setError(null);
      try {
        const response = await api.get('/candidatos/me/vagas-salvas');
        const payload = response.data?.data ?? response.data;
        if (mounted) setVagas(Array.isArray(payload) ? payload : []);
      } catch {
        if (mounted) setError('Não foi possível carregar as vagas salvas.');
      } finally {
        if (mounted) setLoading(false);
      }
    }
    fetchVagas();
    return () => {
      mounted = false;
    };
  }, []);

  function announce(msg: string) {
    if (liveRef.current) {
      liveRef.current.textContent = msg;
      setTimeout(() => {
        if (liveRef.current) liveRef.current.textContent = '';
      }, 1500);
    }
  }

  async function remover(vagaId: number) {
    const alvo = vagas.find((v) => v.vaga.id === vagaId) || null;
    setLastRemoved(alvo);
    setRemovingId(vagaId);
    // Remoção otimista
    setVagas((prev) => prev.filter((v) => v.vaga.id !== vagaId));
    try {
      await api.delete(`/vagas/${vagaId}/salvar`);
      announce('Vaga removida dos salvos.');
    } catch {
      // Reverte em caso de erro
      if (alvo) setVagas((prev) => [alvo, ...prev]);
      announce('Falha ao remover a vaga.');
    } finally {
      setRemovingId(null);
    }
  }

  async function desfazer() {
    const item = lastRemoved;
    if (!item) return;
    try {
      await api.post(`/vagas/${item.vaga.id}/salvar`);
      setVagas((prev) => [item, ...prev]);
      setLastRemoved(null);
      announce('Vaga restaurada aos salvos.');
    } catch {
      announce('Não foi possível desfazer.');
    }
  }

  return (
    <main className="p-4 max-w-5xl mx-auto" aria-labelledby="titulo-vagas-salvas">
      <div ref={liveRef} className="sr-only" aria-live="polite" />

      <h1 id="titulo-vagas-salvas" className="text-2xl font-extrabold mb-4">
        Vagas salvas
      </h1>

      {loading && (
        <div className="py-10">
          <LoadingSpinner />
        </div>
      )}

      {error && !loading && <ErrorAlert message={error} />}

      {!loading && !error && vagas.length === 0 && (
        <div className="rounded border p-6 text-center">
          <p>Você ainda não salvou nenhuma vaga.</p>
          <p className="text-sm text-zinc-600 mt-1">
            Veja{' '}
            <Link to="/vagas" className="underline text-blue-600">
              vagas disponíveis
            </Link>{' '}
            e salve as que interessarem.
          </p>
        </div>
      )}

      {!loading && !error && vagas.length > 0 && (
        <ul className="space-y-2" role="list">
          {vagas.map((v) => (
            <li
              key={v.id}
              className="border p-3 rounded flex justify-between items-start gap-3 bg-white shadow-sm"
            >
              <div>
                <h2 className="font-semibold text-zinc-900">{v.vaga.titulo_vaga}</h2>
                <p className="text-sm text-zinc-700">
                  {v.vaga.instituicao?.nome_fantasia || ''} • {v.vaga.cidade}
                </p>
                <p className="text-sm text-zinc-700">
                  Regime: {v.vaga.regime_contratacao}
                </p>
              </div>
              <div className="flex items-center gap-2">
                <Link
                  to={`/vagas/${v.vaga.id}`}
                  className="rounded bg-zinc-200 px-3 py-1.5 text-sm hover:bg-zinc-300"
                >
                  Ver detalhes
                </Link>
                <Button
                  onClick={() => remover(v.vaga.id)}
                  disabled={removingId === v.vaga.id}
                  className="bg-red-700 text-white px-3 py-1.5 text-sm hover:bg-red-800 disabled:opacity-60"
                  aria-busy={removingId === v.vaga.id}
                >
                  Remover
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}

      {lastRemoved && (
        <div
          className="mt-3 rounded border border-amber-200 bg-amber-50 p-2 text-amber-900 text-sm flex items-center justify-between"
          role="status"
        >
          <span>Vaga removida.</span>
          <Button
            onClick={desfazer}
            className="underline text-amber-900 hover:text-amber-700 bg-transparent p-0"
          >
            Desfazer
          </Button>
        </div>
      )}
    </main>
  );
}
