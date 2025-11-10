<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiencias_profissionais_deficiencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_experiencia_profissional');
            $table->unsignedBigInteger('id_deficiencia');

            $table->primary(['id_experiencia_profissional', 'id_deficiencia'], 'exp_def_primary');

            $table->foreign('id_experiencia_profissional', 'exp_def_exp_fk')
                  ->references('id_experiencia_profissional')
                  ->on('experiencias_profissionais')
                  ->onDelete('cascade');

            $table->foreign('id_deficiencia', 'exp_def_def_fk')
                  ->references('id_deficiencia')
                  ->on('deficiencias')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiencias_profissionais_deficiencias');
    }
};