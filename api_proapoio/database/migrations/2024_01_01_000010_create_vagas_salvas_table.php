<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vagas_salvas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_candidato');
            $table->unsignedBigInteger('id_vaga');
            $table->timestamps();
            
            $table->foreign('id_candidato')->references('id_candidato')->on('candidatos')->onDelete('cascade');
            $table->foreign('id_vaga')->references('id_vaga')->on('vagas')->onDelete('cascade');
            
            $table->unique(['id_candidato', 'id_vaga']);
            $table->index('id_candidato');
            $table->index('id_vaga');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vagas_salvas');
    }
};