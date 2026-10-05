<?php

namespace Database\Factories;

use App\Enums\IndicadorInscricaoEstadual;
use App\Enums\RegimeRecolhimento;
use App\Enums\TipoPessoa;
use App\Models\Cidade;
use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornecedor>
 */
class FornecedorFactory extends Factory
{
    public function definition(): array
    {
        $cidade = Cidade::query()->inRandomOrder()->first();
        $juridica = fake()->boolean(70);

        return [
            'tipo_pessoa' => $juridica ? TipoPessoa::Juridica : TipoPessoa::Fisica,
            'cnpj_cpf' => $juridica ? fake()->unique()->numerify('##############') : fake()->unique()->numerify('###########'),
            'razao_social' => $juridica ? fake()->company() : null,
            'nome_fantasia' => $juridica ? fake()->company() : null,
            'nome' => $juridica ? null : fake()->name(),
            'apelido' => $juridica ? null : fake()->firstName(),
            'indicador_inscricao_estadual' => $juridica ? fake()->randomElement(IndicadorInscricaoEstadual::cases()) : null,
            'inscricao_estadual' => $juridica ? fake()->numerify('###########') : null,
            'inscricao_municipal' => $juridica ? fake()->numerify('########') : null,
            'situacao_cnpj' => $juridica ? fake()->randomElement(['ATIVA', 'BAIXADA', 'SUSPENSA']) : null,
            'recolhimento' => $juridica ? fake()->randomElement(RegimeRecolhimento::cases()) : null,
            'ativo' => fake()->boolean(85),
            'cep' => fake()->numerify('########'),
            'logradouro' => fake()->streetName(),
            'numero' => (string) fake()->numberBetween(1, 9999),
            'complemento' => fake()->optional()->secondaryAddress(),
            'bairro' => fake()->citySuffix(),
            'ponto_referencia' => fake()->optional()->sentence(4),
            'estado_id' => $cidade->estado_id,
            'cidade_id' => $cidade->id,
            'condominio' => false,
            'condominio_endereco' => null,
            'condominio_numero' => null,
            'observacoes' => fake()->optional()->paragraph(),
        ];
    }
}
