<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeficienciaSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $deficiencias = [
            ['nome' => 'Visual', 'descricao' => 'Deficiência Visual'],
            ['nome' => 'Auditiva', 'descricao' => 'Deficiência Auditiva'],
            ['nome' => 'Física', 'descricao' => 'Deficiência Física'],
            ['nome' => 'Intelectual', 'descricao' => 'Deficiência Intelectual'],
            ['nome' => 'Múltipla', 'descricao' => 'Deficiência Múltipla'],
            ['nome' => 'Psicossocial', 'descricao' => 'Deficiência Psicossocial'],
            ['nome' => 'Autismo', 'descricao' => 'Transtorno do Espectro Autista (TEA)'],
            ['nome' => 'TDAH', 'descricao' => 'Transtorno de Déficit de Atenção e Hiperatividade'],
            ['nome' => 'Dislexia', 'descricao' => 'Dislexia'],
            ['nome' => 'Síndrome de Down', 'descricao' => 'Síndrome de Down'],
        ];

        foreach ($deficiencias as $deficiencia) {
            DB::table('deficiencias')->updateOrInsert(
                ['nome' => $deficiencia['nome']],
                [
                    'nome' => $deficiencia['nome'],
                    'descricao' => $deficiencia['descricao'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
