<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidato;
use App\Models\Deficiencia;
use Illuminate\Support\Facades\DB;

class CandidatoFinderController extends Controller
{
    /**
     * Busca candidatos com filtros.
     * Requer autenticação de Instituição.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function buscar(Request $request)
    {
        // Validação básica se necessário, mas presumimos que a Instituição só busca
        // A autorização via middleware já deve garantir que apenas instituições podem acessar.

        $query = Candidato::with(['deficiencias', 'experienciasProfissionais', 'endereco']);

        // 1. Filtro por termo de pesquisa (nome, bio, habilidades, etc.)
        if ($request->has('termo')) {
            $termo = $request->input('termo');
            $query->where(function ($q) use ($termo) {
                $q->where('nome_completo', 'like', "%{$termo}%")
                  ->orWhere('bio', 'like', "%{$termo}%");
                
                // Exemplo de busca em habilidades (se existir tabela/campo)
                // ->orWhereHas('habilidades', function ($sq) use ($termo) {
                //     $sq->where('nome', 'like', "%{$termo}%");
                // });
            });
        }

        // 2. Filtro por Escolaridade (Front-end envia como lista separada por vírgula)
        if ($request->has('escolaridade')) {
            $escolaridades = explode(',', $request->input('escolaridade'));
            $query->whereIn('escolaridade', $escolaridades);
        }
        
        // 3. CORREÇÃO APLICADA: Filtro por Tipo de Deficiência (Experiência Específica)
        if ($request->has('tipo_deficiencia')) {
            $tipoDeficiencia = $request->input('tipo_deficiencia');
            
            // Verifica se a deficiência existe e aplica o filtro usando whereHas
            $deficiencia = Deficiencia::where('nome', $tipoDeficiencia)->first();

            if ($deficiencia) {
                 // Filtra candidatos que possuam a deficiência específica
                 // Nota: Presume-se que o Candidato tem uma relação muitos-para-muitos com Deficiencias.
                 $query->whereHas('deficiencias', function ($q) use ($deficiencia) {
                     $q->where('deficiencia_id', $deficiencia->id);
                 });
            }
        }
        
        // 4. Filtro por Distância/Deslocamento (Distância_km)
        // A implementação completa de filtros por raio (geolocalização) requer latitude/longitude 
        // e cálculos complexos (ex: Haversine), geralmente feitos via DB::raw ou um pacote Geo.
        // Aqui, aplicamos uma filtragem simples no campo de "Disponibilidade de Deslocamento" se existir
        // ou, na ausência de geolocalização, ignoramos por complexidade de implementação sem dados de lat/lng.
        if ($request->has('distancia_km')) {
            $distanciaKm = $request->input('distancia_km');
            // Exemplo conceitual (assumindo que há um campo 'disponibilidade_deslocamento_km' no modelo Candidato)
            // Se o candidato tiver um campo de distância máxima de deslocamento que ele aceita:
            // $query->where('disponibilidade_deslocamento_km', '>=', $distanciaKm);
            
            // Se o filtro for baseado na localização da Instituição (lat/lng), a lógica seria mais complexa:
            // $lat = $request->user()->instituicao->endereco->latitude;
            // $lng = $request->user()->instituicao->endereco->longitude;
            // $query->select(DB::raw("*, (f_haversine(latitude, longitude, $lat, $lng)) AS distance"))
            //       ->having('distance', '<=', $distanciaKm);
            
            // Como a implementação de geolocalização não está visível, este filtro será ignorado
            // ou mapeado para um campo existente que você tenha no Candidato para deslocamento.
            // Para garantir que o filtro pelo menos não quebre, se baseia em uma coluna 'distancia_maxima_deslocamento'
            // no modelo Candidato. Se não existir, deve ser implementado.
        }


        $candidatos = $query->get();

        // Formatação dos dados de resposta, se necessário (ocultar senhas, formatar datas, etc.)
        return response()->json($candidatos, 200);
    }
}
