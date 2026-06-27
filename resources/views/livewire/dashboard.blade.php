<div>

    {{-- ===== BIENVENIDA ===== --}}
    <div class="card border-0 shadow-sm mb-4"
         style="background: linear-gradient(135deg, #16324F 0%, #1e4d77 100%);">
        <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold text-white mb-1">
                    Buen{{ now()->hour < 12 ? 'os días' : (now()->hour < 20 ? 'as tardes' : 'as noches') }},
                    {{ auth()->user()->nombre ?? auth()->user()->name }}
                </h4>
                <p class="text-white-50 mb-0">
                    <span class="badge bg-white bg-opacity-25 text-white me-2">
                        {{ auth()->user()->getRoleNames()->first() ?? 'Sin rol' }}
                    </span>
                    {{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                </p>
            </div>
            <div class="text-white-50 text-end small">
                <div class="fw-semibold text-white fs-6">{{ auth()->user()->empresa?->razon_social ?? config('app.name') }}</div>
                <div>{{ now()->format('H:i') }} hs</div>
            </div>
        </div>
    </div>

    {{-- ===== ALERTAS ===== --}}
    @if ($insumosAlerta->count() > 0 || $comprasVencidas > 0)
    <div class="alert alert-warning border-warning border-start border-5 rounded-3 d-flex align-items-start gap-3 mb-4 py-3">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning mt-1 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold mb-1">Alertas que requieren atención</div>
            <div class="d-flex flex-wrap gap-2">
                @if ($insumosAlerta->count() > 0)
                    <a href="{{ route('insumos.catalogo.index') }}" class="badge bg-warning text-dark text-decoration-none py-2 px-3">
                        <i class="bi bi-box-seam me-1"></i>
                        {{ $insumosAlerta->count() }} insumo{{ $insumosAlerta->count() > 1 ? 's' : '' }} bajo stock mínimo
                    </a>
                @endif
                @if ($comprasVencidas > 0)
                    <a href="{{ route('compras.index') }}" class="badge bg-danger text-white text-decoration-none py-2 px-3">
                        <i class="bi bi-calendar-x me-1"></i>
                        {{ $comprasVencidas }} compra{{ $comprasVencidas > 1 ? 's' : '' }} vencida{{ $comprasVencidas > 1 ? 's' : '' }} sin pagar
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ===== KPI CARDS ===== --}}
    @php
        $hayKpis = $totalAnimales !== null || $superficieSembrada !== null
            || $saldoCuentas !== null || $comprasPendientesCant !== null
            || $ventasMes !== null || $jornalesPendientes !== null;
    @endphp
    @if ($hayKpis)
    <div class="row g-3 mb-4">

        @can('ganaderia.animales.ver')
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('ganaderia.animales.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;">Hacienda</span>
                            <div class="rounded-2 bg-primary bg-opacity-10 p-2 lh-1">
                                <i class="bi bi-cursor text-primary fs-6"></i>
                            </div>
                        </div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($totalAnimales, 0, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:.78rem;">animales activos</div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('agricultura.siembra.ver')
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('agricultura.siembras.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;">Agricultura</span>
                            <div class="rounded-2 bg-success bg-opacity-10 p-2 lh-1">
                                <i class="bi bi-tree text-success fs-6"></i>
                            </div>
                        </div>
                        <div class="fs-3 fw-bold text-dark">{{ number_format($superficieSembrada, 1, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:.78rem;">ha sembradas activas</div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @if ($ventasMes !== null)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;">Ventas del mes</span>
                        <div class="rounded-2 bg-success bg-opacity-10 p-2 lh-1">
                            <i class="bi bi-bag text-success fs-6"></i>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-success">${{ number_format($ventasMes['total'], 0, ',', '.') }}</div>
                    <div class="text-muted" style="font-size:.78rem;">
                        @if ($ventasMes['granos'] > 0 && $ventasMes['hacienda'] > 0)
                            granos + hacienda
                        @elseif ($ventasMes['granos'] > 0)
                            granos
                        @elseif ($ventasMes['hacienda'] > 0)
                            hacienda
                        @else
                            sin ventas aún
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($comprasPendientesCant !== null)
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('compras.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 card-hover {{ $comprasPendientesCant > 0 ? 'border-warning border' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;">Compras</span>
                            <div class="rounded-2 {{ $comprasPendientesCant > 0 ? 'bg-warning bg-opacity-10' : 'bg-secondary bg-opacity-10' }} p-2 lh-1">
                                <i class="bi bi-cart {{ $comprasPendientesCant > 0 ? 'text-warning' : 'text-secondary' }} fs-6"></i>
                            </div>
                        </div>
                        <div class="fs-3 fw-bold {{ $comprasPendientesCant > 0 ? 'text-warning' : 'text-dark' }}">{{ $comprasPendientesCant }}</div>
                        <div class="text-muted" style="font-size:.78rem;">pendiente{{ $comprasPendientesCant !== 1 ? 's' : '' }} de pago</div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if ($saldoCuentas !== null)
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('finanzas.cuentas.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;">Saldo cuentas</span>
                            <div class="rounded-2 {{ $saldoCuentas >= 0 ? 'bg-primary bg-opacity-10' : 'bg-danger bg-opacity-10' }} p-2 lh-1">
                                <i class="bi bi-bank {{ $saldoCuentas >= 0 ? 'text-primary' : 'text-danger' }} fs-6"></i>
                            </div>
                        </div>
                        <div class="fs-3 fw-bold {{ $saldoCuentas >= 0 ? 'text-primary' : 'text-danger' }}">${{ number_format(abs($saldoCuentas), 0, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:.78rem;">{{ $saldoCuentas < 0 ? 'saldo negativo' : 'disponible' }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endif

        @if ($jornalesPendientes !== null)
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('rrhh.jornales.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 card-hover {{ $jornalesPendientes > 0 ? 'border-warning border' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;">Jornales</span>
                            <div class="rounded-2 {{ $jornalesPendientes > 0 ? 'bg-warning bg-opacity-10' : 'bg-secondary bg-opacity-10' }} p-2 lh-1">
                                <i class="bi bi-people {{ $jornalesPendientes > 0 ? 'text-warning' : 'text-secondary' }} fs-6"></i>
                            </div>
                        </div>
                        <div class="fs-3 fw-bold {{ $jornalesPendientes > 0 ? 'text-warning' : 'text-dark' }}">${{ number_format($jornalesPendientes, 0, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:.78rem;">pendiente de liquidar</div>
                    </div>
                </div>
            </a>
        </div>
        @endif

    </div>
    @endif

    {{-- ===== CUERPO PRINCIPAL ===== --}}
    <div class="row g-4">

        {{-- Columna izquierda: actividad reciente --}}
        <div class="col-lg-8">

            {{-- Últimas operaciones (tabs) --}}
            @if ($ultimasVentasGranos->count() > 0 || $ultimasVentasHacienda->count() > 0 || $ultimasCompras->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                    <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-secondary"></i>Últimas operaciones</span>
                    <ul class="nav nav-pills nav-pills-sm gap-1" id="tabsActividad">
                        @can('ventas.granos.ver')
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 active small" data-bs-toggle="pill" data-bs-target="#pane-vg" type="button">
                                <i class="bi bi-bag me-1"></i> Granos
                            </button>
                        </li>
                        @endcan
                        @can('ventas.hacienda.ver')
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small" data-bs-toggle="pill" data-bs-target="#pane-vh" type="button">
                                <i class="bi bi-cursor me-1"></i> Hacienda
                            </button>
                        </li>
                        @endcan
                        @can('compras.ver')
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small" data-bs-toggle="pill" data-bs-target="#pane-comp" type="button">
                                <i class="bi bi-cart me-1"></i> Compras
                            </button>
                        </li>
                        @endcan
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content">

                        {{-- Ventas granos --}}
                        @can('ventas.granos.ver')
                        <div class="tab-pane fade show active" id="pane-vg">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Fecha</th>
                                            <th>Cereal</th>
                                            <th>Comprador</th>
                                            <th class="text-end">Tn</th>
                                            <th class="text-end pe-3">Importe</th>
                                            <th class="pe-3">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ultimasVentasGranos as $vg)
                                        <tr>
                                            <td class="ps-3 text-muted small">{{ $vg->fecha->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">{{ $vg->cereal_label }}</span>
                                            </td>
                                            <td class="small">{{ $vg->comprador }}</td>
                                            <td class="text-end font-monospace small">{{ number_format((float)$vg->cantidad_tn, 3, ',', '.') }}</td>
                                            <td class="text-end pe-3 fw-semibold small">
                                                {{ $vg->moneda === 'USD' ? 'U$S' : '$' }} {{ number_format((float)$vg->importe_total, 2, ',', '.') }}
                                            </td>
                                            <td class="pe-3">
                                                @php
                                                    $badge = ['borrador'=>'bg-secondary-subtle text-secondary','confirmada'=>'bg-primary-subtle text-primary','cobrada'=>'bg-success-subtle text-success','cancelada'=>'bg-danger-subtle text-danger'][$vg->estado] ?? 'bg-secondary-subtle text-secondary';
                                                @endphp
                                                <span class="badge rounded-pill {{ $badge }} small">{{ $vg->estado_label }}</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Sin ventas de granos registradas</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($ultimasVentasGranos->count() >= 6)
                            <div class="px-3 py-2 border-top">
                                <a href="{{ route('ventas.granos.index') }}" class="small text-primary text-decoration-none">Ver todas las ventas de granos <i class="bi bi-arrow-right"></i></a>
                            </div>
                            @endif
                        </div>
                        @endcan

                        {{-- Ventas hacienda --}}
                        @can('ventas.hacienda.ver')
                        <div class="tab-pane fade {{ !auth()->user()->can('ventas.granos.ver') ? 'show active' : '' }}" id="pane-vh">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Fecha</th>
                                            <th>Categoría</th>
                                            <th>Comprador</th>
                                            <th class="text-center">Cabezas</th>
                                            <th class="text-end pe-3">Importe</th>
                                            <th class="pe-3">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ultimasVentasHacienda as $vh)
                                        <tr>
                                            <td class="ps-3 text-muted small">{{ $vh->fecha->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $vh->categoria_label }}</span>
                                            </td>
                                            <td class="small">{{ $vh->comprador }}</td>
                                            <td class="text-center font-monospace small">{{ $vh->cantidad_cabezas }}</td>
                                            <td class="text-end pe-3 fw-semibold small">
                                                {{ $vh->moneda === 'USD' ? 'U$S' : '$' }} {{ number_format((float)$vh->importe_total, 2, ',', '.') }}
                                            </td>
                                            <td class="pe-3">
                                                @php
                                                    $badge = ['confirmada'=>'bg-primary-subtle text-primary','cobrada'=>'bg-success-subtle text-success','cancelada'=>'bg-danger-subtle text-danger'][$vh->estado] ?? 'bg-secondary-subtle text-secondary';
                                                @endphp
                                                <span class="badge rounded-pill {{ $badge }} small">{{ $vh->estado_label }}</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Sin ventas de hacienda registradas</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($ultimasVentasHacienda->count() >= 6)
                            <div class="px-3 py-2 border-top">
                                <a href="{{ route('ventas.hacienda.index') }}" class="small text-primary text-decoration-none">Ver todas las ventas de hacienda <i class="bi bi-arrow-right"></i></a>
                            </div>
                            @endif
                        </div>
                        @endcan

                        {{-- Compras --}}
                        @can('compras.ver')
                        <div class="tab-pane fade {{ !auth()->user()->can('ventas.granos.ver') && !auth()->user()->can('ventas.hacienda.ver') ? 'show active' : '' }}" id="pane-comp">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Fecha</th>
                                            <th>Proveedor</th>
                                            <th>Comprobante</th>
                                            <th class="text-end pe-3">Total</th>
                                            <th class="pe-3">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ultimasCompras as $comp)
                                        <tr>
                                            <td class="ps-3 text-muted small">{{ $comp->fecha->format('d/m/Y') }}</td>
                                            <td class="small">{{ $comp->proveedor?->nombre ?? '—' }}</td>
                                            <td class="small">
                                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">{{ $comp->tipo_comprobante_label }}</span>
                                                @if ($comp->numero_comprobante) <span class="font-monospace">{{ $comp->numero_comprobante }}</span> @endif
                                            </td>
                                            <td class="text-end pe-3 fw-semibold small">${{ number_format((float)$comp->total, 2, ',', '.') }}</td>
                                            <td class="pe-3">
                                                @php
                                                    $badge = ['pendiente'=>'bg-warning-subtle text-warning-emphasis','recibida'=>'bg-info-subtle text-info','pagada'=>'bg-success-subtle text-success','cancelada'=>'bg-secondary-subtle text-secondary'][$comp->estado] ?? 'bg-secondary-subtle text-secondary';
                                                @endphp
                                                <span class="badge rounded-pill {{ $badge }} small">{{ $comp->estado_label }}</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">Sin compras registradas</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($ultimasCompras->count() >= 6)
                            <div class="px-3 py-2 border-top">
                                <a href="{{ route('compras.index') }}" class="small text-primary text-decoration-none">Ver todas las compras <i class="bi bi-arrow-right"></i></a>
                            </div>
                            @endif
                        </div>
                        @endcan

                    </div>
                </div>
            </div>
            @endif

            {{-- Siembras activas --}}
            @can('agricultura.siembra.ver')
            @if ($siembrasActivas->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom fw-semibold d-flex justify-content-between align-items-center py-3">
                    <span><i class="bi bi-tree me-2 text-success"></i>Siembras en curso</span>
                    <a href="{{ route('agricultura.siembras.index') }}" class="small text-primary text-decoration-none">Ver todas <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Cultivo</th>
                                    <th>Lote</th>
                                    <th>Campaña</th>
                                    <th class="text-end">Ha</th>
                                    <th class="text-end pe-3">Siembra</th>
                                    <th class="pe-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($siembrasActivas as $siem)
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge rounded-pill bg-success-subtle text-success">{{ $siem->cultivo_label }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $siem->lote?->nombre ?? '—' }}</td>
                                    <td class="small text-muted">{{ $siem->campana?->nombre ?? '—' }}</td>
                                    <td class="text-end font-monospace small">{{ number_format((float)$siem->superficie_sembrada_ha, 1, ',', '.') }}</td>
                                    <td class="text-end pe-3 small text-muted">{{ $siem->fecha_siembra?->format('d/m/Y') }}</td>
                                    <td class="pe-3">
                                        @php
                                            $badge = ['sembrada'=>'bg-info-subtle text-info','en_cultivo'=>'bg-success-subtle text-success'][$siem->estado] ?? 'bg-secondary-subtle text-secondary';
                                        @endphp
                                        <span class="badge rounded-pill {{ $badge }} small">{{ $siem->estado_label }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            @endcan

        </div>

        {{-- Columna derecha: resúmenes --}}
        <div class="col-lg-4">

            {{-- Flujo financiero del mes --}}
            @can('finanzas.transacciones.ver')
            @if ($flujoMes !== null)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom fw-semibold py-3">
                    <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Flujo del mes
                    <small class="text-muted fw-normal ms-1">({{ now()->locale('es')->isoFormat('MMMM') }})</small>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Ingresos</span>
                        <span class="fw-semibold text-success">${{ number_format($flujoMes['ingresos'], 2, ',', '.') }}</span>
                    </div>
                    @if ($flujoMes['ingresos'] > 0 || $flujoMes['egresos'] > 0)
                    @php
                        $total = $flujoMes['ingresos'] + $flujoMes['egresos'];
                        $pctIngresos = $total > 0 ? ($flujoMes['ingresos'] / $total) * 100 : 0;
                        $pctEgresos  = $total > 0 ? ($flujoMes['egresos']  / $total) * 100 : 0;
                    @endphp
                    <div class="progress mb-3" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctIngresos }}%"></div>
                        <div class="progress-bar bg-danger" style="width:{{ $pctEgresos }}%"></div>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-muted">Egresos</span>
                        <span class="fw-semibold text-danger">${{ number_format($flujoMes['egresos'], 2, ',', '.') }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Flujo neto</span>
                        <span class="fw-bold fs-5 {{ $flujoMes['neto'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $flujoMes['neto'] >= 0 ? '+' : '' }}${{ number_format($flujoMes['neto'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-white border-top py-2 text-center">
                    <a href="{{ route('finanzas.transacciones.index') }}" class="small text-primary text-decoration-none">Ver transacciones <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            @endif
            @endcan

            {{-- Stock hacienda por categoría --}}
            @can('ganaderia.animales.ver')
            @if ($stockHacienda->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom fw-semibold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cursor me-2 text-primary"></i>Stock por categoría</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill">{{ number_format($totalAnimales, 0, ',', '.') }} total</span>
                </div>
                <div class="card-body p-0">
                    @foreach ($stockHacienda as $cat)
                    @php
                        $pct = $totalAnimales > 0 ? ($cat->total / $totalAnimales) * 100 : 0;
                    @endphp
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small">{{ \App\Models\Animal::CATEGORIAS[$cat->categoria] ?? $cat->categoria }}</span>
                            <span class="fw-semibold small">{{ number_format($cat->total, 0, ',', '.') }}</span>
                        </div>
                        <div class="progress" style="height:4px;">
                            <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                        </div>
                        @if ($cat->peso_promedio)
                        <div class="text-muted mt-1" style="font-size:.72rem;">Peso prom.: {{ number_format((float)$cat->peso_promedio, 0, ',', '.') }} kg</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="card-footer bg-white border-top py-2 text-center">
                    <a href="{{ route('ganaderia.animales.index') }}" class="small text-primary text-decoration-none">Ver animales <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            @endif
            @endcan

            {{-- Insumos con alerta --}}
            @can('insumos.catalogo.ver')
            @if ($insumosAlerta->count() > 0)
            <div class="card border-0 shadow-sm border-warning border">
                <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning fw-semibold py-3">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Insumos bajo mínimo
                    <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $insumosAlerta->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @foreach ($insumosAlerta->take(5) as $ins)
                    <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small fw-semibold">{{ $ins->nombre }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $ins->tipo_label }}</div>
                            </div>
                            <div class="text-end">
                                <div class="small fw-bold text-warning">{{ number_format($ins->stock_actual, 2, ',', '.') }} {{ $ins->unidad }}</div>
                                <div class="text-muted" style="font-size:.72rem;">mín: {{ number_format((float)$ins->stock_minimo, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="card-footer bg-white border-top py-2 text-center">
                    <a href="{{ route('insumos.catalogo.index') }}" class="small text-warning text-decoration-none">Ver catálogo de insumos <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            @endif
            @endcan

        </div>
    </div>

</div>
