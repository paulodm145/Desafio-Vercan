<?php

namespace Database\Factories;

use App\Models\Estado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estado>
 */
class EstadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->state(),
            'sigla' => strtoupper(fake()->unique()->lexify('??')),
            'codigo_ibge' => fake()->unique()->numberBetween(11, 53),
        ];
    }
}
