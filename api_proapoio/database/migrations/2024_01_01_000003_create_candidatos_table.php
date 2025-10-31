<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Candidatos', function (Blueprint $table) {
            $table->id('id_candidato');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_endereco')->nullable();
            $table->string('nome_completo', 255);
            $table->string('cpf', 11)->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('link_perfil', 255)->nullable();
            $table->string('nivel_escolaridade', 50)->nullable();
            $table->string('curso_superior', 100)->nullable();
            $table->string('instituicao_ensino', 100)->nullable();
            $table->string('foto_url', 255)->nullable();
            $table->enum('status', ['ATIVO', 'INATIVO'])->default('ATIVO');
            $table->timestamps();
            
            $table->foreign('id_usuario')->references('id_usuario')->on('Usuarios')->onDelete('cascade');
            $table->foreign('id_endereco')->references('id_endereco')->on('Enderecos')->onDelete('set null');
            
            $table->index('cpf');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Candidatos');
    }
};