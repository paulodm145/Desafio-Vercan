{{--
    Deliberately short: only the items the project has today. Add new
    <li> entries here as real sections ship — resist pre-building menu
    items for pages that don't exist yet.
--}}
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark" data-enable-persistence="true">
    <div class="sidebar-brand">
        <a href="{{ route('home') }}" class="brand-link">
            <span class="brand-text-collapsed fw-bold">VR</span>
            <span class="brand-text fw-light">{{ config('app.name') }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Navegação principal">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false">
                <li class="nav-item">
                    <a
                        href="{{ route('home') }}"
                        class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                    >
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>Página Inicial</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a
                        href="{{ route('fornecedores.index') }}"
                        class="nav-link {{ request()->routeIs('fornecedores.*') ? 'active' : '' }}"
                    >
                        <i class="nav-icon bi bi-truck"></i>
                        <p>Fornecedores</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="sidebar-footer p-3 mt-auto border-top border-secondary border-opacity-25">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2"
                title="Sair"
                aria-label="Sair"
            >
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span class="sidebar-footer-text">Sair</span>
            </button>
        </form>
    </div>
</aside>
