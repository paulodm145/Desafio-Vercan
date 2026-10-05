<x-guest-layout title="Página não encontrada">
    <x-erro
        codigo="404"
        titulo="Página não encontrada"
        mensagem="Não encontramos a página que você está procurando."
        cor="primary"
    >
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Voltar para o início
        </a>
    </x-erro>
</x-guest-layout>
