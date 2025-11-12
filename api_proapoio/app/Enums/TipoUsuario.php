<?php

namespace App\Enums;

/**
 * Tipos de usuário no sistema.
 */
enum TipoUsuario: string
{
    case CANDIDATO = 'CANDIDATO';
    case INSTITUICAO = 'INSTITUICAO';
}
