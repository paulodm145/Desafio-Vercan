<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedor_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_contato_id')->constrained('fornecedor_contatos')->cascadeOnDelete();
            $table->string('email');
            $table->enum('tipo', ['pessoal', 'comercial', 'outro']);
            $table->timestamps();

            // Postgres não cria índice automático pra coluna de FK.
            $table->index('fornecedor_contato_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedor_emails');
    }
};
