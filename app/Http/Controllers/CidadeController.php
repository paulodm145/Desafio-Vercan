<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Repositories\CidadeRepository;
use Illuminate\Http\JsonResponse;

class CidadeController extends Controller
{
    public function __construct(private readonly CidadeRepository $cidades) {}

    public function porEstado(Estado $estado): JsonResponse
    {
        return response()->json(
            $this->cidades->porEstado($estado->id)->map(fn ($cidade) => [
                'id' => $cidade->id,
                'nome' => $cidade->nome,
            ]),
        );
    }
}
