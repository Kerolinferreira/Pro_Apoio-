<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidato;
use App\Models\Deficiencia;
use App\Models\Instituicao;
use App\Models\Endereco;
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
        
        // Garante que apenas Instituições podem usar a busca 
        if (($user->tipo_usuario ?? '') !== 'INSTITUICAO') {
            return $this->forbidden('Acesso negado. Apenas instituições podem buscar candidatos.');
        }

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
            // Remove valores vazios que podem vir de uma string como "a,,b"
            $escolaridades = array_filter($escolaridades); 
            // O modelo Candidato usa 'nivel_escolaridade'
            $query->whereIn('nivel_escolaridade', $escolaridades); 
        }
        
        // 3. Filtro por Tipo de Deficiência (Experiência Específica)
        if ($request->has('tipo_deficiencia')) {
            $tipoDeficiencia = $request->input('tipo_deficiencia');
            
            $deficiencia = Deficiencia::where('nome', $tipoDeficiencia)->first();

            if ($deficiencia) {
                 // Filtra candidatos que possuam a deficiência específica 
                 // (Usando a relação muitos-para-muitos através de Experiências Profissionais)
                 $query->whereHas('experienciasProfissionais.deficiencias', function ($q) use ($deficiencia) {
                     $q->where('id_deficiencia', $deficiencia->id);
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

        $candidatos = $query->get();

        // Formatação dos dados de resposta, se necessário (ocultar senhas, formatar datas, etc.)
        return response()->json($candidatos, 200);
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
        $candidato = Candidato::with([
            // Seleciona apenas campos de endereço necessários para a visualização pública
            'endereco' => function ($q) {
                $q->select('id_endereco', 'cidade', 'estado');
            },
            // Seleciona apenas campos de experiências pessoais necessários
            'experienciasPessoais' => function ($q) {
                $q->select('id_experiencia_pessoal', 'id_candidato', 'descricao');
            },
            // Retorna as deficiências (tabela Deficiencias, relação Many-to-Many)
            'deficiencias',
        ])
            // Seleciona apenas campos públicos do Candidato. CPF e Telefone são omitidos.
            ->select('id_candidato', 'id_endereco', 'nome_completo', 'nivel_escolaridade', 'foto_url', 'link_perfil')
            ->findOrFail($id);
            
        // Mapeia para o formato esperado pelo frontend (PerfilCandidatoPublicPage.tsx)
        return response()->json([
            'id'                  => $candidato->id,
            'nome_completo'       => $candidato->nome_completo,
            // O frontend usa 'escolaridade', o modelo usa 'nivel_escolaridade'
            'escolaridade'        => $candidato->nivel_escolaridade,
            // O campo 'bio' não existe no modelo Candidato original, mas o front espera.
            // Se existisse, seria incluído aqui.
            'foto_url'            => $candidato->foto_url,
            'link_perfil'         => $candidato->link_perfil,
            // Endereço
            'cidade'              => optional($candidato->endereco)->cidade,
            'estado'              => optional($candidato->endereco)->estado,
            // Relações
            'deficiencias'        => $candidato->deficiencias->map(fn($d) => ['nome' => $d->nome]),
            'experiencias_pessoais' => $candidato->experienciasPessoais->map(fn($e) => ['descricao' => $e->descricao]),
        ]);
    }
}