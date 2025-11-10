<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidato_deficiencia', function (Blueprint $table) {
            $table->unsignedBigInteger('id_candidato');
            $table->unsignedBigInteger('id_deficiencia');

            $table->primary(['id_candidato', 'id_deficiencia']);

            $table->foreign('id_candidato')
                  ->references('id_candidato')
                  ->on('candidatos')
                  ->onDelete('cascade');

            $table->foreign('id_deficiencia')
                  ->references('id_deficiencia')
                  ->on('deficiencias')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidato_deficiencia');
    }
};
