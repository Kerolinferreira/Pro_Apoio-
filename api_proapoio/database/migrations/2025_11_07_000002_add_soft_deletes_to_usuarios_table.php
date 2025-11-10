<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona coluna deleted_at para implementar SoftDeletes no model User.
     * Isso previne perda de dados relacionados e permite auditoria de exclusões.
     *
     * Resolve Falha #10: Cascade delete sem soft delete apaga dados de terceiros
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->softDeletes(); // Adiciona coluna deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
