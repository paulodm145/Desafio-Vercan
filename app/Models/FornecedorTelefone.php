<?php

namespace App\Models;

use App\Enums\TipoTelefone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FornecedorTelefone extends Model
{
    protected $table = 'fornecedor_telefones';

    protected $fillable = ['fornecedor_contato_id', 'numero', 'tipo'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoTelefone::class,
        ];
    }

    public function contato(): BelongsTo
    {
        return $this->belongsTo(FornecedorContato::class, 'fornecedor_contato_id');
    }
}
