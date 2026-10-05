<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const MAX_TENTATIVAS = 5;

    private const JANELA_EM_SEGUNDOS = 60;

    /**
     * @param  array{email: string, senha: string}  $credenciais
     *
     * @throws ValidationException quando o limite de tentativas foi atingido
     */
    public function login(Request $request, array $credenciais): bool
    {
        $chave = $this->chaveLimitador($request, $credenciais['email']);

        if (RateLimiter::tooManyAttempts($chave, self::MAX_TENTATIVAS)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => RateLimiter::availableIn($chave)]),
            ]);
        }

        $autenticado = Auth::attempt([
            'email' => $credenciais['email'],
            'password' => $credenciais['senha'],
        ]);

        if (! $autenticado) {
            RateLimiter::hit($chave, self::JANELA_EM_SEGUNDOS);

            return false;
        }

        RateLimiter::clear($chave);

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    // E-mail (case-insensitive) + IP: trava tentativas contra uma conta específica
    // sem derrubar um IP inteiro que compartilhe NAT com outros usuários, e evita
    // bypass trivial do limite só variando a capitalização do e-mail.
    private function chaveLimitador(Request $request, string $email): string
    {
        return mb_strtolower($email).'|'.$request->ip();
    }
}
