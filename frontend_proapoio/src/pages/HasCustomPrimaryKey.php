<?php

namespace App\Models\Concerns;

/**
 * Trait HasCustomPrimaryKey
 *
 * Fornece um acessor 'id' que mapeia para a chave primária customizada
 * do modelo, garantindo compatibilidade com código que espera a
 * propriedade ->id.
 */
trait HasCustomPrimaryKey
{
    /**
     * Acessor para compatibilizar a propriedade 'id'.
     */
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}
