<?php

namespace App\Models;

use App\Enums\TipoEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FornecedorEmail extends Model
{
    protected $table = 'fornecedor_emails';

    protected $fillable = ['fornecedor_contato_id', 'email', 'tipo'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoEmail::class,
        ];
    }

    public function contato(): BelongsTo
    {
        return $this->belongsTo(FornecedorContato::class, 'fornecedor_contato_id');
    }
}
