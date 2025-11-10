<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona soft deletes à tabela vagas para permitir recuperação de registros
     */
    public function up(): void
    {
        Schema::table('vagas', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverte as mudanças
     */
    public function down(): void
    {
        Schema::table('vagas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
