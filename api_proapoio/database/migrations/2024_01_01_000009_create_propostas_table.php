<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Propostas', function (Blueprint $table) {
            $table->id('id_proposta');
            $table->unsignedBigInteger('id_vaga');
            $table->unsignedBigInteger('id_candidato');
            $table->enum('iniciador', ['CANDIDATO', 'INSTITUICAO']);
            $table->enum('status', ['ENVIADA', 'ACEITA', 'RECUSADA'])->default('ENVIADA');
            $table->text('mensagem');
            $table->text('mensagem_resposta')->nullable();
            $table->timestamp('data_envio');
            $table->timestamp('data_resposta')->nullable();
            $table->timestamps();
            
            $table->foreign('id_vaga')->references('id_vaga')->on('Vagas')->onDelete('cascade');
            $table->foreign('id_candidato')->references('id_candidato')->on('Candidatos')->onDelete('cascade');
            
            $table->index('id_vaga');
            $table->index('id_candidato');
            $table->index('status');
            $table->index(['id_candidato', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Propostas');
    }
};