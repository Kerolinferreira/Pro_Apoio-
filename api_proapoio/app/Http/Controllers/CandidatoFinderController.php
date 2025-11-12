<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidato;
use App\Models\Deficiencia;
use App\Models\Instituicao;
use App\Models\Endereco;
use App\Enums\TipoUsuario;
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
        $user = $request->user();

        // Garante que apenas Instituições podem usar a busca (case-insensitive para robustez)
        if (strtoupper($user->tipo_usuario ?? '') !== TipoUsuario::INSTITUICAO->value) {
            return $this->forbidden('Acesso negado. Apenas instituições podem buscar candidatos.');
        }

        $query = Candidato::with(['deficiencias', 'experienciasProfissionais', 'endereco']);

        // 1. Filtro por termo de pesquisa (nome, bio, habilidades, etc.)
        if ($request->has('termo')) {
            $termo = trim((string) $request->input('termo'));

            // Limita o tamanho do termo
            if (strlen($termo) > 100) {
                return $this->error('Termo de busca muito longo.', 400);
            }

            // Escapa caracteres curingas do LIKE (%, _)
            // Não escape \ pois o Laravel usa prepared statements
            $safeTermo = addcslashes($termo, '%_');

            // Usa where com LIKE para binding seguro
            $query->where(function ($q) use ($safeTermo) {
                $q->where('nome_completo', 'LIKE', "%{$safeTermo}%");
            });
        }

        // 2. Filtro por Escolaridade (Front-end envia nível mínimo)
        if ($request->has('escolaridade')) {
            $escolaridadeMinima = $request->input('escolaridade');

            // Hierarquia de níveis de escolaridade (do menor para o maior)
            $hierarquia = [
                'Fundamental Incompleto' => 1,
                'Fundamental Completo' => 2,
                'Médio Incompleto' => 3,
                'Médio Completo' => 4,
                'Superior Incompleto' => 5,
                'Superior Completo' => 6,
                'Pós-graduação' => 7,
                'Mestrado' => 8,
                'Doutorado' => 9,
            ];

            // Obtém o nível numérico da escolaridade mínima
            $nivelMinimo = $hierarquia[$escolaridadeMinima] ?? 0;

            if ($nivelMinimo > 0) {
                // Filtra candidatos com escolaridade igual ou superior
                $niveisAceitos = array_keys(array_filter($hierarquia, function($nivel) use ($nivelMinimo) {
                    return $nivel >= $nivelMinimo;
                }));

                $query->whereIn('nivel_escolaridade', $niveisAceitos);
            }
        }
        
        // 3. Filtro por Tipo de Deficiência (Experiência Específica)
        if ($request->has('tipo_deficiencia')) {
            $tipoDeficiencia = $request->input('tipo_deficiencia');
            
            $deficiencia = Deficiencia::where('nome', $tipoDeficiencia)->first();

            if ($deficiencia) {
                 // Filtra candidatos que possuam a deficiência específica
                 // (Usando a relação muitos-para-muitos através de Experiências Profissionais)
                 $query->whereHas('experienciasProfissionais.deficiencias', function ($q) use ($deficiencia) {
                     // Qualifica a coluna para evitar ambiguidade SQL 1052
                     $q->where('deficiencias.id_deficiencia', $deficiencia->id_deficiencia);
                 });
            }
        }
        
        // 4. Filtro por Distância/Deslocamento (Distância_km) - IMPLEMENTAÇÃO POR ESTIMATIVA
        if ($request->has('distancia_km')) {
            $distanciaKm = (int) $request->input('distancia_km');

            // Carrega o perfil completo da Instituição para obter a localização base
            $instituicao = $user->instituicao()->with('endereco')->first();
            $enderecoInst = optional($instituicao)->endereco;

            if ($enderecoInst && $enderecoInst->cidade && $enderecoInst->estado) {
                // Estimativa local (ex: até 50km): Filtra por candidatos na mesma Cidade/Estado
                if ($distanciaKm > 0 && $distanciaKm <= 50) { 
                    $query->whereHas('endereco', function ($q) use ($enderecoInst) {
                        $q->where('cidade', $enderecoInst->cidade)
                          ->where('estado', $enderecoInst->estado);
                    });
                } 
                // Estimativa regional (ex: 50km até 200km): Filtra por candidatos no mesmo Estado
                else if ($distanciaKm > 50 && $distanciaKm <= 200) {
                    $query->whereHas('endereco', function ($q) use ($enderecoInst) {
                        $q->where('estado', $enderecoInst->estado);
                    });
                }
                // Para distâncias muito grandes (> 200km), o filtro é ignorado, 
                // pois a estimativa não é útil e o filtro de termo já pode cobrir.
            }
        }

        // Paginação obrigatória para prevenir DoS
        $perPage = $this->safePerPage($request, 20);
        $candidatos = $query->paginate($perPage);

        // Formatação dos dados de resposta, se necessário (ocultar senhas, formatar datas, etc.)
        return $candidatos;
    }
    
    /**
     * GET /candidatos/{id}
     * Detalhe público de um candidato específico (sem dados sensíveis).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Eager-load all necessary nested relationships to prevent N+1 issues.
        $candidato = Candidato::with([
            'endereco',
            'experienciasPessoais',
            'experienciasProfissionais.deficiencias' // Load deficiencias through professional experiences
        ])
        ->findOrFail($id);

        // Unify personal and professional experiences into a single collection.
        $experienciasPessoais = $candidato->experienciasPessoais->map(function ($exp) {
            return [
                'id' => $exp->id_experiencia_pessoal,
                'tipo' => 'pessoal',
                'titulo' => 'Experiência Pessoal',
                'descricao' => $exp->descricao,
                'data_inicio' => null,
                'data_fim' => null,
            ];
        });

        $experienciasProfissionais = $candidato->experienciasProfissionais->map(function ($exp) {
            return [
                'id' => $exp->id_experiencia_profissional,
                'tipo' => 'profissional',
                'titulo' => 'Experiência Profissional',
                'descricao' => $exp->descricao,
                'data_inicio' => null,
                'data_fim' => null,
            ];
        });

        // Une e ordena experiências por data de início (mais recente primeiro)
        // Tratamento de valores null: experiências sem data vão para o final
        $todasExperiencias = $experienciasPessoais->concat($experienciasProfissionais)
            ->sortByDesc(function ($exp) {
                // Coloca null no final ao inverter a ordenação
                return $exp['data_inicio'] ?? '1900-01-01';
            })
            ->values();

        // Collect unique deficiencies from all professional experiences.
        $deficienciasAtuadas = $candidato->experienciasProfissionais
            ->pluck('deficiencias')
            ->flatten()
            ->unique('id_deficiencia')
            ->map(function ($def) {
                return [
                    'id' => $def->id_deficiencia,
                    'nome' => $def->nome,
                ];
            })
            ->values();

        // Build the final response structure matching the frontend's CandidatoPublico interface.
        return response()->json([
            'id'                  => $candidato->id,
            'nome_completo'       => $candidato->nome_completo,
            'escolaridade'        => $candidato->nivel_escolaridade,
            'foto_url'            => $candidato->foto_url,
            'link_perfil'         => $candidato->link_perfil,
            
            // Correctly nested endereco object
            'endereco'            => [
                'cidade' => optional($candidato->endereco)->cidade,
                'estado' => optional($candidato->endereco)->estado,
            ],
            
            // Correctly named and structured deficiencies array
            'deficiencias_atuadas' => $deficienciasAtuadas,
            
            // Unified and correctly structured experiences array
            'experiencias'        => $todasExperiencias,

            // NOTE: Fields expected by frontend but not present in backend model (bio, data_nascimento, genero) are omitted.
        ]);
    }
}