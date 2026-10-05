@props(['codigo', 'titulo', 'mensagem', 'cor' => 'primary'])

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="display-1 fw-bold text-{{ $cor }} lh-1 mb-3">{{ $codigo }}</div>
            <h1 class="h3 mb-3">{{ $titulo }}</h1>
            <p class="text-secondary mb-4">{{ $mensagem }}</p>
            <div class="d-flex gap-2 justify-content-center">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
