<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona a coluna deleted_at às tabelas candidatos e instituicoes
     * para suportar soft deletes e manter consistência com a tabela usuarios.
     */
    public function up(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('instituicoes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('instituicoes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
