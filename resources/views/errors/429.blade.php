<x-guest-layout title="Muitas requisições">
    <x-erro
        codigo="429"
        titulo="Muitas requisições"
        mensagem="Você fez muitas requisições em pouco tempo. Aguarde um instante e tente novamente."
        cor="warning"
    >
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Voltar para o início
        </a>
    </x-erro>
</x-guest-layout>
