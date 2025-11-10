import React from 'react';
import { Link } from 'react-router-dom';

/**
 * @description Define as propriedades públicas do VagaCard.
 */
interface VagaCardProps {
  id: number;
  title: string;
  institution: string;
  city: string;
  regime: 'CLT' | 'ESTAGIO' | 'MEI' | 'TEMPORARIO' | string;
  linkTo: string; // URL para os detalhes da vaga
  // Adicionado isNew e isSaved para possíveis badges futuros, seguindo as boas práticas.
  isNew?: boolean; 
  isSaved?: boolean;
}

/**
 * @description Retorna a classe semântica do badge com base no regime de contratação.
 * @param regime O regime de contratação da vaga.
 */
const getRegimeBadgeClass = (regime: string) => {
  const upperRegime = regime.toUpperCase();
  switch (upperRegime) {
    case 'CLT':
    case 'ESTAGIO':
      return 'badge-green'; // Status de vagas ativas/padrão
    case 'MEI':
    case 'TEMPORARIO':
      return 'badge-yellow'; // Destaque para regimes específicos
    default:
      return 'badge-gray';
  }
};

const VagaCard: React.FC<VagaCardProps> = ({
  id,
  title,
  institution,
  city,
  regime,
  linkTo
}) => {
  const badgeClass = getRegimeBadgeClass(regime);

  // Usamos <Link> para navegação e a classe 'card-simple' para estilo.
  return (
    <Link to={linkTo} aria-label={`Ver detalhes da vaga ${title} na instituição ${institution}`}>
      <article className="card-simple">
        <header className="flex-group-md-row justify-between">
          {/* Título da Vaga - title-md para destaque */}
          <h3 className="title-md mb-xs">{title}</h3>
          {/* Regime de Contratação - badge global */}
          <div className={`badge ${badgeClass}`}>{regime}</div>
        </header>

        {/* Informações da Instituição e Localização */}
        <p className="text-sm text-muted mb-xs">{institution}</p>
        
        {/* Localização */}
        <p className="text-sm text-muted">
          Local: {city}
        </p>
      </article>
    </Link>
  );
};

export default VagaCard;
