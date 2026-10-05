<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FornecedorContato extends Model
{
    protected $table = 'fornecedor_contatos';

    protected $fillable = ['fornecedor_id', 'principal', 'nome', 'empresa', 'cargo'];

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
        ];
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function telefones(): HasMany
    {
        return $this->hasMany(FornecedorTelefone::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(FornecedorEmail::class);
    }
}
