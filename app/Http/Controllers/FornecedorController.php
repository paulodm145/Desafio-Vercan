<?php

namespace App\Http\Controllers;

use App\Exports\FornecedoresExport;
use App\Http\Requests\StoreFornecedorRequest;
use App\Http\Requests\UpdateFornecedorRequest;
use App\Http\Requests\VerificarDocumentoRequest;
use App\Http\Resources\FornecedorGridResource;
use App\Models\Fornecedor;
use App\Services\FornecedorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FornecedorController extends Controller
{
    public function __construct(private readonly FornecedorService $fornecedorService) {}

    public function index(): View
    {
        return view('fornecedores.index');
    }

    public function dados(Request $request): JsonResponse
    {
        $pagina = $this->fornecedorService->listarParaGrid($request->query());

        return response()->json([
            'data' => FornecedorGridResource::collection($pagina->items()),
            'last_page' => $pagina->lastPage(),
            'last_row' => $pagina->total(),
        ]);
    }

    public function create(): View
    {
        $fornecedor = new Fornecedor;

        return view('fornecedores.form', [
            'fornecedor' => $fornecedor,
            ...$this->fornecedorService->dadosParaFormulario($fornecedor),
        ]);
    }

    public function store(StoreFornecedorRequest $request): RedirectResponse
    {
        $this->fornecedorService->criar($request->validated());

        return redirect()
            ->route('fornecedores.index')
            ->with('sucesso', 'Fornecedor cadastrado com sucesso.');
    }

    public function edit(Fornecedor $fornecedor): View
    {
        $fornecedor->load(['contatos.telefones', 'contatos.emails']);

        return view('fornecedores.form', [
            'fornecedor' => $fornecedor,
            ...$this->fornecedorService->dadosParaFormulario($fornecedor),
        ]);
    }

    public function update(UpdateFornecedorRequest $request, Fornecedor $fornecedor): RedirectResponse
    {
        $this->fornecedorService->atualizar($fornecedor, $request->validated());

        return redirect()
            ->route('fornecedores.index')
            ->with('sucesso', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Fornecedor $fornecedor): JsonResponse
    {
        $this->fornecedorService->excluir($fornecedor);

        return response()->json(['sucesso' => true]);
    }

    public function exportarPdf(Request $request): Response
    {
        $fornecedores = $this->fornecedorService->listarParaExportacao($request->query());

        return Pdf::loadView('fornecedores.exportacao-pdf', ['fornecedores' => $fornecedores])
            ->download('fornecedores.pdf');
    }

    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $fornecedores = $this->fornecedorService->listarParaExportacao($request->query());

        return Excel::download(new FornecedoresExport($fornecedores), 'fornecedores.xlsx');
    }

    public function verificarDocumento(VerificarDocumentoRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $cadastrado = $this->fornecedorService->documentoJaCadastrado(
            $dados['documento'],
            $dados['fornecedor_id'] ?? null,
        );

        return response()->json(['cadastrado' => $cadastrado]);
    }
}
