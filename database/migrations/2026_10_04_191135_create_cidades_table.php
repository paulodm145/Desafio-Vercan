<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estados')->cascadeOnDelete();
            $table->string('nome');
            $table->unsignedInteger('codigo_ibge')->unique();
            $table->timestamps();

            $table->index(['estado_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cidades');
    }
};
