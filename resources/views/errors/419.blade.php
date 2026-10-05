<x-guest-layout title="Sessão expirada">
    <x-erro
        codigo="419"
        titulo="Sessão expirada"
        mensagem="Sua sessão expirou por inatividade. Volte para o início e tente novamente."
        cor="warning"
    >
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Voltar para o início
        </a>
    </x-erro>
</x-guest-layout>
