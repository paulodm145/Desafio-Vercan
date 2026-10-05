<?php

namespace Database\Factories;

use App\Models\Cidade;
use App\Models\Estado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cidade>
 */
class CidadeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estado_id' => Estado::factory(),
            'nome' => fake()->unique()->city(),
            'codigo_ibge' => fake()->unique()->numberBetween(1000000, 9999999),
        ];
    }
}
