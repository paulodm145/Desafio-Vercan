<x-guest-layout title="Erro interno">
    <x-erro
        codigo="500"
        titulo="Algo deu errado do nosso lado"
        mensagem="Ocorreu um erro inesperado. Tente novamente em instantes."
        cor="danger"
    >
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Voltar para o início
        </a>
    </x-erro>
</x-guest-layout>
