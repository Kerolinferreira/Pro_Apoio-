<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidato;
use App\Models\Endereco;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function usuario_pode_listar_notificacoes_nao_lidas()
    {
        $user = $this->createUser();

        // Criar notificações
        $this->createNotification($user, false);
        $this->createNotification($user, false);
        $this->createNotification($user, true); // lida

        $response = $this->actingAs($user)
            ->getJson('/api/notificacoes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'data', 'read_at', 'created_at']
                ],
                'links',
                'meta'
            ]);

        // Deve retornar apenas 2 não lidas
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function pode_filtrar_notificacoes_lidas()
    {
        $user = $this->createUser();

        $this->createNotification($user, false);
        $this->createNotification($user, true);
        $this->createNotification($user, true);

        $response = $this->actingAs($user)
            ->getJson('/api/notificacoes?status=read');

        $response->assertOk();

        // Deve retornar apenas 2 lidas
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function pode_listar_todas_notificacoes()
    {
        $user = $this->createUser();

        $this->createNotification($user, false);
        $this->createNotification($user, true);
        $this->createNotification($user, false);

        $response = $this->actingAs($user)
            ->getJson('/api/notificacoes?status=all');

        $response->assertOk();

        // Deve retornar todas (3)
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function notificacoes_sao_ordenadas_por_mais_recentes()
    {
        $user = $this->createUser();

        $old = $this->createNotification($user, false);
        sleep(1);
        $new = $this->createNotification($user, false);

        $response = $this->actingAs($user)
            ->getJson('/api/notificacoes');

        $response->assertOk();
        $data = $response->json('data');

        // Mais recente deve vir primeiro
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[1]['id']);
    }

    /** @test */
    public function paginacao_funciona()
    {
        $user = $this->createUser();

        // Criar 25 notificações
        for ($i = 0; $i < 25; $i++) {
            $this->createNotification($user, false);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/notificacoes?per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);

        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function usuario_nao_ve_notificacoes_de_outros()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->createNotification($user1, false);
        $this->createNotification($user2, false);

        $response = $this->actingAs($user1)
            ->getJson('/api/notificacoes');

        $response->assertOk();

        // user1 deve ver apenas 1 notificação
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function pode_marcar_notificacao_especifica_como_lida()
    {
        $user = $this->createUser();

        $notification = $this->createNotification($user, false);

        $response = $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'ids' => [$notification->id]
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Notificações marcadas como lidas.']);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);

        $updated = DatabaseNotification::find($notification->id);
        $this->assertNotNull($updated->read_at);
    }

    /** @test */
    public function pode_marcar_multiplas_notificacoes_como_lidas()
    {
        $user = $this->createUser();

        $n1 = $this->createNotification($user, false);
        $n2 = $this->createNotification($user, false);
        $n3 = $this->createNotification($user, false);

        $response = $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'ids' => [$n1->id, $n2->id]
            ]);

        $response->assertOk();

        // n1 e n2 devem estar lidas
        $this->assertNotNull(DatabaseNotification::find($n1->id)->read_at);
        $this->assertNotNull(DatabaseNotification::find($n2->id)->read_at);

        // n3 ainda não lida
        $this->assertNull(DatabaseNotification::find($n3->id)->read_at);
    }

    /** @test */
    public function pode_marcar_todas_como_lidas()
    {
        $user = $this->createUser();

        $this->createNotification($user, false);
        $this->createNotification($user, false);
        $this->createNotification($user, false);

        $response = $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'all' => true
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Todas as notificações marcadas como lidas.']);

        $unread = $user->unreadNotifications()->count();
        $this->assertEquals(0, $unread);
    }

    /** @test */
    public function marcar_sem_ids_e_sem_all_retorna_erro()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', []);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Nenhum ID informado.']);
    }

    /** @test */
    public function nao_marca_notificacoes_de_outros_usuarios()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $n1 = $this->createNotification($user1, false);
        $n2 = $this->createNotification($user2, false);

        // user1 tenta marcar notificação de user2
        $this->actingAs($user1)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'ids' => [$n2->id]
            ]);

        // Notificação de user2 não deve ser marcada
        $this->assertNull(DatabaseNotification::find($n2->id)->read_at);
    }

    /** @test */
    public function notificacao_ja_lida_nao_e_alterada()
    {
        $user = $this->createUser();

        $notification = $this->createNotification($user, true);
        $originalReadAt = $notification->read_at;

        sleep(1);

        $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'ids' => [$notification->id]
            ]);

        // read_at deve permanecer igual (já estava lida)
        $updated = DatabaseNotification::find($notification->id);
        $this->assertEquals($originalReadAt->timestamp, $updated->read_at->timestamp);
    }

    /** @test */
    public function type_retorna_apenas_basename_da_classe()
    {
        $user = $this->createUser();

        $notification = $this->createNotification($user, false);

        $response = $this->actingAs($user)
            ->getJson('/api/notificacoes');

        $response->assertOk();
        $data = $response->json('data');

        // Type deve ser apenas o basename, não o namespace completo
        $this->assertStringNotContainsString('\\', $data[0]['type']);
    }

    /** @test */
    public function validacao_rejeita_ids_invalidos()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'ids' => [123] // deve ser string
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function all_deve_ser_boolean()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/api/notificacoes/marcar-como-lidas', [
                'all' => 'yes' // deve ser boolean
            ]);

        $response->assertStatus(422);
    }

    // Helpers
    protected function createUser(): User
    {
        return User::create([
            'nome' => 'User Teste',
            'email' => fake()->unique()->safeEmail(),
            'senha_hash' => bcrypt('password123'),
            'tipo_usuario' => 'CANDIDATO',
        ]);
    }

    protected function createNotification(User $user, bool $read): DatabaseNotification
    {
        $notification = new DatabaseNotification([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['message' => 'Test notification'],
            'read_at' => $read ? now() : null,
        ]);

        $notification->notifiable()->associate($user);
        $notification->save();

        return $notification;
    }
}
