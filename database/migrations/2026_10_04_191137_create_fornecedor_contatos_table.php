<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedor_contatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->boolean('principal')->default(false);
            $table->string('nome')->nullable();
            $table->string('empresa')->nullable();
            $table->string('cargo')->nullable();
            $table->timestamps();

            // Postgres não cria índice automático pra coluna de FK — sem isso,
            // tanto o CASCADE do delete quanto $fornecedor->contatos()->delete()
            // (FornecedorService::atualizar()) varrem a tabela inteira.
            $table->index('fornecedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedor_contatos');
    }
};
