import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import Header from '../components/Header';
import { LoadingSpinner, ErrorAlert, Button } from '../components/ui';

// Tipos
interface Deficiencia {
  id: number;
  nome: string;
}

interface Vaga {
  id: number;
  titulo: string;
  descricao: string;
  tipo_apoio: string;            // Ex.: Presencial, Online
  data_publicacao: string;       // ISO string
  salario: number | null;
  localizacao: string;
  deficiencias: Deficiencia[];   // Lista de deficiências associadas
  necessidades_descricao: string;
  instituicao?: {
    id: number;
    nome_fantasia: string;
  };
}

const DetalhesVagaPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();

  const [vaga, setVaga] = useState<Vaga | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Suposição: usuário logado é candidato
  const isCandidato = true;
  const [isSaved, setIsSaved] = useState(false);
  const [isApplied, setIsApplied] = useState(false);

  useEffect(() => {
    const fetchVaga = async () => {
      if (!id) {
        setError('ID da vaga não fornecido.');
        setLoading(false);
        return;
      }
      try {
        const response = await api.get(`/vagas/${id}`);
        setVaga(response.data as Vaga);
        // Exemplos de estados derivados de backend:
        // if (response.data.status_candidato?.salva) setIsSaved(true);
        // if (response.data.status_candidato?.proposta_enviada) setIsApplied(true);
        setLoading(false);
      } catch (err) {
        console.error('Erro ao buscar detalhes da vaga:', err);
        setError('Não foi possível carregar os detalhes desta vaga.');
        setLoading(false);
      }
    };
    fetchVaga();
  }, [id]);

  const handleApply = () => {
    // TODO: POST /propostas
    alert('Implementar envio de proposta');
    setIsApplied(true);
  };

  const handleSave = () => {
    // TODO: POST/DELETE /candidatos/me/vagas-salvas
    alert(isSaved ? 'Remover vaga salva' : 'Salvar vaga');
    setIsSaved(!isSaved);
  };

  const formatSalary = (salary: number | null) =>
    salary != null ? `R$ ${salary.toFixed(2).replace('.', ',')}` : 'A combinar';

  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorAlert message={error} />;
  if (!vaga) return <ErrorAlert message="Vaga não encontrada." />;

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">
        <div className="bg-white shadow-xl rounded-lg p-8">
          {/* Título e Instituição */}
          <div className="border-b pb-4 mb-6">
            <h1 className="text-4xl font-extrabold text-gray-900 leading-tight">
              {vaga.titulo}
            </h1>
            {vaga.instituicao && (
              <Link
                to={`/instituicoes/${vaga.instituicao.id}`}
                className="text-blue-600 hover:text-blue-800 text-lg mt-1 inline-block"
              >
                {vaga.instituicao.nome_fantasia}
              </Link>
            )}
          </div>

          {/* Informações Básicas */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 text-gray-700">
            <div>
              <p className="text-sm font-medium text-gray-500">Tipo de Apoio</p>
              <p className="text-lg font-semibold">{vaga.tipo_apoio}</p>
            </div>
            <div>
              <p className="text-sm font-medium text-gray-500">Localização</p>
              <p className="text-lg font-semibold">{vaga.localizacao}</p>
            </div>
            <div>
              <p className="text-sm font-medium text-gray-500">Remuneração Estimada</p>
              <p className="text-lg font-semibold text-green-700">{formatSalary(vaga.salario)}</p>
            </div>
            <div>
              <p className="text-sm font-medium text-gray-500">Publicado em</p>
              <p className="text-lg font-semibold">
                {new Date(vaga.data_publicacao).toLocaleDateString()}
              </p>
            </div>
          </div>

          {/* Requisitos Específicos do Aluno */}
          <section className="mt-8 pt-6 border-t border-gray-100">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Requisitos Específicos do Aluno</h2>

            {/* Deficiências */}
            <div className="mb-6">
              <h3 className="text-lg font-semibold text-gray-700 mb-2">Deficiências Associadas</h3>
              {Array.isArray(vaga.deficiencias) && vaga.deficiencias.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {vaga.deficiencias.map((def) => (
                    <span
                      key={def.id}
                      className="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full"
                    >
                      {def.nome}
                    </span>
                  ))}
                </div>
              ) : (
                <p className="text-gray-500 italic">Nenhuma deficiência específica listada.</p>
              )}
            </div>

            {/* Descrição do Apoio */}
            <div>
              <h3 className="text-lg font-semibold text-gray-700 mb-2">
                Descrição Detalhada das Necessidades (Tarefas/Apoio)
              </h3>
              <div className="p-4 bg-gray-50 rounded-lg whitespace-pre-wrap">
                <p className="text-gray-700">
                  {vaga.necessidades_descricao || 'Nenhuma descrição detalhada de necessidades fornecida.'}
                </p>
              </div>
            </div>
          </section>

          {/* Descrição Geral da Vaga */}
          <section className="mt-8 pt-6 border-t border-gray-100">
            <h2 className="text-2xl font-bold text-gray-800 mb-4">Descrição da Oportunidade</h2>
            <div className="whitespace-pre-wrap text-gray-700">{vaga.descricao}</div>
          </section>

          {/* Ações do Candidato */}
          {isCandidato && (
            <div className="mt-8 pt-6 border-t border-gray-100 flex justify-end gap-4">
              <Button
                onClick={handleSave}
                disabled={isApplied}
                className={`bg-white text-blue-600 border border-blue-600 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 ${
                  isApplied ? 'hidden' : ''
                }`}
              >
                {isSaved ? 'Vaga Salva' : 'Salvar Vaga'}
              </Button>

              <Button
                onClick={handleApply}
                disabled={isApplied}
                className="text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
              >
                {isApplied ? 'Proposta Enviada' : 'Candidatar-se Agora'}
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default DetalhesVagaPage;
