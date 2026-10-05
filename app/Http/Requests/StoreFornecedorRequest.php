<?php

namespace App\Http\Requests;

use App\Enums\IndicadorInscricaoEstadual;
use App\Enums\RegimeRecolhimento;
use App\Enums\TipoEmail;
use App\Enums\TipoPessoa;
use App\Enums\TipoTelefone;
use App\Rules\CnpjValido;
use App\Rules\CpfValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pessoaFisica = $this->input('tipo_pessoa') === TipoPessoa::Fisica->value;
        $temCondominio = $this->boolean('condominio');

        return [
            'tipo_pessoa' => ['required', Rule::enum(TipoPessoa::class)],
            'cnpj_cpf' => [
                'required',
                'string',
                $pessoaFisica ? new CpfValido : new CnpjValido,
                Rule::unique('fornecedores', 'cnpj_cpf')->ignore($this->route('fornecedor')),
            ],
            'razao_social' => [Rule::requiredIf(! $pessoaFisica), 'nullable', 'string', 'max:255'],
            'nome_fantasia' => [Rule::requiredIf(! $pessoaFisica), 'nullable', 'string', 'max:255'],
            'nome' => [Rule::requiredIf($pessoaFisica), 'nullable', 'string', 'max:255'],
            'apelido' => ['nullable', 'string', 'max:255'],
            'indicador_inscricao_estadual' => ['nullable', Rule::enum(IndicadorInscricaoEstadual::class)],
            'inscricao_estadual' => [
                Rule::requiredIf($this->input('indicador_inscricao_estadual') === IndicadorInscricaoEstadual::Contribuinte->value),
                'nullable',
                'string',
                'max:30',
            ],
            'inscricao_municipal' => ['nullable', 'string', 'max:30'],
            // situacao_cnpj não tem regra aqui de propósito: é readonly na UI,
            // nunca aceito do cliente — ver FornecedorService::situacaoCnpjConfiavel().
            'recolhimento' => [Rule::requiredIf(! $pessoaFisica), 'nullable', Rule::enum(RegimeRecolhimento::class)],
            'ativo' => ['required', 'boolean'],

            'cep' => ['required', 'string', 'size:8'],
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['required', 'string', 'max:255'],
            'ponto_referencia' => ['nullable', 'string', 'max:255'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
            'cidade_id' => [
                'required',
                'integer',
                Rule::exists('cidades', 'id')->where('estado_id', $this->input('estado_id')),
            ],
            'condominio' => ['required', 'boolean'],
            'condominio_endereco' => [Rule::requiredIf($temCondominio), 'nullable', 'string', 'max:255'],
            'condominio_numero' => [Rule::requiredIf($temCondominio), 'nullable', 'string', 'max:20'],

            'observacoes' => ['nullable', 'string'],

            'contato_principal.telefones' => ['required', 'array', 'min:1'],
            'contato_principal.telefones.*.numero' => ['required', 'string', 'max:20'],
            'contato_principal.telefones.*.tipo' => ['required', Rule::enum(TipoTelefone::class)],
            'contato_principal.emails' => ['nullable', 'array'],
            'contato_principal.emails.*.email' => ['required', 'email', 'max:255'],
            'contato_principal.emails.*.tipo' => ['required', Rule::enum(TipoEmail::class)],

            'contatos_adicionais' => ['nullable', 'array'],
            'contatos_adicionais.*.nome' => ['nullable', 'string', 'max:255'],
            'contatos_adicionais.*.empresa' => ['nullable', 'string', 'max:255'],
            'contatos_adicionais.*.cargo' => ['nullable', 'string', 'max:255'],
            'contatos_adicionais.*.telefones' => ['nullable', 'array'],
            'contatos_adicionais.*.telefones.*.numero' => ['required', 'string', 'max:20'],
            'contatos_adicionais.*.telefones.*.tipo' => ['required', Rule::enum(TipoTelefone::class)],
            'contatos_adicionais.*.emails' => ['nullable', 'array'],
            'contatos_adicionais.*.emails.*.email' => ['required', 'email', 'max:255'],
            'contatos_adicionais.*.emails.*.tipo' => ['required', Rule::enum(TipoEmail::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contato_principal.telefones' => 'telefones do contato principal',
            'contato_principal.telefones.*.numero' => 'telefone do contato principal',
            'contato_principal.telefones.*.tipo' => 'tipo de telefone do contato principal',
            'contato_principal.emails.*.email' => 'e-mail do contato principal',
            'contato_principal.emails.*.tipo' => 'tipo de e-mail do contato principal',
            'contatos_adicionais.*.telefones.*.numero' => 'telefone do contato adicional',
            'contatos_adicionais.*.telefones.*.tipo' => 'tipo de telefone do contato adicional',
            'contatos_adicionais.*.emails.*.email' => 'e-mail do contato adicional',
            'contatos_adicionais.*.emails.*.tipo' => 'tipo de e-mail do contato adicional',
        ];
    }

    protected function prepareForValidation(): void
    {
        $dados = [
            'cnpj_cpf' => $this->normalizarDocumento((string) $this->input('cnpj_cpf')),
            'cep' => preg_replace('/\D/', '', (string) $this->input('cep')),
        ];

        if ($this->has('contato_principal.telefones')) {
            $dados['contato_principal'] = $this->input('contato_principal', []);
            $dados['contato_principal']['telefones'] = $this->normalizarTelefones(
                $this->input('contato_principal.telefones', []),
            );
        }

        if ($this->has('contatos_adicionais')) {
            $dados['contatos_adicionais'] = array_map(
                function (array $contato) {
                    if (isset($contato['telefones'])) {
                        $contato['telefones'] = $this->normalizarTelefones($contato['telefones']);
                    }

                    return $contato;
                },
                $this->input('contatos_adicionais', []),
            );
        }

        $this->merge($dados);
    }

    private function normalizarDocumento(string $valor): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $valor));
    }

    /**
     * @param  array<int, array<string, mixed>>  $telefones
     * @return array<int, array<string, mixed>>
     */
    private function normalizarTelefones(array $telefones): array
    {
        return array_map(
            fn (array $telefone) => [
                ...$telefone,
                'numero' => preg_replace('/\D/', '', (string) ($telefone['numero'] ?? '')),
            ],
            $telefones,
        );
    }
}
