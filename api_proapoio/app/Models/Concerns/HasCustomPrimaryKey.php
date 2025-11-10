<?php

namespace App\Models\Concerns;

trait HasCustomPrimaryKey
{
    public function getIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}