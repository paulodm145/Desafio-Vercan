<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CidadeController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\Integracoes\CepController;
use App\Http\Controllers\Integracoes\CnpjController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'telaInicial'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/home', fn () => view('home'))->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/fornecedores', [FornecedorController::class, 'index'])->name('fornecedores.index');
    Route::get('/fornecedores/dados', [FornecedorController::class, 'dados'])->name('fornecedores.dados');
    Route::get('/fornecedores/criar', [FornecedorController::class, 'create'])->name('fornecedores.create');
    Route::get('/fornecedores/verificar-documento', [FornecedorController::class, 'verificarDocumento'])->name('fornecedores.verificar-documento');
    Route::get('/fornecedores/exportar/pdf', [FornecedorController::class, 'exportarPdf'])->name('fornecedores.exportar-pdf');
    Route::get('/fornecedores/exportar/excel', [FornecedorController::class, 'exportarExcel'])->name('fornecedores.exportar-excel');
    Route::post('/fornecedores', [FornecedorController::class, 'store'])->name('fornecedores.store');
    Route::get('/fornecedores/{fornecedor}/editar', [FornecedorController::class, 'edit'])->name('fornecedores.edit');
    Route::put('/fornecedores/{fornecedor}', [FornecedorController::class, 'update'])->name('fornecedores.update');
    Route::delete('/fornecedores/{fornecedor}', [FornecedorController::class, 'destroy'])->name('fornecedores.destroy');

    Route::get('/estados/{estado}/cidades', [CidadeController::class, 'porEstado'])->name('estados.cidades');
    Route::get('/ceps/{cep}', [CepController::class, 'show'])->name('ceps.show');
    Route::get('/cnpjs/{cnpj}', [CnpjController::class, 'show'])->name('cnpjs.show');
});
