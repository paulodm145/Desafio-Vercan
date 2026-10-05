<x-guest-layout title="Acesso negado">
    <x-erro
        codigo="403"
        titulo="Acesso negado"
        mensagem="Você não tem permissão para acessar este recurso."
        cor="danger"
    >
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Voltar para o início
        </a>
    </x-erro>
</x-guest-layout>
