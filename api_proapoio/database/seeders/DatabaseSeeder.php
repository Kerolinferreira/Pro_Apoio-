<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Vaga;
use App\Models\Endereco;
use Illuminate\Support\Facades\Hash;

/**
 * Classe de seeder principal.
 *
 * Popula o banco de dados com alguns usuários, candidatos, instituições e
 * vagas para facilitar o desenvolvimento e testes manuais.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Popula a tabela de deficiências
        $this->call(DeficienciaSeeder::class);

        // Cria um candidato de exemplo
        $userCandidato = User::create([
            'nome' => 'Candidato Exemplo',
            'email' => 'candidato@example.com',
            'senha_hash' => Hash::make('password'),
            // Armazena em maiúsculo conforme o esquema
            'tipo_usuario' => 'CANDIDATO',
            'termos_aceite' => true,
            'data_termos_aceite' => now(),
        ]);
        $endCandidato = Endereco::create([
            'cep' => '01001000',
        ]);
        $candidato = Candidato::create([
            'id_usuario'         => $userCandidato->id,
            'id_endereco'        => $endCandidato->id,
            'nome_completo'      => 'Candidato Exemplo',
            'cpf'               => '12345678909',
            'telefone'          => '11999999999',
            'nivel_escolaridade'=> 'Superior Completo',
            'status'            => 'ATIVO',
        ]);

        // Cria uma instituição de exemplo
        $userInstituicao = User::create([
            'nome' => 'Instituição Exemplo',
            'email' => 'instituicao@example.com',
            'senha_hash' => Hash::make('password'),
            'tipo_usuario' => 'INSTITUICAO',
            'termos_aceite' => true,
            'data_termos_aceite' => now(),
        ]);
        $endInst = Endereco::create([
            'cep' => '02002000',
        ]);
        $instituicao = Instituicao::create([
            'id_usuario'        => $userInstituicao->id,
            'id_endereco'       => $endInst->id,
            'cnpj'             => '12345678000190',
            'razao_social'      => 'Instituição Exemplo LTDA',
            'nome_fantasia'     => 'InstEx',
            'codigo_inep'       => '12345678',
            // Define o tipo de instituição em maiúsculo para aderir à nomenclatura
            'tipo_instituicao'  => 'PRIVADA',
            'nome_responsavel'  => 'Responsável Exemplo',
            'funcao_responsavel'=> 'Diretor',
            'email_corporativo' => 'contato@instex.com',
        ]);

        // Cria uma vaga de exemplo
        Vaga::create([
            'id_instituicao'        => $instituicao->id,
            'status'                => 'ATIVA',
            'carga_horaria_semanal' => 30,
            'regime_contratacao'    => 'ESTAGIO',
            'valor_remuneracao'     => 1500,
            'tipo_remuneracao'      => 'MES',
            'titulo'                => 'Monitor Educacional',
            'titulo_vaga'           => 'Monitor Educacional',
            'cidade'                => 'São Paulo',
            'estado'                => 'SP',
        ]);
    }
}