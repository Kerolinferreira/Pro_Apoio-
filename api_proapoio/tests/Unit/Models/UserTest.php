<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Candidato;
use App\Models\Instituicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_table_name()
    {
        $user = new User();
        $this->assertEquals('usuarios', $user->getTable());
    }

    /** @test */
    public function it_has_correct_primary_key()
    {
        $user = new User();
        $this->assertEquals('id_usuario', $user->getKeyName());
    }

    /** @test */
    public function it_can_be_created_with_valid_data()
    {
        $user = User::factory()->create([
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'tipo_usuario' => 'Candidato'
        ]);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'joao@example.com',
            'nome' => 'João Silva',
            'tipo_usuario' => 'Candidato'
        ]);
    }

    /** @test */
    public function it_hashes_password_on_creation()
    {
        $user = User::factory()->create([
            'senha' => 'password123'
        ]);

        $this->assertNotEquals('password123', $user->senha);
        $this->assertTrue(strlen($user->senha) > 20); // Bcrypt hash length
    }

    /** @test */
    public function it_has_candidato_relationship()
    {
        $user = User::factory()->create(['tipo_usuario' => 'Candidato']);
        $candidato = Candidato::factory()->create(['id_usuario' => $user->id_usuario]);

        $this->assertInstanceOf(Candidato::class, $user->candidato);
        $this->assertEquals($candidato->id_candidato, $user->candidato->id_candidato);
    }

    /** @test */
    public function it_has_instituicao_relationship()
    {
        $user = User::factory()->create(['tipo_usuario' => 'Instituicao']);
        $instituicao = Instituicao::factory()->create(['id_usuario' => $user->id_usuario]);

        $this->assertInstanceOf(Instituicao::class, $user->instituicao);
        $this->assertEquals($instituicao->id_instituicao, $user->instituicao->id_instituicao);
    }

    /** @test */
    public function it_can_send_notifications()
    {
        $user = User::factory()->create();

        $this->assertIsArray($user->toArray());
        // Verifica que a trait Notifiable foi aplicada
        $this->assertTrue(method_exists($user, 'notify'));
    }

    /** @test */
    public function email_is_unique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->create(['senha' => 'password123']);

        $array = $user->toArray();
        $this->assertArrayNotHasKey('senha', $array);
    }
}
