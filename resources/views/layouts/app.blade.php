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
            width: 260px; height: 100vh; position: fixed;
            top: 0; left: 0; background: #16324F; z-index: 100;
            overflow-y: auto; overflow-x: hidden;
            scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.15) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
        .sidebar-brand {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .sidebar-brand .brand-text {
            color: white; font-weight: 700; font-size: .95rem;
        }
        /* Links dentro de secciones */
        .sidebar .nav-link {
            color: rgba(255,255,255,.72); padding: .45rem 1.25rem;
            font-size: .82rem; border-radius: 0; transition: all .15s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white; background: rgba(255,255,255,.1);
        }
        /* Botón de sección colapsable */
        .sidebar .sec-toggle {
            display: flex; align-items: center; justify-content: space-between;
            width: 100%; background: none; border: none; cursor: pointer;
            color: rgba(255,255,255,.55); font-size: .68rem; font-weight: 700;
            letter-spacing: .8px; text-transform: uppercase;
            padding: .9rem 1.25rem .35rem; transition: color .15s;
        }
        .sidebar .sec-toggle:hover { color: rgba(255,255,255,.85); }
        .sidebar .sec-toggle .chevron {
            font-size: .65rem; opacity: .6; transition: transform .2s;
        }
        .sidebar .sec-toggle[aria-expanded="true"] .chevron { transform: rotate(180deg); }
        /* Sub-etiquetas dentro de una sección (Agricultura, Ganadería…) */
        .sidebar .sub-label {
            color: rgba(255,255,255,.28); font-size: .63rem; font-weight: 700;
            letter-spacing: .5px; text-transform: uppercase;
            padding: .5rem 1.25rem .1rem 1.75rem; display: block;
        }
        /* Dashboard directo */
        .sidebar .nav-link-top {
            color: rgba(255,255,255,.8); padding: .5rem 1.25rem;
            font-size: .85rem; font-weight: 500;
            display: block; text-decoration: none; transition: all .15s;
        }
        .sidebar .nav-link-top:hover, .sidebar .nav-link-top.active {
            color: white; background: rgba(255,255,255,.1);
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

@php
    $secProd     = request()->routeIs('campos.*','agricultura.*','ganaderia.*','feedlot.*');
    $secInsumos  = request()->routeIs('insumos.*');
    $secCompras  = request()->routeIs('compras.*');
    $secVentas   = request()->routeIs('ventas.*');
    $secFinanzas = request()->routeIs('finanzas.*');
    $secRrhh     = request()->routeIs('rrhh.*');
    $secReportes = request()->routeIs('reportes.*');
    $secSistema  = request()->routeIs('admin.*','sistema.*');
@endphp
<nav class="sidebar">

    <div class="sidebar-brand d-flex align-items-center gap-2">
        <x-application-logo-white style="height:28px; width:auto;" />
        <span class="brand-text">{{ config('app.name') }}</span>
    </div>

    <ul class="nav flex-column mt-1 pb-2">

        {{-- Dashboard --}}
        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link-top {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        {{-- ── PRODUCCIÓN ──────────────────────────────────── --}}
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secProd"
                    aria-expanded="{{ $secProd ? 'true' : 'false' }}">
                <span><i class="bi bi-tree me-2"></i>Producción</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secProd ? 'show' : '' }}" id="secProd">
                @can('campos.lotes.ver')
                <a href="{{ route('campos.lotes.index') }}"
                   class="nav-link {{ request()->routeIs('campos.lotes.*') ? 'active' : '' }}">
                    <i class="bi bi-map me-2"></i> Lotes
                </a>
                @endcan
                @canany(['agricultura.campanas.ver','agricultura.siembra.ver','agricultura.labores.ver','agricultura.cosecha.ver'])
                <span class="sub-label">Agricultura</span>
                @endcanany
                @can('agricultura.campanas.ver')
                <a href="{{ route('agricultura.campanas.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('agricultura.campanas.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar3 me-2"></i> Campañas
                </a>
                @endcan
                @can('agricultura.siembra.ver')
                <a href="{{ route('agricultura.siembras.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('agricultura.siembras.*') ? 'active' : '' }}">
                    <i class="bi bi-flower1 me-2"></i> Siembras
                </a>
                @endcan
                @can('agricultura.labores.ver')
                <a href="{{ route('agricultura.labores.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('agricultura.labores.*') ? 'active' : '' }}">
                    <i class="bi bi-tools me-2"></i> Labores
                </a>
                @endcan
                @can('agricultura.cosecha.ver')
                <a href="{{ route('agricultura.cosechas.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('agricultura.cosechas.*') ? 'active' : '' }}">
                    <i class="bi bi-basket me-2"></i> Cosechas
                </a>
                @endcan
                @canany(['ganaderia.animales.ver','ganaderia.movimientos.ver','ganaderia.pesajes.ver','ganaderia.sanidad.ver','ganaderia.reproduccion.ver'])
                <span class="sub-label">Ganadería</span>
                @endcanany
                @can('ganaderia.animales.ver')
                <a href="{{ route('ganaderia.animales.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('ganaderia.animales.*') ? 'active' : '' }}">
                    <i class="bi bi-heart-pulse me-2"></i> Animales
                </a>
                @endcan
                @can('ganaderia.movimientos.ver')
                <a href="{{ route('ganaderia.movimientos.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('ganaderia.movimientos.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right me-2"></i> Movimientos
                </a>
                @endcan
                @can('ganaderia.pesajes.ver')
                <a href="{{ route('ganaderia.pesajes.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('ganaderia.pesajes.*') ? 'active' : '' }}">
                    <i class="bi bi-speedometer me-2"></i> Pesajes
                </a>
                @endcan
                @can('ganaderia.sanidad.ver')
                <a href="{{ route('ganaderia.sanidad.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('ganaderia.sanidad.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-plus me-2"></i> Sanidad
                </a>
                @endcan
                @can('ganaderia.reproduccion.ver')
                <a href="{{ route('ganaderia.reproduccion.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('ganaderia.reproduccion.*') ? 'active' : '' }}">
                    <i class="bi bi-gender-ambiguous me-2"></i> Reproducción
                </a>
                @endcan
                @canany(['feedlot.corrales.ver','feedlot.tropas.ver','feedlot.consumos.registrar'])
                <span class="sub-label">Feedlot</span>
                @endcanany
                @can('feedlot.corrales.ver')
                <a href="{{ route('feedlot.corrales.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('feedlot.corrales.*') ? 'active' : '' }}">
                    <i class="bi bi-grid me-2"></i> Corrales
                </a>
                @endcan
                @can('feedlot.tropas.ver')
                <a href="{{ route('feedlot.tropas.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('feedlot.tropas.*') ? 'active' : '' }}">
                    <i class="bi bi-collection me-2"></i> Tropas
                </a>
                @endcan
                @can('feedlot.consumos.registrar')
                <a href="{{ route('feedlot.consumos.index') }}"
                   class="nav-link ps-4 {{ request()->routeIs('feedlot.consumos.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text me-2"></i> Consumos
                </a>
                @endcan
            </div>
        </li>

        {{-- ── INSUMOS ──────────────────────────────────────── --}}
        @canany(['insumos.catalogo.ver','insumos.movimientos.ver'])
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secInsumos"
                    aria-expanded="{{ $secInsumos ? 'true' : 'false' }}">
                <span><i class="bi bi-box-seam me-2"></i>Insumos</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secInsumos ? 'show' : '' }}" id="secInsumos">
                @can('insumos.catalogo.ver')
                <a href="{{ route('insumos.catalogo.index') }}"
                   class="nav-link {{ request()->routeIs('insumos.catalogo.*') ? 'active' : '' }}">
                    <i class="bi bi-tags me-2"></i> Catálogo
                </a>
                @endcan
                @can('insumos.movimientos.ver')
                <a href="{{ route('insumos.movimientos.index') }}"
                   class="nav-link {{ request()->routeIs('insumos.movimientos.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-repeat me-2"></i> Stock / Movimientos
                </a>
                @endcan
            </div>
        </li>
        @endcanany

        {{-- ── COMPRAS ──────────────────────────────────────── --}}
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secCompras"
                    aria-expanded="{{ $secCompras ? 'true' : 'false' }}">
                <span><i class="bi bi-cart me-2"></i>Compras</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secCompras ? 'show' : '' }}" id="secCompras">
                @can('compras.proveedores.gestionar')
                <a href="{{ route('compras.proveedores.index') }}"
                   class="nav-link {{ request()->routeIs('compras.proveedores.*') ? 'active' : '' }}">
                    <i class="bi bi-building me-2"></i> Proveedores
                </a>
                @endcan
                @can('compras.ver')
                <a href="{{ route('compras.index') }}"
                   class="nav-link {{ request()->routeIs('compras.index') ? 'active' : '' }}">
                    <i class="bi bi-receipt me-2"></i> Comprobantes
                </a>
                @endcan
                @can('compras.crear')
                <a href="{{ route('compras.importar-arca') }}"
                   class="nav-link {{ request()->routeIs('compras.importar-arca') ? 'active' : '' }}">
                    <i class="bi bi-cloud-upload me-2"></i> Importar ARCA
                </a>
                @endcan
            </div>
        </li>

        {{-- ── VENTAS ───────────────────────────────────────── --}}
        @canany(['ventas.granos.ver','ventas.hacienda.ver'])
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secVentas"
                    aria-expanded="{{ $secVentas ? 'true' : 'false' }}">
                <span><i class="bi bi-bag me-2"></i>Ventas</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secVentas ? 'show' : '' }}" id="secVentas">
                @can('ventas.granos.ver')
                <a href="{{ route('ventas.granos.index') }}"
                   class="nav-link {{ request()->routeIs('ventas.granos.*') ? 'active' : '' }}">
                    <i class="bi bi-brightness-high me-2"></i> Granos
                </a>
                @endcan
                @can('ventas.hacienda.ver')
                <a href="{{ route('ventas.hacienda.index') }}"
                   class="nav-link {{ request()->routeIs('ventas.hacienda.*') ? 'active' : '' }}">
                    <i class="bi bi-cursor me-2"></i> Hacienda
                </a>
                @endcan
            </div>
        </li>
        @endcanany

        {{-- ── FINANZAS ─────────────────────────────────────── --}}
        @canany(['finanzas.cuentas.ver','finanzas.transacciones.ver','finanzas.periodos.gestionar','finanzas.reintegros.gestionar'])
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secFinanzas"
                    aria-expanded="{{ $secFinanzas ? 'true' : 'false' }}">
                <span><i class="bi bi-bank me-2"></i>Finanzas</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secFinanzas ? 'show' : '' }}" id="secFinanzas">
                @can('finanzas.cuentas.ver')
                <a href="{{ route('finanzas.cuentas.index') }}"
                   class="nav-link {{ request()->routeIs('finanzas.cuentas.*') ? 'active' : '' }}">
                    <i class="bi bi-wallet2 me-2"></i> Cuentas
                </a>
                @endcan
                @can('finanzas.transacciones.ver')
                <a href="{{ route('finanzas.transacciones.index') }}"
                   class="nav-link {{ request()->routeIs('finanzas.transacciones.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right me-2"></i> Transacciones
                </a>
                @endcan
                @can('finanzas.periodos.gestionar')
                <a href="{{ route('finanzas.periodos.index') }}"
                   class="nav-link {{ request()->routeIs('finanzas.periodos.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check me-2"></i> Períodos Fiscales
                </a>
                @endcan
                @can('finanzas.reintegros.gestionar')
                <a href="{{ route('finanzas.reintegros.index') }}"
                   class="nav-link {{ request()->routeIs('finanzas.reintegros.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-return-left me-2"></i> Reintegros IVA
                </a>
                @endcan
            </div>
        </li>
        @endcanany

        {{-- ── RRHH ─────────────────────────────────────────── --}}
        @canany(['rrhh.personal.ver','rrhh.jornales.ver'])
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secRrhh"
                    aria-expanded="{{ $secRrhh ? 'true' : 'false' }}">
                <span><i class="bi bi-people me-2"></i>RRHH</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secRrhh ? 'show' : '' }}" id="secRrhh">
                @can('rrhh.personal.ver')
                <a href="{{ route('rrhh.personal.index') }}"
                   class="nav-link {{ request()->routeIs('rrhh.personal.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge me-2"></i> Personal
                </a>
                @endcan
                @can('rrhh.jornales.ver')
                <a href="{{ route('rrhh.jornales.index') }}"
                   class="nav-link {{ request()->routeIs('rrhh.jornales.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check me-2"></i> Jornales
                </a>
                @endcan
            </div>
        </li>
        @endcanany

        {{-- ── REPORTES ─────────────────────────────────────── --}}
        @canany(['reportes.productivos.ver','reportes.economicos.ver','reportes.fiscales.ver'])
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secReportes"
                    aria-expanded="{{ $secReportes ? 'true' : 'false' }}">
                <span><i class="bi bi-bar-chart-line me-2"></i>Reportes</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secReportes ? 'show' : '' }}" id="secReportes">
                @can('reportes.productivos.ver')
                <a href="{{ route('reportes.productivo') }}"
                   class="nav-link {{ request()->routeIs('reportes.productivo') ? 'active' : '' }}">
                    <i class="bi bi-graph-up me-2"></i> Productivo
                </a>
                @endcan
                @can('reportes.economicos.ver')
                <a href="{{ route('reportes.economico') }}"
                   class="nav-link {{ request()->routeIs('reportes.economico') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow me-2"></i> Económico
                </a>
                @endcan
                @can('reportes.fiscales.ver')
                <a href="{{ route('reportes.fiscal') }}"
                   class="nav-link {{ request()->routeIs('reportes.fiscal') ? 'active' : '' }}">
                    <i class="bi bi-receipt-cutoff me-2"></i> Fiscal / IVA
                </a>
                @endcan
            </div>
        </li>
        @endcanany

        {{-- ── SISTEMA ──────────────────────────────────────── --}}
        <li>
            <button class="sec-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#secSistema"
                    aria-expanded="{{ $secSistema ? 'true' : 'false' }}">
                <span><i class="bi bi-gear me-2"></i>Sistema</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse {{ $secSistema ? 'show' : '' }}" id="secSistema">
                <a href="{{ route('sistema.perfil') }}"
                   class="nav-link {{ request()->routeIs('sistema.perfil') ? 'active' : '' }}">
                    <i class="bi bi-person-circle me-2"></i> Mi perfil
                </a>
                @can('admin.usuarios.ver')
                <a href="{{ route('admin.usuarios.index') }}"
                   class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                    <i class="bi bi-people me-2"></i> Usuarios
                </a>
                @endcan
                @can('admin.establecimientos.gestionar')
                <a href="{{ route('admin.establecimientos.index') }}"
                   class="nav-link {{ request()->routeIs('admin.establecimientos.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt me-2"></i> Establecimientos
                </a>
                @endcan
                @can('admin.roles.gestionar')
                <a href="{{ route('sistema.empresa') }}"
                   class="nav-link {{ request()->routeIs('sistema.empresa') ? 'active' : '' }}">
                    <i class="bi bi-building-gear me-2"></i> Empresa
                </a>
                <a href="{{ route('sistema.roles') }}"
                   class="nav-link {{ request()->routeIs('sistema.roles') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock me-2"></i> Roles y permisos
                </a>
                @endcan
                @can('auditoria.ver')
                <a href="{{ route('sistema.auditoria') }}"
                   class="nav-link {{ request()->routeIs('sistema.auditoria') ? 'active' : '' }}">
                    <i class="bi bi-journal-text me-2"></i> Auditoría
                </a>
                @endcan
            </div>
        </li>

        {{-- Cerrar sesión --}}
        <li class="nav-item mt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link w-100 text-start border-0"
                        style="background:none;">
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
