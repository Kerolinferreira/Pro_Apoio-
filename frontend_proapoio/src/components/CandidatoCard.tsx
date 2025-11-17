import React from 'react';

export type CandidatoPublico = {
  id?: number;
  nome_completo: string;
  escolaridade?: string;
  cidade?: string;
  estado?: string;
  deficiencias?: { nome: string }[];
};

type Props = { candidato: CandidatoPublico };

function CandidatoCardBase({ candidato }: Props) {
  const {
    nome_completo,
    escolaridade = 'Não informado',
    cidade = 'Cidade não informada',
    estado = 'UF',
    deficiencias = [],
  } = candidato;

  // Formata a lista de deficiências para exibição
  const listaDef = deficiencias.length
    ? deficiencias.map(d => d?.nome).filter(Boolean).join(', ')
    : 'Não informado';

  // O card usa a classe 'card-simple' (fundo, borda, sombra e padding definidos globalmente)
  return (
    <article
      className="card-simple"
      aria-label={`Candidato ${nome_completo}`}
      data-testid="candidato-card"
    >
      {/* Classe title-md para o nome e mb-xs para espaçamento */}
      <h3 className="title-md mb-xs">{nome_completo}</h3>
      
      {/* text-sm e text-muted para informações secundárias */}
      <p className="text-sm text-muted">{escolaridade}</p>
      <p className="text-sm text-muted">
        {cidade} - {estado}
      </p>
      <p className="text-sm text-muted">
        Experiência com Deficiências: {listaDef}
      </p>
    </article>
  );
}

export default React.memo(CandidatoCardBase);