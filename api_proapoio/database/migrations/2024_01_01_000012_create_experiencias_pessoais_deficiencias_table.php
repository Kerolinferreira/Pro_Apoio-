<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CORREÇÃO P6: Criar tabela pivot para associar deficiências a experiências pessoais
 * Permite que candidatos indiquem com quais deficiências têm experiência pessoal
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiencias_pessoais_deficiencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_experiencia_pessoal');
            $table->unsignedBigInteger('id_deficiencia');

            $table->primary(['id_experiencia_pessoal', 'id_deficiencia'], 'exp_pes_def_primary');

            $table->foreign('id_experiencia_pessoal', 'exp_pes_def_exp_fk')
                  ->references('id_experiencia_pessoal')
                  ->on('experiencias_pessoais')
                  ->onDelete('cascade');

            $table->foreign('id_deficiencia', 'exp_pes_def_def_fk')
                  ->references('id_deficiencia')
                  ->on('deficiencias')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiencias_pessoais_deficiencias');
    }
};
