<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instituicoes', function (Blueprint $table) {
            $table->id('id_instituicao');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_endereco')->nullable();
            $table->string('cnpj', 14)->unique();
            $table->string('razao_social', 255);
            $table->string('nome_fantasia', 255);
            $table->string('codigo_inep', 20)->nullable();
            $table->string('tipo_instituicao', 50)->nullable();
            $table->json('niveis_oferecidos')->nullable();
            $table->string('nome_responsavel', 100)->nullable();
            $table->string('funcao_responsavel', 100)->nullable();
            $table->string('email_corporativo', 100)->nullable();
            $table->string('telefone_fixo', 20)->nullable();
            $table->string('celular_corporativo', 20)->nullable();
            $table->timestamps();

            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_endereco')->references('id_endereco')->on('enderecos')->onDelete('set null');

            $table->index('cnpj');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instituicoes');
    }
};