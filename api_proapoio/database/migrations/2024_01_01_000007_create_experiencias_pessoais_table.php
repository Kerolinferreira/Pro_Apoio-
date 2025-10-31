<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ExperienciasPessoais', function (Blueprint $table) {
            $table->id('id_experiencia_pessoal');
            $table->unsignedBigInteger('id_candidato');
            $table->boolean('interesse_atuar')->default(false);
            $table->text('descricao')->nullable();
            $table->timestamps();
            
            $table->foreign('id_candidato')->references('id_candidato')->on('Candidatos')->onDelete('cascade');
            $table->index('id_candidato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ExperienciasPessoais');
    }
};