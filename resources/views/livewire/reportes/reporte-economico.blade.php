<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Reporte Económico</h5>
            <small class="text-muted">Ventas, compras y flujo financiero — {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</small>
        </div>
        @can('reportes.exportar')
        <button class="btn btn-outline-success btn-sm" wire:click="exportarCsv" wire:loading.attr="disabled">
            <span wire:loading wire:target="exportarCsv" class="spinner-border spinner-border-sm me-1"></span>
            <i wire:loading.remove wire:target="exportarCsv" class="bi bi-file-earmark-spreadsheet me-1"></i>
            Exportar CSV
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Período desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaDesde">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Período hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaHasta">
                </div>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-success">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Ventas granos</div>
                    <div class="fs-4 fw-bold text-success">${{ number_format((float)$totalVentasGranos, 2, ',', '.') }}</div>
                    <div class="text-muted small">{{ $ventasGranosPorCereal->sum('operaciones') }} operaciones</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-primary">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Ventas hacienda</div>
                    <div class="fs-4 fw-bold text-primary">${{ number_format((float)$totalVentasHacienda, 2, ',', '.') }}</div>
                    <div class="text-muted small">{{ $ventasHaciendaPorCategoria->sum('operaciones') }} operaciones</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-danger">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Compras</div>
                    <div class="fs-4 fw-bold text-danger">${{ number_format((float)$totalCompras, 2, ',', '.') }}</div>
                    <div class="text-muted small">{{ $comprasPorEstado->whereNotIn('estado', ['cancelada'])->sum('cantidad') }} comprobantes</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            @php $saldoFinanciero = (float)$totalIngresos - (float)$totalEgresos; @endphp
            <div class="card border-0 shadow-sm h-100 border-start border-5 {{ $saldoFinanciero >= 0 ? 'border-success' : 'border-danger' }}">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Saldo cuentas</div>
                    <div class="fs-4 fw-bold {{ $saldoCuentas >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format((float)$saldoCuentas, 2, ',', '.') }}</div>
                    <div class="text-muted small">{{ $cuentas->count() }} cuentas activas</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-3 gap-1">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-eco-ventas" type="button">
                <i class="bi bi-bag me-1"></i> Ventas
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-eco-compras" type="button">
                <i class="bi bi-cart me-1"></i> Compras
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-eco-finanzas" type="button">
                <i class="bi bi-bank me-1"></i> Finanzas
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-eco-rrhh" type="button">
                <i class="bi bi-people me-1"></i> RRHH
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- TAB: Ventas --}}
        <div class="tab-pane fade show active" id="pane-eco-ventas" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-bag me-2 text-success"></i>Ventas de granos por cereal
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Cereal</th>
                                        <th class="text-center">Ops.</th>
                                        <th class="text-end">Tn</th>
                                        <th class="text-end pe-3">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ventasGranosPorCereal as $vg)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                                {{ \App\Models\VentaGrano::CEREALES[$vg->cereal] ?? $vg->cereal }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $vg->operaciones }}</td>
                                        <td class="text-end font-monospace">{{ number_format((float)$vg->total_tn, 3, ',', '.') }}</td>
                                        <td class="text-end pe-3 fw-semibold">${{ number_format((float)$vg->total_importe, 2, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Sin ventas de granos</td></tr>
                                    @endforelse
                                    @if ($ventasGranosPorCereal->count())
                                    <tr class="table-light fw-bold">
                                        <td class="ps-3">Total</td>
                                        <td class="text-center">{{ $ventasGranosPorCereal->sum('operaciones') }}</td>
                                        <td class="text-end font-monospace">{{ number_format($ventasGranosPorCereal->sum('total_tn'), 3, ',', '.') }}</td>
                                        <td class="text-end pe-3">${{ number_format((float)$totalVentasGranos, 2, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-cursor me-2 text-primary"></i>Ventas de hacienda por categoría
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Categoría</th>
                                        <th class="text-center">Ops.</th>
                                        <th class="text-center">Cabezas</th>
                                        <th class="text-end pe-3">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ventasHaciendaPorCategoria as $vh)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill bg-primary-subtle text-primary">
                                                {{ \App\Models\VentaHacienda::CATEGORIAS[$vh->categoria] ?? $vh->categoria }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $vh->operaciones }}</td>
                                        <td class="text-center font-monospace">{{ number_format((int)$vh->total_cabezas, 0, ',', '.') }}</td>
                                        <td class="text-end pe-3 fw-semibold">${{ number_format((float)$vh->total_importe, 2, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Sin ventas de hacienda</td></tr>
                                    @endforelse
                                    @if ($ventasHaciendaPorCategoria->count())
                                    <tr class="table-light fw-bold">
                                        <td class="ps-3">Total</td>
                                        <td class="text-center">{{ $ventasHaciendaPorCategoria->sum('operaciones') }}</td>
                                        <td class="text-center font-monospace">{{ number_format($ventasHaciendaPorCategoria->sum('total_cabezas'), 0, ',', '.') }}</td>
                                        <td class="text-end pe-3">${{ number_format((float)$totalVentasHacienda, 2, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: Compras --}}
        <div class="tab-pane fade" id="pane-eco-compras" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-cart me-2 text-danger"></i>Compras del período por estado
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Estado</th>
                                <th class="text-center">Comprobantes</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($comprasPorEstado as $comp)
                            @php
                                $badge = [
                                    'pendiente'  => 'bg-warning-subtle text-warning-emphasis',
                                    'recibida'   => 'bg-info-subtle text-info-emphasis',
                                    'pagada'     => 'bg-success-subtle text-success',
                                    'cancelada'  => 'bg-secondary-subtle text-secondary',
                                ][$comp->estado] ?? 'bg-secondary-subtle text-secondary';
                            @endphp
                            <tr>
                                <td class="ps-3"><span class="badge rounded-pill {{ $badge }}">{{ \App\Models\Compra::ESTADOS[$comp->estado] ?? $comp->estado }}</span></td>
                                <td class="text-center">{{ $comp->cantidad }}</td>
                                <td class="text-end font-monospace">${{ number_format((float)($comp->sum_subtotal ?? 0), 2, ',', '.') }}</td>
                                <td class="text-end font-monospace text-muted">${{ number_format((float)($comp->total_iva ?? 0), 2, ',', '.') }}</td>
                                <td class="text-end pe-3 fw-semibold">${{ number_format((float)($comp->total_importe ?? 0), 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Sin compras en el período</td></tr>
                            @endforelse
                            @if ($comprasPorEstado->count())
                            <tr class="table-light fw-bold">
                                <td class="ps-3">Total</td>
                                <td class="text-center">{{ $comprasPorEstado->sum('cantidad') }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-end pe-3">${{ number_format((float)$totalCompras, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB: Finanzas --}}
        <div class="tab-pane fade" id="pane-eco-finanzas" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom fw-semibold d-flex justify-content-between">
                            <span><i class="bi bi-arrow-up-circle me-2 text-success"></i>Ingresos por categoría</span>
                            <span class="text-success fw-bold">${{ number_format((float)$totalIngresos, 2, ',', '.') }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th class="ps-3">Categoría</th><th class="text-end pe-3">Importe</th></tr></thead>
                                <tbody>
                                    @forelse ($ingresosPorCategoria as $ing)
                                    <tr>
                                        <td class="ps-3 small">{{ $ing->categoria }}</td>
                                        <td class="text-end pe-3 font-monospace text-success fw-semibold">${{ number_format((float)$ing->total, 2, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3 small">Sin ingresos</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom fw-semibold d-flex justify-content-between">
                            <span><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Egresos por categoría</span>
                            <span class="text-danger fw-bold">${{ number_format((float)$totalEgresos, 2, ',', '.') }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th class="ps-3">Categoría</th><th class="text-end pe-3">Importe</th></tr></thead>
                                <tbody>
                                    @forelse ($egresosPorCategoria as $egr)
                                    <tr>
                                        <td class="ps-3 small">{{ $egr->categoria }}</td>
                                        <td class="text-end pe-3 font-monospace text-danger fw-semibold">${{ number_format((float)$egr->total, 2, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3 small">Sin egresos</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom fw-semibold"><i class="bi bi-bank me-2 text-primary"></i>Saldo por cuenta</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th class="ps-3">Cuenta</th><th class="text-end pe-3">Saldo</th></tr></thead>
                                <tbody>
                                    @forelse ($cuentas as $cta)
                                    <tr>
                                        <td class="ps-3 small">{{ $cta->nombre }}</td>
                                        <td class="text-end pe-3 font-monospace fw-semibold {{ $cta->saldo_actual >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($cta->saldo_actual, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3 small">Sin cuentas</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @php $saldoFinanciero = (float)$totalIngresos - (float)$totalEgresos; @endphp
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Ingresos del período</span>
                                <span class="text-success fw-semibold">${{ number_format((float)$totalIngresos, 2, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Egresos del período</span>
                                <span class="text-danger fw-semibold">${{ number_format((float)$totalEgresos, 2, ',', '.') }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Flujo neto</span>
                                <span class="{{ $saldoFinanciero >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($saldoFinanciero, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: RRHH --}}
        <div class="tab-pane fade" id="pane-eco-rrhh" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <div class="text-muted small fw-semibold text-uppercase mb-2">Jornales pendientes</div>
                            <div class="fs-2 fw-bold text-warning">${{ number_format((float)$jornalesPendientes, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <div class="text-muted small fw-semibold text-uppercase mb-2">Jornales liquidados</div>
                            <div class="fs-2 fw-bold text-success">${{ number_format((float)$jornalesLiquidados, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <div class="text-muted small fw-semibold text-uppercase mb-2">Total jornales del período</div>
                            <div class="fs-2 fw-bold text-dark">${{ number_format((float)$jornalesPendientes + (float)$jornalesLiquidados, 2, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}

</div>
