<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Reporte Productivo</h5>
            <small class="text-muted">Hacienda, agricultura e insumos — {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</small>
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
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Campaña agrícola</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroCampana">
                        <option value="">Todas</option>
                        @foreach ($campanas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Período desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaDesde">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Período hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaHasta">
                </div>
            </div>
        </div>
    </div>

    {{-- Cards resumen --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Stock hacienda</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($stockTotal, 0, ',', '.') }}</div>
                    <div class="text-muted small">cabezas activas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Categorías</div>
                    <div class="fs-3 fw-bold text-dark">{{ $stockPorCategoria->count() }}</div>
                    <div class="text-muted small">distintas en stock</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Superficie sembrada</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($siembrasPorCultivo->sum('total_ha'), 1, ',', '.') }}</div>
                    <div class="text-muted small">hectáreas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $insumosAlerta > 0 ? 'border-warning border' : '' }}">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Alertas de insumos</div>
                    <div class="fs-3 fw-bold {{ $insumosAlerta > 0 ? 'text-warning' : 'text-success' }}">{{ $insumosAlerta }}</div>
                    <div class="text-muted small">bajo stock mínimo</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-3 gap-1" id="tabsProductivo" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-ganaderia" data-bs-toggle="pill" data-bs-target="#pane-ganaderia" type="button">
                <i class="bi bi-cursor me-1"></i> Ganadería
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-agricultura" data-bs-toggle="pill" data-bs-target="#pane-agricultura" type="button">
                <i class="bi bi-tree me-1"></i> Agricultura
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link position-relative" id="tab-insumos" data-bs-toggle="pill" data-bs-target="#pane-insumos" type="button">
                <i class="bi bi-box-seam me-1"></i> Insumos
                @if ($insumosAlerta > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">{{ $insumosAlerta }}</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- TAB: Ganadería --}}
        <div class="tab-pane fade show active" id="pane-ganaderia" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-cursor me-2 text-primary"></i>Stock por categoría
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Categoría</th>
                                        <th class="text-center">Cabezas</th>
                                        <th class="text-end pe-3">Peso prom. (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($stockPorCategoria as $cat)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill bg-primary-subtle text-primary">
                                                {{ \App\Models\Animal::CATEGORIAS[$cat->categoria] ?? $cat->categoria }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($cat->total, 0, ',', '.') }}</td>
                                        <td class="text-end pe-3 font-monospace">
                                            {{ $cat->peso_promedio ? number_format((float)$cat->peso_promedio, 0, ',', '.') : '—' }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Sin animales registrados</td></tr>
                                    @endforelse
                                    @if ($stockPorCategoria->count())
                                    <tr class="table-light fw-bold">
                                        <td class="ps-3">Total</td>
                                        <td class="text-center">{{ number_format($stockTotal, 0, ',', '.') }}</td>
                                        <td></td>
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
                            <i class="bi bi-arrow-left-right me-2 text-success"></i>Movimientos del período
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Tipo</th>
                                        <th class="text-center">Operaciones</th>
                                        <th class="text-end pe-3">Cabezas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($movimientosPeriodo as $mov)
                                    @php
                                        $positivo = in_array($mov->tipo, \App\Models\Movimiento::TIPOS_POSITIVOS);
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill {{ $positivo ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ \App\Models\Movimiento::TIPOS[$mov->tipo] ?? $mov->tipo }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $mov->operaciones }}</td>
                                        <td class="text-end pe-3 fw-semibold {{ $positivo ? 'text-success' : 'text-danger' }}">
                                            {{ $positivo ? '+' : '−' }}{{ number_format((int)$mov->total_cabezas, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Sin movimientos en el período</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: Agricultura --}}
        <div class="tab-pane fade" id="pane-agricultura" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom fw-semibold">
                            <i class="bi bi-tree me-2 text-success"></i>Superficie sembrada por cultivo
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Cultivo</th>
                                        <th class="text-center">Lotes</th>
                                        <th class="text-end pe-3">Superficie (ha)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($siembrasPorCultivo as $siem)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill bg-success-subtle text-success">
                                                {{ \App\Models\Siembra::CULTIVOS[$siem->cultivo] ?? $siem->cultivo }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $siem->lotes }}</td>
                                        <td class="text-end pe-3 font-monospace fw-semibold">{{ number_format((float)$siem->total_ha, 1, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Sin siembras registradas</td></tr>
                                    @endforelse
                                    @if ($siembrasPorCultivo->count())
                                    <tr class="table-light fw-bold">
                                        <td class="ps-3">Total</td>
                                        <td class="text-center">{{ $siembrasPorCultivo->sum('lotes') }}</td>
                                        <td class="text-end pe-3 font-monospace">{{ number_format($siembrasPorCultivo->sum('total_ha'), 1, ',', '.') }} ha</td>
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
                            <i class="bi bi-bag me-2 text-warning"></i>Cosechas del período
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Cultivo</th>
                                        <th class="text-end">Producción (tn)</th>
                                        <th class="text-end">Superficie (ha)</th>
                                        <th class="text-end pe-3">Rinde (kg/ha)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cosechasPorCultivo as $cos)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                                {{ $cos['cultivo_label'] }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace fw-bold">{{ number_format($cos['total_tn'], 3, ',', '.') }}</td>
                                        <td class="text-end font-monospace">{{ number_format($cos['total_ha'], 1, ',', '.') }}</td>
                                        <td class="text-end pe-3 font-monospace">{{ $cos['rinde_kg_ha'] ? number_format($cos['rinde_kg_ha'], 0, ',', '.') : '—' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Sin cosechas en el período</td></tr>
                                    @endforelse
                                    @if ($cosechasPorCultivo->count())
                                    <tr class="table-light fw-bold">
                                        <td class="ps-3">Total</td>
                                        <td class="text-end font-monospace">{{ number_format($cosechasPorCultivo->sum('total_tn'), 3, ',', '.') }} tn</td>
                                        <td class="text-end font-monospace">{{ number_format($cosechasPorCultivo->sum('total_ha'), 1, ',', '.') }} ha</td>
                                        <td></td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: Insumos --}}
        <div class="tab-pane fade" id="pane-insumos" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom fw-semibold d-flex justify-content-between">
                    <span><i class="bi bi-box-seam me-2 text-primary"></i>Stock actual de insumos</span>
                    @if ($insumosAlerta > 0)
                        <span class="badge bg-warning text-dark rounded-pill">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $insumosAlerta }} bajo mínimo
                        </span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Insumo</th>
                                <th>Tipo</th>
                                <th class="text-end">Stock actual</th>
                                <th class="text-end">Mínimo</th>
                                <th class="text-end pe-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($insumosStock as $ins)
                            @php
                                $stockActual = $ins->stock_actual;
                                $bajo = $ins->stock_minimo !== null && $stockActual < (float)$ins->stock_minimo;
                            @endphp
                            <tr class="{{ $bajo ? 'table-warning' : '' }}">
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $ins->nombre }}</div>
                                    @if ($ins->marca) <small class="text-muted">{{ $ins->marca }}</small> @endif
                                </td>
                                <td><span class="badge rounded-pill bg-secondary-subtle text-secondary">{{ $ins->tipo_label }}</span></td>
                                <td class="text-end font-monospace fw-bold {{ $bajo ? 'text-warning-emphasis' : '' }}">
                                    {{ number_format($stockActual, 2, ',', '.') }} {{ $ins->unidad }}
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    {{ $ins->stock_minimo !== null ? number_format((float)$ins->stock_minimo, 2, ',', '.') . ' ' . $ins->unidad : '—' }}
                                </td>
                                <td class="text-end pe-3">
                                    @if ($bajo)
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Bajo mínimo</span>
                                    @elseif ($stockActual <= 0)
                                        <span class="badge bg-danger">Sin stock</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success">OK</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">No hay insumos registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}

</div>
