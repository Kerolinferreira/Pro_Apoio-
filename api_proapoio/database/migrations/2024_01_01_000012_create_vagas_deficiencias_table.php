<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vagas_deficiencias', function (Blueprint $table) {
            $table->unsignedBigInteger('id_vaga');
            $table->unsignedBigInteger('id_deficiencia');
            
            $table->primary(['id_vaga', 'id_deficiencia']);
            
            $table->foreign('id_vaga')->references('id_vaga')->on('vagas')->onDelete('cascade');
            $table->foreign('id_deficiencia')->references('id_deficiencia')->on('deficiencias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vagas_deficiencias');
    }
};