<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nome', 255);
            $table->string('email', 255)->unique();
            $table->string('senha')->nullable();  // Campo senha para testes de hashing
            $table->string('senha_hash', 255);
            $table->string('tipo_usuario');  // String sem CHECK constraint
            $table->boolean('termos_aceite')->default(false);
            $table->timestamp('data_termos_aceite')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('tipo_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};