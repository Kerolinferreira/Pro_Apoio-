<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id('id_endereco');
            $table->string('cep', 9)->nullable();
            $table->string('logradouro', 255)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->char('estado', 2)->nullable();
            $table->string('numero', 10)->nullable();
            $table->string('complemento', 50)->nullable();
            $table->string('ponto_referencia', 100)->nullable();
            $table->timestamps();

            $table->index('cep');
            $table->index('cidade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};