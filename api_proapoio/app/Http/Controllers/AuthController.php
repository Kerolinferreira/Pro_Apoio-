<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use App\Models\Endereco;
use App\Helpers\JwtHelper;

/**
 * Autenticação e cadastro de usuários (candidato e instituição).
 * JWT stateless; o cliente descarta o token no logout.
 */
class AuthController extends Controller
{
    /** Normaliza email */
    private function normEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Somente dígitos */
    private function onlyDigits(?string $v): ?string
    {
        if ($v === null) return null;
        return preg_replace('/\D+/', '', $v);
    }

    /**
     * Cadastro de candidato: cria Endereco, User e Candidato em transação.
     * Campos do front aceitos: escolaridade|nivel_escolaridade,
     * nome_curso|curso_superior, nome_instituicao_ensino|instituicao_ensino.
     */
    public function registerCandidato(\App\Http\Requests\RegisterCandidatoRequest $request)
    {
        $data = $request->validated();

        $email = $this->normEmail($data['email']);

        $nivelEscolaridade = $data['nivel_escolaridade'] ?? $data['escolaridade'] ?? null;
        $cursoSuperior     = $data['curso_superior'] ?? $data['nome_curso'] ?? null;
        $instEnsino        = $data['instituicao_ensino'] ?? $data['nome_instituicao_ensino'] ?? null;

        return DB::transaction(function () use ($data, $email, $nivelEscolaridade, $cursoSuperior, $instEnsino) {
            $endereco = Endereco::create([
                'cep'              => $data['cep'],
                'logradouro'       => $data['logradouro'] ?? null,
                'bairro'           => $data['bairro'] ?? null,
                'cidade'           => $data['cidade'] ?? null,
                'estado'           => $data['estado'] ?? null,
                'numero'           => $data['numero'] ?? null,
                'complemento'      => $data['complemento'] ?? null,
                'ponto_referencia' => $data['ponto_referencia'] ?? null,
            ]);

            $user = User::create([
                'nome'              => $data['nome'],
                'email'             => $email,
                'senha_hash'        => Hash::make($data['password']),
                'tipo_usuario'      => 'CANDIDATO',
                'termos_aceite'     => (bool)($data['termos_aceite'] ?? false),
                'data_termos_aceite'=> ($data['termos_aceite'] ?? false) ? now() : null,
            ]);

            Candidato::create([
                'id_usuario'          => $user->id,
                'id_endereco'         => $endereco->id,
                'nome_completo'       => $data['nome'],
                'cpf'                 => $data['cpf'],
                'telefone'            => $data['telefone'],
                'link_perfil'         => $data['link_perfil'] ?? null,
                'nivel_escolaridade'  => $nivelEscolaridade,
                'curso_superior'      => $cursoSuperior,
                'instituicao_ensino'  => $instEnsino,
                'status'              => 'ATIVO',
            ]);

            $token = JwtHelper::generateToken($user);

            return response()->json([
                'user'  => array_merge($user->toArray(), ['tipo_usuario' => 'candidato']),
                'token' => $token,
            ], 201);
        });
    }

    /**
     * Cadastro de instituição: cria Endereco (se houver), User e Instituicao em transação.
     */
    public function registerInstituicao(\App\Http\Requests\RegisterInstituicaoRequest $request)
    {
        $data   = $request->validated();
        $email  = $this->normEmail($data['email']);
        $cnpj   = $this->onlyDigits($data['cnpj']);

        return DB::transaction(function () use ($data, $email, $cnpj) {
            $endereco = Endereco::create([
                'cep'              => isset($data['cep']) ? $this->onlyDigits($data['cep']) : null,
                'logradouro'       => $data['logradouro'] ?? null,
                'bairro'           => $data['bairro'] ?? null,
                'cidade'           => $data['cidade'] ?? null,
                'estado'           => $data['estado'] ?? null,
                'numero'           => $data['numero'] ?? null,
                'complemento'      => $data['complemento'] ?? null,
                'ponto_referencia' => $data['ponto_referencia'] ?? null,
            ]);

            $user = User::create([
                'nome'               => $data['nome'],
                'email'              => $email,
                'senha_hash'         => Hash::make($data['password']),
                'tipo_usuario'       => 'INSTITUICAO',
                'termos_aceite'      => (bool)($data['termos_aceite'] ?? false),
                'data_termos_aceite' => ($data['termos_aceite'] ?? false) ? now() : null,
            ]);

            Instituicao::create([
                'id_usuario'          => $user->id,
                'id_endereco'         => $endereco->id,
                'cnpj'                => $cnpj,
                'razao_social'        => $data['razao_social'],
                'nome_fantasia'       => $data['nome_fantasia'],
                'codigo_inep'         => $data['codigo_inep'] ?? null,
                'tipo_instituicao'    => $data['tipo_instituicao'] ?? null,
                'niveis_oferecidos'   => $data['niveis_oferecidos'] ?? null,
                'nome_responsavel'    => $data['nome_responsavel'] ?? null,
                'funcao_responsavel'  => $data['funcao_responsavel'] ?? null,
                'email_corporativo'   => $data['email_corporativo'] ?? null,
                'telefone_fixo'       => $data['telefone_fixo'] ?? null,
                'celular_corporativo' => $data['celular_corporativo'] ?? null,
            ]);

            $token = JwtHelper::generateToken($user);

            return response()->json([
                'user'  => array_merge($user->toArray(), ['tipo_usuario' => 'instituicao']),
                'token' => $token,
            ], 201);
        });
    }

    /**
     * Login com resposta genérica para falha e tipo_usuario alinhado ao front.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $this->normEmail($data['email']))->first();

        if (!$user || !Hash::check($data['password'], $user->senha_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $token = JwtHelper::generateToken($user);

        return response()->json([
            'user'  => array_merge($user->toArray(), [
                'tipo_usuario' => strtolower($user->tipo_usuario ?? ''),
            ]),
            'token' => $token,
        ]);
    }

    /**
     * Logout stateless (JWT): cliente deve descartar o token.
     */
    public function logout(Request $request)
    {
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    /**
     * Envia link de recuperação. Resposta neutra.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(['email' => $this->normEmail($request->input('email'))]);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Email de recuperação enviado'])
            : response()->json(['message' => 'Erro ao enviar e-mail'], 500);
    }

    /**
     * Redefine senha usando broker do Laravel, gravando em senha_hash.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            [
                'email'                 => $this->normEmail($request->input('email')),
                'password'              => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token'                 => $request->input('token'),
            ],
            function ($user, $password) {
                $user->forceFill([
                    'senha_hash'     => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Senha redefinida com sucesso'])
            : response()->json(['message' => 'Token inválido ou expirado'], 400);
    }
}
