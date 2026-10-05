<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect(route('login'));
    }

    public function test_resposta_inclui_headers_basicos_de_seguranca(): void
    {
        $resposta = $this->get(route('login'));

        $resposta->assertHeader('X-Frame-Options', 'DENY');
        $resposta->assertHeader('X-Content-Type-Options', 'nosniff');
        $resposta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['senha' => 'senha-valida']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'senha' => 'senha-valida',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['senha' => 'senha-valida']);

        // A real browser always GETs the login page before submitting the form —
        // that's what lets Laravel's back() know where to return to on failure.
        $this->get('/login');

        $response = $this->post('/login', [
            'email' => $user->email,
            'senha' => 'senha-errada',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_bloqueia_login_apos_muitas_tentativas_invalidas(): void
    {
        $user = User::factory()->create(['senha' => 'senha-valida']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'senha' => 'senha-errada']);
        }

        // A 6ª tentativa é bloqueada mesmo com a senha CERTA — prova que quem
        // travou foi o rate limit, não a validação de credencial.
        $resposta = $this->post('/login', ['email' => $user->email, 'senha' => 'senha-valida']);

        $resposta->assertSessionHasErrors('email');
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_login_bem_sucedido_limpa_o_limitador_de_tentativas(): void
    {
        $user = User::factory()->create(['senha' => 'senha-valida']);

        $this->post('/login', ['email' => $user->email, 'senha' => 'senha-errada']);
        $this->post('/login', ['email' => $user->email, 'senha' => 'senha-errada']);

        $sucesso = $this->post('/login', ['email' => $user->email, 'senha' => 'senha-valida']);
        $sucesso->assertRedirect(route('home'));

        $this->post('/logout');

        // Se o limitador não tivesse sido zerado no login bem-sucedido, essa
        // tentativa somaria com as 2 de antes e acabaria travando cedo demais.
        $resposta = $this->post('/login', ['email' => $user->email, 'senha' => 'senha-errada']);

        $resposta->assertSessionHasErrors('email');
        $this->assertStringNotContainsString('Muitas tentativas', session('errors')->first('email'));
    }
}
