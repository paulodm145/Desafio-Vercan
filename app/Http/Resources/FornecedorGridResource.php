<?php

namespace App\Http\Resources;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Fornecedor $resource
 */
class FornecedorGridResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'razao_social_nome' => $this->razao_social ?? $this->nome,
            'nome_fantasia_apelido' => $this->nome_fantasia ?? $this->apelido,
            'cnpj_cpf' => $this->cnpj_cpf,
            'tipo_pessoa' => $this->tipo_pessoa->value,
            'ativo' => $this->ativo,
            'editar_url' => route('fornecedores.edit', $this->resource),
            'excluir_url' => route('fornecedores.destroy', $this->resource),
        ];
    }
}
