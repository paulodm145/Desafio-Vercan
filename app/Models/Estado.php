<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    use HasFactory;

    protected $table = 'estados';

    protected $fillable = ['nome', 'sigla', 'codigo_ibge'];

    public function cidades(): HasMany
    {
        return $this->hasMany(Cidade::class);
    }
}
