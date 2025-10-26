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

  const listaDef = deficiencias.length
    ? deficiencias.map(d => d?.nome).filter(Boolean).join(', ')
    : 'Não informado';

  return (
    <article
      className="border rounded p-3 bg-white shadow-sm"
      aria-label={`Candidato ${nome_completo}`}
      data-testid="candidato-card"
    >
      <h3 className="font-medium">{nome_completo}</h3>
      <p className="text-sm">{escolaridade}</p>
      <p className="text-sm">
        {cidade} - {estado}
      </p>
      <p className="text-sm">
        Deficiências: {listaDef}
      </p>
    </article>
  );
}

export default React.memo(CandidatoCardBase);
