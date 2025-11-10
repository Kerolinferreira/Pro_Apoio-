<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiencias_profissionais', function (Blueprint $table) {
            $table->id('id_experiencia_profissional');
            $table->unsignedBigInteger('id_candidato');
            $table->integer('idade_aluno')->nullable();
            $table->string('tempo_experiencia', 50)->nullable();
            $table->boolean('interesse_mesma_deficiencia')->default(false);
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->foreign('id_candidato')->references('id_candidato')->on('candidatos')->onDelete('cascade');
            $table->index('id_candidato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiencias_profissionais');
    }
};