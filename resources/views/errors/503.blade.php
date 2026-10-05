<x-guest-layout title="Serviço indisponível">
    <x-erro
        codigo="503"
        titulo="Serviço indisponível"
        mensagem="O sistema está em manutenção ou temporariamente indisponível. Tente novamente em instantes."
        cor="secondary"
    >
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Voltar para o início
        </a>
    </x-erro>
</x-guest-layout>
