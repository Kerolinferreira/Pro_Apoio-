<?php

namespace App\Enums;

/**
 * Status possíveis para uma Proposta.
 */
enum PropostaStatus: string
{
    case ENVIADA = 'ENVIADA';
    case ACEITA = 'ACEITA';
    case RECUSADA = 'RECUSADA';
}
