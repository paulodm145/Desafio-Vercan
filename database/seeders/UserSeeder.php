<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Credenciais documentadas no README — a senha deve ser alterada em
     * qualquer ambiente que não seja desenvolvimento local.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@vercan.com.br'],
            [
                'nome' => 'Administrador',
                'senha' => 'Vercan@123',
                'email_verified_at' => now(),
            ],
        );
    }
}
