<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatos', function (Blueprint $table) {
            $table->id('id_candidato');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_endereco')->nullable();
            $table->string('nome_completo', 255);
            $table->string('cpf', 255);  // Armazena sem formatação via mutator
            $table->string('telefone', 255)->nullable();  // Armazena sem formatação via mutator
            $table->date('data_nascimento')->nullable();
            $table->string('foto_url', 255)->nullable();  // Campo legado
            $table->string('foto_perfil_url', 255)->nullable();  // Campo novo
            $table->string('link_perfil', 255)->nullable();
            $table->string('escolaridade', 100)->nullable();  // Campo usado nos testes
            $table->string('nivel_escolaridade', 50)->nullable();
            $table->string('curso_superior', 100)->nullable();
            $table->string('instituicao_ensino', 100)->nullable();
            $table->enum('status', ['ATIVO', 'INATIVO'])->default('INATIVO');
            $table->timestamps();

            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_endereco')->references('id_endereco')->on('enderecos')->onDelete('set null');

            $table->index('cpf');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};