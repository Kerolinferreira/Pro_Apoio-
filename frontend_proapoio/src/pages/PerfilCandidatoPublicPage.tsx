import { useParams } from 'react-router-dom';
import { useEffect, useState } from 'react';
import api from '../services/api';

/**
 * Página de visualização pública do perfil de um candidato.
 *
 * Exibe o identificador do candidato na URL e serve de placeholder para
 * apresentar informações públicas do candidato (sem dados de contato).
 */
export default function PerfilCandidatoPublicPage() {
  const { id } = useParams<{ id: string }>();
  const [candidato, setCandidato] = useState<any>(null);
  useEffect(() => {
    async function fetchCandidato() {
      if (!id) return;
      try {
        const resp = await api.get(`/candidatos/public/${id}`);
        setCandidato(resp.data);
      } catch {
        setCandidato(null);
      }
    }
    fetchCandidato();
  }, [id]);
  return (
    <div className="p-4">
      <h2 className="text-xl font-bold">Perfil Público do Candidato</h2>
      {!candidato ? (
        <p className="mt-4">Carregando dados...</p>
      ) : (
        <div className="mt-4 space-y-2">
          <p className="font-semibold text-lg">{candidato.nome}</p>
          <p>Cidade: {candidato.cidade}</p>
          <p>Escolaridade: {candidato.escolaridade}{candidato.nome_curso ? ` - ${candidato.nome_curso}` : ''}</p>
          <p>Instituição de Ensino: {candidato.nome_instituicao_ensino}</p>
        </div>
      )}
    </div>
  );
}