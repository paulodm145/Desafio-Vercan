@props(['title' => null])
<!doctype html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8" />
        <title>{{ $title ? "{$title} | " . config('app.name') : config('app.name') }}</title>

        {{-- Resolves the theme before first paint so there is no flash of the wrong color scheme. --}}
        <script>
            (() => {
                'use strict';
                const root = document.documentElement;
                const STORAGE_KEY = 'lte-theme';
                let stored = null;
                try {
                    stored = localStorage.getItem(STORAGE_KEY);
                } catch {
                    // localStorage may be unavailable (private mode, sandboxed iframe).
                }
                const authored = root.getAttribute('data-bs-theme');
                let resolved = 'light';
                if (stored === 'dark' || stored === 'light') {
                    resolved = stored;
                } else if (authored === 'dark' || authored === 'light') {
                    resolved = authored;
                } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
                    resolved = 'dark';
                }
                root.setAttribute('data-bs-theme', resolved);
                root.style.colorScheme = resolved;
                if (resolved !== authored) {
                    root.setAttribute('data-lte-theme-resolved', '');
                }
            })();
        </script>

        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="color-scheme" content="light dark" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">
        <div class="app-wrapper">
            <x-navbar />
            <x-sidebar />

            <main class="app-main">
                <div class="app-content-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                <h1 class="mb-0 fs-3">{{ $title }}</h1>
                            </div>
                            <div class="col-sm-6">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb float-sm-end">
                                        <li class="breadcrumb-item">
                                            <a href="{{ route('home') }}">Início</a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            {{ $title }}
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-content">
                    <div class="container-fluid">
                        @if (session('sucesso'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('sucesso') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </div>
            </main>

            <x-footer />
        </div>

        @stack('scripts')
    </body>
</html>
