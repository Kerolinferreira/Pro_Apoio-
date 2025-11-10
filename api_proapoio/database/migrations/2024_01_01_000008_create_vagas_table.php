<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vagas', function (Blueprint $table) {
            $table->id('id_vaga');
            $table->unsignedBigInteger('id_instituicao');
            $table->enum('status', ['ATIVA', 'PAUSADA', 'FECHADA'])->default('ATIVA');
            $table->integer('aluno_nascimento_mes')->nullable();
            $table->integer('aluno_nascimento_ano')->nullable();
            $table->text('necessidades_descricao')->nullable();
            $table->text('descricao')->nullable();
            $table->integer('carga_horaria_semanal')->nullable();
            $table->string('regime_contratacao', 30)->nullable();
            $table->decimal('valor_remuneracao', 10, 2)->nullable();
            $table->decimal('remuneracao', 10, 2)->nullable();  // Alias para valor_remuneracao
            $table->string('tipo_remuneracao', 30)->nullable();
            $table->string('tipo')->nullable();
            $table->string('modalidade')->nullable();
            $table->string('titulo', 255);  // Campo usado nos testes
            $table->string('titulo_vaga', 255);  // Campo legado
            $table->string('cidade', 120)->nullable();
            $table->char('estado', 2)->nullable();
            $table->timestamp('data_criacao')->useCurrent();
            $table->timestamps();

            $table->foreign('id_instituicao')->references('id_instituicao')->on('instituicoes')->onDelete('cascade');

            $table->index('status');
            $table->index('cidade');
            $table->index('id_instituicao');
            $table->index(['id_instituicao', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vagas');
    }
};