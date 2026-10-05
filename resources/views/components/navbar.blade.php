{{--
    Top header bar. Kept to the chrome that works with zero backend data
    (sidebar toggle, fullscreen, color mode) — dropdowns that need real
    data (messages, notifications, the signed-in user) come back once
    there is an authenticated user to show.
--}}
<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a
                    class="nav-link"
                    data-lte-toggle="sidebar"
                    href="#"
                    role="button"
                    aria-label="Alternar menu lateral"
                >
                    <i class="bi bi-list"></i>
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a
                    class="nav-link"
                    href="#"
                    data-lte-toggle="fullscreen"
                    aria-label="Alternar tela cheia"
                >
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                </a>
            </li>

            <li class="nav-item dropdown">
                <a
                    class="nav-link"
                    href="#"
                    id="bd-theme"
                    aria-label="Alternar tema de cores"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                    <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                    <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
                </a>
                <ul
                    class="dropdown-menu dropdown-menu-end"
                    aria-labelledby="bd-theme"
                    style="--bs-dropdown-min-width: 8rem"
                >
                    <li>
                        <button
                            type="button"
                            class="dropdown-item d-flex align-items-center"
                            data-bs-theme-value="light"
                            aria-pressed="false"
                        >
                            <i class="bi bi-sun-fill me-2"></i>
                            Claro
                            <i class="bi bi-check-lg ms-auto d-none"></i>
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item d-flex align-items-center"
                            data-bs-theme-value="dark"
                            aria-pressed="false"
                        >
                            <i class="bi bi-moon-fill me-2"></i>
                            Escuro
                            <i class="bi bi-check-lg ms-auto d-none"></i>
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item d-flex align-items-center active"
                            data-bs-theme-value="auto"
                            aria-pressed="true"
                        >
                            <i class="bi bi-circle-half me-2"></i>
                            Automático
                            <i class="bi bi-check-lg ms-auto d-none"></i>
                        </button>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
