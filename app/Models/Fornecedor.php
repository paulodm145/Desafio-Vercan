<?php

namespace App\Models;

use App\Enums\IndicadorInscricaoEstadual;
use App\Enums\RegimeRecolhimento;
use App\Enums\TipoPessoa;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'fornecedores';

    protected $fillable = [
        'tipo_pessoa',
        'cnpj_cpf',
        'razao_social',
        'nome_fantasia',
        'nome',
        'apelido',
        'indicador_inscricao_estadual',
        'inscricao_estadual',
        'inscricao_municipal',
        'situacao_cnpj',
        'recolhimento',
        'ativo',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'ponto_referencia',
        'estado_id',
        'cidade_id',
        'condominio',
        'condominio_endereco',
        'condominio_numero',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo_pessoa' => TipoPessoa::class,
            'indicador_inscricao_estadual' => IndicadorInscricaoEstadual::class,
            'recolhimento' => RegimeRecolhimento::class,
            'ativo' => 'boolean',
            'condominio' => 'boolean',
        ];
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    public function contatos(): HasMany
    {
        return $this->hasMany(FornecedorContato::class);
    }

    public function contatoPrincipal(): HasOne
    {
        return $this->hasOne(FornecedorContato::class)->where('principal', true);
    }

    protected function nomeExibicao(): Attribute
    {
        return Attribute::make(get: fn () => $this->razao_social ?? $this->nome);
    }

    protected function nomeFantasiaExibicao(): Attribute
    {
        return Attribute::make(get: fn () => $this->nome_fantasia ?? $this->apelido);
    }

    protected function cnpjCpfFormatado(): Attribute
    {
        return Attribute::make(get: function () {
            $documento = (string) $this->cnpj_cpf;

            return match (strlen($documento)) {
                11 => preg_replace('/(.{3})(.{3})(.{3})(.{2})/', '$1.$2.$3-$4', $documento),
                14 => preg_replace('/(.{2})(.{3})(.{3})(.{4})(.{2})/', '$1.$2.$3/$4-$5', $documento),
                default => $documento,
            };
        });
    }
}
