<x-guest-layout title="Entrar">
    <main class="login-box">
        <h1 class="login-logo">
            <b>{{ config('app.name') }}</b>
        </h1>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Acesse sua conta</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <label class="visually-hidden" for="email">E-mail</label>
                    <div class="input-group mb-3">
                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-control"
                            placeholder="E-mail"
                            value="{{ old('email') }}"
                            required
                            autofocus
                        />
                        <div class="input-group-text">
                            <span class="bi bi-envelope"></span>
                        </div>
                    </div>

                    <label class="visually-hidden" for="senha">Senha</label>
                    <div class="input-group mb-3">
                        <input
                            id="senha"
                            name="senha"
                            type="password"
                            class="form-control"
                            placeholder="Senha"
                            required
                        />
                        <div class="input-group-text">
                            <span class="bi bi-lock-fill"></span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</x-guest-layout>
