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
        .card-hover { transition: box-shadow .15s, transform .15s; cursor: pointer; }
        .card-hover:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; transform: translateY(-2px); }
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
        @can('campos.lotes.ver')
        <li class="nav-item">
            <a href="{{ route('campos.lotes.index') }}"
               class="nav-link {{ request()->routeIs('campos.lotes.*') ? 'active' : '' }}">
                <i class="bi bi-map me-2"></i> Lotes
            </a>
        </li>
        @endcan
        @canany(['agricultura.campanas.ver', 'agricultura.siembra.ver', 'agricultura.labores.ver', 'agricultura.cosecha.ver'])
        <li>
            <span class="d-block" style="color:rgba(255,255,255,.3); font-size:.68rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:.5rem 1.5rem .15rem 2rem;">
                Agricultura
            </span>
        </li>
        @endcanany
        @can('agricultura.campanas.ver')
        <li class="nav-item">
            <a href="{{ route('agricultura.campanas.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('agricultura.campanas.*') ? 'active' : '' }}">
                <i class="bi bi-calendar3 me-2"></i> Campañas
            </a>
        </li>
        @endcan
        @can('agricultura.siembra.ver')
        <li class="nav-item">
            <a href="{{ route('agricultura.siembras.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('agricultura.siembras.*') ? 'active' : '' }}">
                <i class="bi bi-flower1 me-2"></i> Siembras
            </a>
        </li>
        @endcan
        @can('agricultura.labores.ver')
        <li class="nav-item">
            <a href="{{ route('agricultura.labores.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('agricultura.labores.*') ? 'active' : '' }}">
                <i class="bi bi-tools me-2"></i> Labores
            </a>
        </li>
        @endcan
        @can('agricultura.cosecha.ver')
        <li class="nav-item">
            <a href="{{ route('agricultura.cosechas.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('agricultura.cosechas.*') ? 'active' : '' }}">
                <i class="bi bi-basket me-2"></i> Cosechas
            </a>
        </li>
        @endcan
        @canany(['ganaderia.animales.ver', 'ganaderia.movimientos.ver', 'ganaderia.pesajes.ver', 'ganaderia.sanidad.ver', 'ganaderia.reproduccion.ver'])
        <li>
            <span class="d-block" style="color:rgba(255,255,255,.3); font-size:.68rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:.5rem 1.5rem .15rem 2rem;">
                Ganadería
            </span>
        </li>
        @endcanany
        @can('ganaderia.animales.ver')
        <li class="nav-item">
            <a href="{{ route('ganaderia.animales.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ganaderia.animales.*') ? 'active' : '' }}">
                <i class="bi bi-heart-pulse me-2"></i> Animales
            </a>
        </li>
        @endcan
        @can('ganaderia.movimientos.ver')
        <li class="nav-item">
            <a href="{{ route('ganaderia.movimientos.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ganaderia.movimientos.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right me-2"></i> Movimientos
            </a>
        </li>
        @endcan
        @can('ganaderia.pesajes.ver')
        <li class="nav-item">
            <a href="{{ route('ganaderia.pesajes.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ganaderia.pesajes.*') ? 'active' : '' }}">
                <i class="bi bi-speedometer me-2"></i> Pesajes
            </a>
        </li>
        @endcan
        @can('ganaderia.sanidad.ver')
        <li class="nav-item">
            <a href="{{ route('ganaderia.sanidad.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ganaderia.sanidad.*') ? 'active' : '' }}">
                <i class="bi bi-shield-plus me-2"></i> Sanidad
            </a>
        </li>
        @endcan
        @can('ganaderia.reproduccion.ver')
        <li class="nav-item">
            <a href="{{ route('ganaderia.reproduccion.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ganaderia.reproduccion.*') ? 'active' : '' }}">
                <i class="bi bi-gender-ambiguous me-2"></i> Reproducción
            </a>
        </li>
        @endcan
        @canany(['feedlot.corrales.ver', 'feedlot.tropas.ver', 'feedlot.consumos.registrar'])
        <li>
            <span class="d-block" style="color:rgba(255,255,255,.3); font-size:.68rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:.5rem 1.5rem .15rem 2rem;">
                Feedlot</span>
        </li>
        @endcanany
        @can('feedlot.corrales.ver')
        <li class="nav-item">
            <a href="{{ route('feedlot.corrales.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('feedlot.corrales.*') ? 'active' : '' }}">
                <i class="bi bi-grid me-2"></i> Corrales
            </a>
        </li>
        @endcan
        @can('feedlot.tropas.ver')
        <li class="nav-item">
            <a href="{{ route('feedlot.tropas.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('feedlot.tropas.*') ? 'active' : '' }}">
                <i class="bi bi-collection me-2"></i> Tropas
            </a>
        </li>
        @endcan
        @can('feedlot.consumos.registrar')
        <li class="nav-item">
            <a href="{{ route('feedlot.consumos.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('feedlot.consumos.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i> Consumos
            </a>
        </li>
        @endcan

        <li><span class="nav-section">Comercial</span></li>
        @canany(['insumos.catalogo.ver', 'insumos.movimientos.ver'])
        <li>
            <span class="d-block" style="color:rgba(255,255,255,.3); font-size:.68rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; padding:.5rem 1.5rem .15rem 2rem;">
                Insumos
            </span>
        </li>
        @endcanany
        @can('insumos.catalogo.ver')
        <li class="nav-item">
            <a href="{{ route('insumos.catalogo.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('insumos.catalogo.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam me-2"></i> Catálogo
            </a>
        </li>
        @endcan
        @can('insumos.movimientos.ver')
        <li class="nav-item">
            <a href="{{ route('insumos.movimientos.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('insumos.movimientos.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-repeat me-2"></i> Stock / Movimientos
            </a>
        </li>
        @endcan
        <li><span class="nav-section">Compras</span></li>
        @can('compras.proveedores.gestionar')
        <li class="nav-item">
            <a href="{{ route('compras.proveedores.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('compras.proveedores.*') ? 'active' : '' }}">
                <i class="bi bi-building me-2"></i> Proveedores
            </a>
        </li>
        @endcan
        @can('compras.ver')
        <li class="nav-item">
            <a href="{{ route('compras.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('compras.index') ? 'active' : '' }}">
                <i class="bi bi-cart me-2"></i> Órdenes de compra
            </a>
        </li>
        @endcan
        @can('compras.crear')
        <li class="nav-item">
            <a href="{{ route('compras.importar-arca') }}"
               class="nav-link ps-4 {{ request()->routeIs('compras.importar-arca') ? 'active' : '' }}">
                <i class="bi bi-cloud-upload me-2"></i> Importar ARCA
            </a>
        </li>
        @endcan
        <li><span class="nav-section">Ventas</span></li>
        @can('ventas.granos.ver')
        <li class="nav-item">
            <a href="{{ route('ventas.granos.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ventas.granos.*') ? 'active' : '' }}">
                <i class="bi bi-bag me-2"></i> Granos
            </a>
        </li>
        @endcan
        @can('ventas.hacienda.ver')
        <li class="nav-item">
            <a href="{{ route('ventas.hacienda.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('ventas.hacienda.*') ? 'active' : '' }}">
                <i class="bi bi-cursor me-2"></i> Hacienda
            </a>
        </li>
        @endcan

        <li><span class="nav-section">Finanzas</span></li>
        @can('finanzas.cuentas.ver')
        <li class="nav-item">
            <a href="{{ route('finanzas.cuentas.index') }}"
               class="nav-link {{ request()->routeIs('finanzas.cuentas.*') ? 'active' : '' }}">
                <i class="bi bi-bank me-2"></i> Cuentas
            </a>
        </li>
        @endcan
        @can('finanzas.transacciones.ver')
        <li class="nav-item">
            <a href="{{ route('finanzas.transacciones.index') }}"
               class="nav-link {{ request()->routeIs('finanzas.transacciones.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i> Transacciones
            </a>
        </li>
        @endcan
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-receipt me-2"></i> ARCA / IVA
            </a>
        </li>

        <li><span class="nav-section">RRHH</span></li>
        @can('rrhh.personal.ver')
        <li class="nav-item">
            <a href="{{ route('rrhh.personal.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('rrhh.personal.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge me-2"></i> Personal
            </a>
        </li>
        @endcan
        @can('rrhh.jornales.ver')
        <li class="nav-item">
            <a href="{{ route('rrhh.jornales.index') }}"
               class="nav-link ps-4 {{ request()->routeIs('rrhh.jornales.*') ? 'active' : '' }}">
                <i class="bi bi-calendar2-check me-2"></i> Jornales
            </a>
        </li>
        @endcan

        <li><span class="nav-section">Reportes</span></li>
        @can('reportes.productivos.ver')
        <li class="nav-item">
            <a href="{{ route('reportes.productivo') }}"
               class="nav-link ps-4 {{ request()->routeIs('reportes.productivo') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line me-2"></i> Productivo
            </a>
        </li>
        @endcan
        @can('reportes.economicos.ver')
        <li class="nav-item">
            <a href="{{ route('reportes.economico') }}"
               class="nav-link ps-4 {{ request()->routeIs('reportes.economico') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow me-2"></i> Económico
            </a>
        </li>
        @endcan
        @can('reportes.fiscales.ver')
        <li class="nav-item">
            <a href="{{ route('reportes.fiscal') }}"
               class="nav-link ps-4 {{ request()->routeIs('reportes.fiscal') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff me-2"></i> Fiscal / IVA
            </a>
        </li>
        @endcan

        <li><span class="nav-section">Sistema</span></li>
        <li class="nav-item">
            <a href="{{ route('sistema.perfil') }}"
               class="nav-link {{ request()->routeIs('sistema.perfil') ? 'active' : '' }}">
                <i class="bi bi-person-circle me-2"></i> Mi perfil
            </a>
        </li>
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
        @can('admin.roles.gestionar')
        <li class="nav-item">
            <a href="{{ route('sistema.empresa') }}"
               class="nav-link {{ request()->routeIs('sistema.empresa') ? 'active' : '' }}">
                <i class="bi bi-building-gear me-2"></i> Empresa
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('sistema.roles') }}"
               class="nav-link {{ request()->routeIs('sistema.roles') ? 'active' : '' }}">
                <i class="bi bi-shield-lock me-2"></i> Roles y permisos
            </a>
        </li>
        @endcan
        @can('auditoria.ver')
        <li class="nav-item">
            <a href="{{ route('sistema.auditoria') }}"
               class="nav-link {{ request()->routeIs('sistema.auditoria') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i> Auditoría
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