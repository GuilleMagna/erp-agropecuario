<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Dashboard')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @livewireStyles

    <style>
        body { background-color: #f4f6f8; }
        .sidebar {
            width: 260px; min-height: 100vh; position: fixed;
            top: 0; left: 0; background: #16324F; z-index: 100; overflow-y: auto;
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .sidebar-brand .brand-text {
            color: white; font-weight: 700; font-size: 1rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75); padding: .6rem 1.5rem;
            font-size: .875rem; border-radius: 0; transition: all .15s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white; background: rgba(255,255,255,.1);
        }
        .sidebar .nav-section {
            color: rgba(255,255,255,.4); font-size: .7rem; font-weight: 700;
            letter-spacing: .8px; text-transform: uppercase;
            padding: 1.2rem 1.5rem .4rem;
        }
        .main-content { margin-left: 260px; padding: 1.5rem; }
        .topbar {
            background: white; border-bottom: 1px solid #e7ecf1;
            padding: .75rem 1.5rem; margin: -1.5rem -1.5rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar .page-title { font-weight: 700; font-size: 1.05rem; color: #16324F; }
    </style>
</head>
<body>

<nav class="sidebar">

    <div class="sidebar-brand d-flex align-items-center gap-2">
        <x-application-logo-white style="height:32px; width:auto;" />
        <span class="brand-text">{{ config('app.name') }}</span>
    </div>

    <ul class="nav flex-column mt-2">

        <li><span class="nav-section">Principal</span></li>
        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <li><span class="nav-section">Producción</span></li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-map me-2"></i> Campos y Lotes
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-flower1 me-2"></i> Agricultura
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-heart-pulse me-2"></i> Ganadería
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-grid me-2"></i> Encierre a Corral
            </a>
        </li>

        <li><span class="nav-section">Comercial</span></li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-box-seam me-2"></i> Insumos
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-cart me-2"></i> Compras
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-cash-coin me-2"></i> Ventas
            </a>
        </li>

        <li><span class="nav-section">Finanzas</span></li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-bank me-2"></i> Finanzas
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-receipt me-2"></i> ARCA / IVA
            </a>
        </li>

        <li><span class="nav-section">Sistema</span></li>
        @can('admin.usuarios.ver')
        <li class="nav-item">
            <a href="{{ route('admin.usuarios.index') }}"
               class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                <i class="bi bi-people me-2"></i> Usuarios
            </a>
        </li>
        @endcan
        @can('admin.establecimientos.gestionar')
        <li class="nav-item">
            <a href="{{ route('admin.establecimientos.index') }}"
               class="nav-link {{ request()->routeIs('admin.establecimientos.*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt me-2"></i> Establecimientos
            </a>
        </li>
        @endcan

        <li class="nav-item mt-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        style="background:none; border:none; width:100%; text-align:left; padding: 0.6rem 1.5rem; color:rgba(255,255,255,.75); font-size:.875rem; cursor:pointer;"
                        onmouseover="this.style.color='white'; this.style.background='rgba(255,255,255,.1)'"
                        onmouseout="this.style.color='rgba(255,255,255,.75)'; this.style.background='none'">
                    <i class="bi bi-box-arrow-left me-2"></i> Cerrar sesión
                </button>
            </form>
        </li>

    </ul>
</nav>

<main class="main-content">
    <div class="topbar">
        <span class="page-title">@yield('title', 'Dashboard')</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">
                <i class="bi bi-building me-1"></i>
                {{ auth()->user()->empresa->razon_social ?? '' }}
            </span>
            <span class="fw-semibold small text-secondary">
                {{ auth()->user()->nombre_completo }}
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@livewireScripts

</body>
</html>