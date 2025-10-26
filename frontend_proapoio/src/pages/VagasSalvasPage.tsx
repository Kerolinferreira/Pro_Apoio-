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

  // Acessibilidade: regiões vivas e gestão de foco
  const liveRef = useRef<HTMLDivElement>(null);
  const countLiveRef = useRef<HTMLDivElement>(null);
  const h1Ref = useRef<HTMLHeadingElement>(null);
  const itemRefs = useRef<Record<number, HTMLAnchorElement | null>>({}); // foco no título da vaga

  useEffect(() => {
    let mounted = true;
    async function fetchVagas() {
      setLoading(true);
      setError(null);
      try {
        const response = await api.get('/candidatos/me/vagas-salvas');
        const payload = response.data?.data ?? response.data;
        const fetchedVagas = Array.isArray(payload) ? payload : [];
        
        if (mounted) {
          setVagas(fetchedVagas);
          // 1. ANUNCIA A CONTAGEM INICIAL
          announceCount(fetchedVagas.length, true); 
        }
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
      // REMOVIDO: setTimeout para limpar o texto. A região viva deve reter a última
      // mensagem até ser substituída, o que é suficiente para o NVDA/JAWS.
    }
  }

  // Adicionado parâmetro 'isInitial' para diferenciar o anúncio
  function announceCount(total: number, isInitial = false) {
    const msg = isInitial 
      ? `Você possui ${total} vagas salvas.` 
      : `Total de vagas salvas: ${total}`;
      
    if (countLiveRef.current) {
      countLiveRef.current.textContent = msg;
      // REMOVIDO: setTimeout para limpar o texto.
    }
  }

  async function remover(vagaId: number) {
    const idx = vagas.findIndex((v) => v.vaga.id === vagaId);
    const alvo = idx >= 0 ? vagas[idx] : null;

    if (!alvo) return;
    
    setLastRemoved(alvo);
    setRemovingId(vagaId);

    // Calcula próximo alvo de foco antes da remoção otimista
    const proximoItem =
      idx + 1 < vagas.length
        ? vagas[idx + 1]?.vaga.id // Foca no próximo
        : idx > 0
        ? vagas[idx - 1]?.vaga.id // Foca no anterior
        : null;

    // Remoção otimista
    setVagas((prev) => {
      const novo = prev.filter((v) => v.vaga.id !== vagaId);
      announceCount(novo.length); // Anuncia a nova contagem
      return novo;
    });

    try {
      await api.delete(`/vagas/${vagaId}/salvar`);
      
      // Anúncio de sucesso
      announce(`Vaga ${alvo.vaga.titulo_vaga} removida dos salvos.`);
      
      // Move foco (postergado para garantir a atualização do DOM)
      setTimeout(() => {
        if (proximoItem && itemRefs.current[proximoItem]) {
          itemRefs.current[proximoItem]?.focus();
        } else {
          // Se for o último item, volta o foco para o título da página.
          h1Ref.current?.focus();
        }
      }, 0);
    } catch {
      // Reverte em caso de erro
      setVagas((prev) => {
        // Usa a lógica de inserção inicial do item para garantir que ele volte corretamente à lista
        const novo = [alvo, ...prev.filter(v => v.vaga.id !== vagaId)];
        announceCount(novo.length);
        return novo;
      });
      announce('Falha ao remover a vaga.');
    } finally {
      setRemovingId(null);
    }
  }

  async function desfazer() {
    const item = lastRemoved;
    if (!item) return;
    
    setLastRemoved(null); // Remove o banner de desfazer imediatamente

    try {
      await api.post(`/vagas/${item.vaga.id}/salvar`);
      
      // Restaura o item
      setVagas((prev) => {
        // Encontra o ponto de inserção para manter a ordem original
        const vagaOriginal = vagas.find(v => v.vaga.id === item.vaga.id);
        if (vagaOriginal) {
           // Uma inserção simples no topo é mais segura para a lógica de estado do que tentar recriar a ordem original
           const novo = [item, ...prev];
           announceCount(novo.length);
           return novo;
        }

        const novo = [item, ...prev];
        announceCount(novo.length);
        return novo;
      });
      
      announce('Vaga restaurada aos salvos.');
      
      // Move o foco para o item restaurado
      setTimeout(() => itemRefs.current[item.vaga.id]?.focus(), 0);
    } catch {
      announce('Não foi possível desfazer. Tente novamente mais tarde.');
      // Mantém o item no estado de lastRemoved para que a pessoa possa tentar novamente (opcional, pode-se reverter)
      setLastRemoved(item); 
    }
  }

  return (
    <main
      className="p-4 max-w-5xl mx-auto"
      aria-labelledby="titulo-vagas-salvas"
      role="main"
    >
      {/* Regiões vivas: 'polite' para atualizações de status e contagem de itens. */}
      <div ref={liveRef} className="sr-only" aria-live="polite" />
      <div ref={countLiveRef} className="sr-only" aria-live="polite" />
      
      <h1
        id="titulo-vagas-salvas"
        className="text-2xl font-extrabold mb-4"
        tabIndex={-1}
        ref={h1Ref}
      >
        Vagas salvas
      </h1>

      {loading && (
        <div className="py-10" role="status" aria-live="polite">
          <LoadingSpinner />
        </div>
      )}

      {error && !loading && <ErrorAlert message={error} />}

      {!loading && !error && vagas.length === 0 && (
        <section
          className="rounded border p-6 text-center bg-white"
          aria-labelledby="estado-vazio"
        >
          <h2 id="estado-vazio" className="text-lg font-semibold mb-1">
            Nenhuma vaga salva
          </h2>
          <p className="text-sm text-zinc-700">
            Você ainda não salvou nenhuma vaga.
          </p>
          <p className="text-sm text-zinc-600 mt-1">
            Acesse{' '}
            <Link to="/vagas" className="underline text-blue-700">
              vagas disponíveis
            </Link>{' '}
            e clique no ícone de estrela para guardar as que interessarem.
          </p>
        </section>
      )}

      {!loading && !error && vagas.length > 0 && (
        <section aria-label="Lista de vagas salvas">
          {/* REMOVIDO: O <p className="sr-only"> estático. A contagem é comunicada dinamicamente pelo countLiveRef. */}
          
          <ul className="space-y-2" role="list">
            {vagas.map((v) => {
              const headingId = `vaga-salva-${v.vaga.id}-titulo`;
              return (
                <li
                  key={v.id}
                  className="border p-3 rounded bg-white shadow-sm"
                  role="article"
                  aria-labelledby={headingId}
                >
                  <div className="flex justify-between items-start gap-3">
                    <div>
                      <h2 id={headingId} className="font-semibold text-zinc-900">
                        <Link
                          to={`/vagas/${v.vaga.id}`}
                          className="hover:underline focus:underline outline-none focus:outline-offset-2 focus:outline-2 focus:outline-blue-600 rounded"
                          ref={(el) => (itemRefs.current[v.vaga.id] = el)}
                          // REMOVIDO: O aria-label redundante. O leitor de tela lerá o texto visível do link, que é o título da vaga.
                          // Se necessário rotular, use: aria-label={`Detalhes da vaga: ${v.vaga.titulo_vaga}`}
                        >
                          {v.vaga.titulo_vaga}
                        </Link>
                      </h2>
                      <p className="text-sm text-zinc-700">
                        {v.vaga.instituicao?.nome_fantasia || 'Instituição não informada'} • {v.vaga.cidade}
                      </p>
                      <p className="text-sm text-zinc-700">
                        Regime: {v.vaga.regime_contratacao}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Link
                        to={`/vagas/${v.vaga.id}`}
                        className="rounded bg-zinc-200 px-3 py-1.5 text-sm hover:bg-zinc-300 focus:outline-none focus:ring-2 focus:ring-blue-600"
                        aria-label={`Ver detalhes da vaga ${v.vaga.titulo_vaga}`}
                      >
                        Ver detalhes
                      </Link>
                      <Button
                        onClick={() => remover(v.vaga.id)}
                        disabled={removingId === v.vaga.id}
                        className="bg-red-700 text-white px-3 py-1.5 text-sm hover:bg-red-800 disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-red-600"
                        aria-busy={removingId === v.vaga.id}
                        aria-disabled={removingId === v.vaga.id}
                        aria-label={`Remover a vaga ${v.vaga.titulo_vaga} dos salvos`}
                        title={`Remover a vaga ${v.vaga.titulo_vaga} dos salvos`}
                      >
                        Remover
                      </Button>
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        </section>
      )}

      {lastRemoved && (
        <div
          className="mt-3 rounded border border-amber-200 bg-amber-50 p-2 text-amber-900 text-sm flex items-center justify-between"
          role="status" // Anuncia a mudança de forma cortês (polite)
          aria-live="polite"
        >
          <span>Vaga **{lastRemoved.vaga.titulo_vaga}** removida.</span>
          <Button
            onClick={desfazer}
            className="underline text-amber-900 hover:text-amber-700 bg-transparent p-0 focus:outline-none focus:ring-2 focus:ring-amber-600"
            aria-label={`Desfazer a remoção da vaga ${lastRemoved.vaga.titulo_vaga}`}
            title="Desfazer a remoção da vaga"
          >
            Desfazer
          </Button>
        </div>
      )}
    </main>
  );
}