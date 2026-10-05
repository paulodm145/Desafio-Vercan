<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();

            $table->enum('tipo_pessoa', ['fisica', 'juridica']);
            $table->string('cnpj_cpf', 14)->unique();
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia')->nullable();
            $table->string('nome')->nullable();
            $table->string('apelido')->nullable();
            $table->enum('indicador_inscricao_estadual', ['contribuinte', 'contribuinte_isento', 'nao_contribuinte'])
                ->nullable();
            $table->string('inscricao_estadual', 30)->nullable();
            $table->string('inscricao_municipal', 30)->nullable();
            $table->string('situacao_cnpj')->nullable();
            $table->enum('recolhimento', ['recolher_prestador', 'retido_tomador'])->nullable();
            $table->boolean('ativo')->default(true);

            $table->string('cep', 8);
            $table->string('logradouro');
            $table->string('numero', 20);
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('ponto_referencia')->nullable();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->foreignId('cidade_id')->constrained('cidades')->restrictOnDelete();
            $table->boolean('condominio')->default(false);
            $table->string('condominio_endereco')->nullable();
            $table->string('condominio_numero', 20)->nullable();

            $table->text('observacoes')->nullable();

            $table->timestamps();

            // Postgres, diferente do MySQL/InnoDB, não cria índice automático
            // para coluna de FK — sem isso, o DELETE ON RESTRICT de
            // estados/cidades precisa varrer a tabela inteira pra checar se
            // existe fornecedor referenciando.
            $table->index('estado_id');
            $table->index('cidade_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
