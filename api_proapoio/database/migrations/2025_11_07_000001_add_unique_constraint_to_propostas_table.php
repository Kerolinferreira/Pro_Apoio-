<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona unique constraint para evitar que um candidato se candidate
     * múltiplas vezes à mesma vaga. Isso resolve a falha crítica #6 identificada
     * na análise de Business Logic Flaws.
     */
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            // Adiciona unique constraint composto para (id_candidato, id_vaga)
            // Isso garante que cada candidato possa ter no máximo 1 proposta por vaga
            $table->unique(['id_candidato', 'id_vaga'], 'unique_candidato_vaga_proposta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropUnique('unique_candidato_vaga_proposta');
        });
    }
};
