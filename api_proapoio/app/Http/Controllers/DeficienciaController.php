<?php

namespace App\Http\Controllers;

use App\Models\Deficiencia;
use Illuminate\Http\Request;

/**
 * Controller para gerenciar deficiências.
 */
class DeficienciaController extends Controller
{
    /**
     * Lista todas as deficiências disponíveis.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $deficiencias = Deficiencia::all(['id_deficiencia', 'nome']);

        // Retorna ambos os campos id e id_deficiencia para compatibilidade com diferentes componentes do frontend
        $data = $deficiencias->map(function ($deficiencia) {
            return [
                'id' => $deficiencia->id_deficiencia,
                'id_deficiencia' => $deficiencia->id_deficiencia,
                'nome' => $deficiencia->nome,
            ];
        });

        return response()->json($data);
    }
}
