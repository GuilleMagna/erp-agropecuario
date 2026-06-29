<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Reporte Fiscal / IVA</h5>
            <small class="text-muted">Crédito fiscal (compras) y débito fiscal estimado (ventas) — {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</small>
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

    {{-- Cards resumen IVA --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-danger">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">IVA Crédito fiscal</div>
                    <div class="fs-4 fw-bold text-danger">${{ number_format((float)$totalIvaCredito, 2, ',', '.') }}</div>
                    <div class="text-muted small">IVA en compras del período</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Compras bruto</div>
                    <div class="fs-4 fw-bold text-dark">${{ number_format((float)$totalComprasBruto, 2, ',', '.') }}</div>
                    <div class="text-muted small">Neto: ${{ number_format((float)$totalComprasNeto, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-success">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Ventas ARS</div>
                    <div class="fs-4 fw-bold text-success">${{ number_format((float)$totalVentasGranosArs + (float)$totalVentasHaciendaArs, 2, ',', '.') }}</div>
                    <div class="text-muted small">Granos + Hacienda en pesos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-primary">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Ventas USD</div>
                    <div class="fs-4 fw-bold text-primary">U$S {{ number_format((float)$totalVentasGranosUsd + (float)$totalVentasHaciendaUsd, 2, ',', '.') }}</div>
                    <div class="text-muted small">Granos + Hacienda en dólares</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-3 gap-1">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pane-fiscal-iva" type="button">
                <i class="bi bi-receipt me-1"></i> Comprobantes
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pane-fiscal-imputacion" type="button">
                <i class="bi bi-diagram-3 me-1"></i> Imputación
                @if ($totalSinImputar > 0)
                    <span class="badge bg-warning text-dark rounded-pill ms-1">!</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content">

    {{-- TAB: Comprobantes --}}
    <div class="tab-pane fade show active" id="pane-fiscal-iva" role="tabpanel">
    <div class="row g-4">

        {{-- Compras por tipo comprobante --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-receipt me-2 text-danger"></i>IVA Crédito — Compras por tipo comprobante
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tipo</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Neto</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($comprasPorTipo as $comp)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                        {{ \App\Models\Compra::TIPOS_COMPROBANTE[$comp->tipo_comprobante] ?? $comp->tipo_comprobante }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $comp->cantidad }}</td>
                                <td class="text-end font-monospace text-muted">${{ number_format((float)($comp->sum_subtotal ?? 0), 2, ',', '.') }}</td>
                                <td class="text-end font-monospace text-danger">${{ number_format((float)($comp->sum_iva ?? 0), 2, ',', '.') }}</td>
                                <td class="text-end pe-3 fw-semibold">${{ number_format((float)($comp->sum_total ?? 0), 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Sin compras en el período</td></tr>
                            @endforelse
                            @if ($comprasPorTipo->count())
                            <tr class="table-danger fw-bold">
                                <td class="ps-3">Total IVA crédito</td>
                                <td class="text-center">{{ $comprasPorTipo->sum('cantidad') }}</td>
                                <td class="text-end font-monospace">${{ number_format((float)$totalComprasNeto, 2, ',', '.') }}</td>
                                <td class="text-end font-monospace text-danger">${{ number_format((float)$totalIvaCredito, 2, ',', '.') }}</td>
                                <td class="text-end pe-3">${{ number_format((float)$totalComprasBruto, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Ventas granos (IVA débito estimado) --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-bag me-2 text-success"></i>Ventas de granos por cereal y moneda
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Cereal</th>
                                <th>Moneda</th>
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
                                <td><span class="badge rounded-pill bg-primary-subtle text-primary font-monospace">{{ $vg->moneda }}</span></td>
                                <td class="text-center">{{ $vg->operaciones }}</td>
                                <td class="text-end font-monospace">{{ number_format((float)$vg->total_tn, 3, ',', '.') }}</td>
                                <td class="text-end pe-3 fw-semibold">{{ $vg->moneda === 'USD' ? 'U$S' : '$' }} {{ number_format((float)$vg->total_importe, 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Sin ventas de granos</td></tr>
                            @endforelse
                        </tbody>
                        @if ($ventasGranosPorCereal->count())
                        <tfoot class="table-light fw-semibold small">
                            @if ((float)$totalVentasGranosArs > 0)
                            <tr>
                                <td colspan="4" class="ps-3 text-end">Subtotal ARS</td>
                                <td class="pe-3 text-end">${{ number_format((float)$totalVentasGranosArs, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if ((float)$totalVentasGranosUsd > 0)
                            <tr>
                                <td colspan="4" class="ps-3 text-end">Subtotal USD</td>
                                <td class="pe-3 text-end">U$S {{ number_format((float)$totalVentasGranosUsd, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom fw-semibold">
                    <i class="bi bi-cursor me-2 text-primary"></i>Ventas de hacienda por categoría y moneda
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Categoría</th>
                                <th>Moneda</th>
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
                                <td><span class="badge rounded-pill bg-secondary-subtle text-secondary font-monospace">{{ $vh->moneda }}</span></td>
                                <td class="text-center">{{ $vh->operaciones }}</td>
                                <td class="text-center font-monospace">{{ number_format((int)$vh->total_cabezas, 0, ',', '.') }}</td>
                                <td class="text-end pe-3 fw-semibold">{{ $vh->moneda === 'USD' ? 'U$S' : '$' }} {{ number_format((float)$vh->total_importe, 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Sin ventas de hacienda</td></tr>
                            @endforelse
                        </tbody>
                        @if ($ventasHaciendaPorCategoria->count())
                        <tfoot class="table-light fw-semibold small">
                            @if ((float)$totalVentasHaciendaArs > 0)
                            <tr>
                                <td colspan="4" class="ps-3 text-end">Subtotal ARS</td>
                                <td class="pe-3 text-end">${{ number_format((float)$totalVentasHaciendaArs, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if ((float)$totalVentasHaciendaUsd > 0)
                            <tr>
                                <td colspan="4" class="ps-3 text-end">Subtotal USD</td>
                                <td class="pe-3 text-end">U$S {{ number_format((float)$totalVentasHaciendaUsd, 2, ',', '.') }}</td>
                            </tr>
                            @endif
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Detalle compras --}}
    @if ($detalleCompras->count())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-list-ul me-2 text-secondary"></i>Detalle de compras del período</span>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $detalleCompras->count() }} comprobantes</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fecha</th>
                        <th>Tipo / N°</th>
                        <th>Proveedor</th>
                        <th class="text-end">Neto</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end pe-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detalleCompras as $comp)
                    <tr>
                        <td class="ps-3 text-muted small">{{ \Carbon\Carbon::parse($comp->fecha)->format('d/m/Y') }}</td>
                        <td class="small">
                            <span class="badge bg-secondary-subtle text-secondary">{{ \App\Models\Compra::TIPOS_COMPROBANTE[$comp->tipo_comprobante] ?? $comp->tipo_comprobante }}</span>
                            @if ($comp->numero_comprobante) <span class="font-monospace ms-1">{{ $comp->numero_comprobante }}</span> @endif
                        </td>
                        <td class="small">{{ $comp->proveedor?->nombre ?? '—' }}</td>
                        <td class="text-end font-monospace text-muted small">${{ number_format((float)$comp->subtotal, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace text-danger small">${{ number_format((float)$comp->iva_importe, 2, ',', '.') }}</td>
                        <td class="text-end pe-3 fw-semibold small">${{ number_format((float)$comp->total, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="ps-3">Total</td>
                        <td class="text-end font-monospace">${{ number_format((float)$totalComprasNeto, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace text-danger">${{ number_format((float)$totalIvaCredito, 2, ',', '.') }}</td>
                        <td class="text-end pe-3">${{ number_format((float)$totalComprasBruto, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    </div>{{-- /tab-pane comprobantes --}}

    {{-- TAB: Imputación --}}
    <div class="tab-pane fade" id="pane-fiscal-imputacion" role="tabpanel">

        @if ($totalSinImputar > 0)
        <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Hay <strong>${{ number_format((float)$totalSinImputar, 2, ',', '.') }}</strong> en compras sin imputar a ninguna actividad.</span>
        </div>
        @endif

        <div class="row g-4">

            {{-- Por actividad --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom fw-semibold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>Gastos por actividad
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Actividad</th>
                                    <th class="text-center">Comprobantes</th>
                                    <th class="text-end">Neto</th>
                                    <th class="text-end">IVA crédito</th>
                                    <th class="text-end pe-3">Total</th>
                                    <th class="text-end pe-3" style="width:120px">% del gasto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalGastos = $comprasPorActividad->sum('sum_total') ?: 1; @endphp
                                @forelse ($comprasPorActividad as $act)
                                @php
                                    $actKey   = $act->actividad;
                                    $actLabel = \App\Models\Compra::ACTIVIDADES[$actKey] ?? 'Sin imputar';
                                    $pct      = round((float)$act->sum_total / $totalGastos * 100, 1);
                                    $badgeCls = match($actKey) {
                                        'agricultura' => 'bg-success-subtle text-success',
                                        'ganaderia'   => 'bg-primary-subtle text-primary',
                                        'feedlot'     => 'bg-warning-subtle text-warning-emphasis',
                                        'general'     => 'bg-secondary-subtle text-secondary',
                                        default       => 'bg-danger-subtle text-danger',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge rounded-pill {{ $badgeCls }}">{{ $actLabel }}</span>
                                    </td>
                                    <td class="text-center">{{ $act->cantidad }}</td>
                                    <td class="text-end font-monospace text-muted">${{ number_format((float)$act->sum_subtotal, 2, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-danger">${{ number_format((float)$act->sum_iva, 2, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-semibold">${{ number_format((float)$act->sum_total, 2, ',', '.') }}</td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex align-items-center gap-2 justify-content-end">
                                            <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                                                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <span class="text-muted small font-monospace" style="min-width:36px">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Sin compras en el período</td></tr>
                                @endforelse
                                @if ($comprasPorActividad->count())
                                <tr class="table-light fw-bold">
                                    <td class="ps-3">Total</td>
                                    <td class="text-center">{{ $comprasPorActividad->sum('cantidad') }}</td>
                                    <td class="text-end font-monospace">${{ number_format((float)$comprasPorActividad->sum('sum_subtotal'), 2, ',', '.') }}</td>
                                    <td class="text-end font-monospace text-danger">${{ number_format((float)$comprasPorActividad->sum('sum_iva'), 2, ',', '.') }}</td>
                                    <td class="text-end pe-3">${{ number_format((float)$comprasPorActividad->sum('sum_total'), 2, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Por lote --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom fw-semibold">
                        <i class="bi bi-map me-2 text-success"></i>Gastos por lote
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Lote</th>
                                    <th class="text-center">Comp.</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end pe-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($comprasPorLote as $cl)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold small">{{ $cl->lote?->nombre ?? 'Lote #' . $cl->id_lote }}</div>
                                        @if ($cl->lote?->superficie_ha)
                                            <small class="text-muted">{{ number_format((float)$cl->lote->superficie_ha, 1, ',', '.') }} ha</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $cl->cantidad }}</td>
                                    <td class="text-end font-monospace text-danger small">${{ number_format((float)$cl->sum_iva, 2, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-semibold">${{ number_format((float)$cl->sum_total, 2, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Sin compras imputadas a lotes</td></tr>
                                @endforelse
                                @if ($comprasPorLote->count())
                                <tr class="table-light fw-semibold">
                                    <td class="ps-3">Total imputado</td>
                                    <td class="text-center">{{ $comprasPorLote->sum('cantidad') }}</td>
                                    <td class="text-end font-monospace text-danger">${{ number_format((float)$comprasPorLote->sum('sum_iva'), 2, ',', '.') }}</td>
                                    <td class="text-end pe-3">${{ number_format((float)$comprasPorLote->sum('sum_total'), 2, ',', '.') }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Por campaña --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom fw-semibold">
                        <i class="bi bi-calendar3 me-2 text-warning"></i>Gastos por campaña agrícola
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Campaña</th>
                                    <th class="text-center">Comp.</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end pe-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($comprasPorCampana as $cc)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold small">{{ $cc->campana?->nombre ?? 'Campaña #' . $cc->id_campana }}</div>
                                    </td>
                                    <td class="text-center">{{ $cc->cantidad }}</td>
                                    <td class="text-end font-monospace text-danger small">${{ number_format((float)$cc->sum_iva, 2, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-semibold">${{ number_format((float)$cc->sum_total, 2, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Sin compras imputadas a campañas</td></tr>
                                @endforelse
                                @if ($comprasPorCampana->count())
                                <tr class="table-light fw-semibold">
                                    <td class="ps-3">Total imputado</td>
                                    <td class="text-center">{{ $comprasPorCampana->sum('cantidad') }}</td>
                                    <td class="text-end font-monospace text-danger">${{ number_format((float)$comprasPorCampana->sum('sum_iva'), 2, ',', '.') }}</td>
                                    <td class="text-end pe-3">${{ number_format((float)$comprasPorCampana->sum('sum_total'), 2, ',', '.') }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>{{-- /row --}}
    </div>{{-- /tab-pane imputacion --}}

    </div>{{-- /tab-content --}}

</div>
