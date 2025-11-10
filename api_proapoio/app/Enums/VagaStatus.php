<?php

namespace App\Enums;

/**
 * Status possíveis para uma Vaga.
 */
enum VagaStatus: string
{
    case ATIVA = 'ATIVA';
    case PAUSADA = 'PAUSADA';
    case FECHADA = 'FECHADA';
}
