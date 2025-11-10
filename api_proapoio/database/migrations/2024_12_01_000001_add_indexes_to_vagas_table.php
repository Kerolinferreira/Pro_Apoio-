<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona índices para melhorar performance em filtros de busca
     */
    public function up(): void
    {
        Schema::table('vagas', function (Blueprint $table) {
            // Índices para campos frequentemente usados em filtros
            $table->index('tipo');
            $table->index('regime_contratacao');
            $table->index('modalidade');
            $table->index('estado'); // Já existe mas incluímos por completude

            // Índice composto para busca por tipo + status
            $table->index(['tipo', 'status']);

            // Índice composto para busca por estado + cidade + status
            $table->index(['estado', 'cidade', 'status']);
        });
    }

    /**
     * Reverte as mudanças
     */
    public function down(): void
    {
        Schema::table('vagas', function (Blueprint $table) {
            $table->dropIndex(['vagas_tipo_index']);
            $table->dropIndex(['vagas_regime_contratacao_index']);
            $table->dropIndex(['vagas_modalidade_index']);
            $table->dropIndex(['vagas_estado_index']);
            $table->dropIndex(['vagas_tipo_status_index']);
            $table->dropIndex(['vagas_estado_cidade_status_index']);
        });
    }
};
