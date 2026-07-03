<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Ganancias</h5>
            <small class="text-muted">Resultado impositivo estimado (ingresos − egresos, neto de IVA) mes a mes y acumulado — {{ $anio }}</small>
        </div>
        @can('reportes.exportar')
        <button class="btn btn-outline-success btn-sm" wire:click="exportarCsv" wire:loading.attr="disabled">
            <span wire:loading wire:target="exportarCsv" class="spinner-border spinner-border-sm me-1"></span>
            <i wire:loading.remove wire:target="exportarCsv" class="bi bi-file-earmark-spreadsheet me-1"></i>
            Exportar CSV
        </button>
        @endcan
    </div>

    <div class="alert alert-secondary d-flex align-items-start gap-2 py-2 mb-4">
        <i class="bi bi-info-circle-fill mt-1"></i>
        <div>
            Este resultado es la <strong>base impositiva estimada</strong> (ingresos de ventas menos egresos de compras,
            ambos netos de IVA, más gastos NO ARCA). No aplica el mínimo no imponible, deducciones personales ni la
            escala progresiva del Art. 94 — eso depende de datos que no están en el ERP (cargas de familia, aportes,
            forma societaria) y debe calcularlo tu contador sobre este resultado ya consolidado.
        </div>
    </div>

    {{-- Filtro --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Año</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroAnio">
                        @foreach (range(now()->year, now()->year - 4) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards resumen anual --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-success">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Ingresos {{ $anio }}</div>
                    <div class="fs-5 fw-bold text-success">${{ number_format($totalesAnio['ingresos'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Ventas granos + hacienda (neto)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-danger">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Egresos {{ $anio }}</div>
                    <div class="fs-5 fw-bold text-danger">${{ number_format($totalesAnio['egresos'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Compras (neto) + gastos NO ARCA</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 {{ $totalesAnio['resultado'] >= 0 ? 'border-primary' : 'border-warning' }}">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Resultado {{ $anio }}</div>
                    <div class="fs-5 fw-bold {{ $totalesAnio['resultado'] >= 0 ? 'text-primary' : 'text-warning-emphasis' }}">${{ number_format($totalesAnio['resultado'], 2, ',', '.') }}</div>
                    <div class="text-muted small">{{ $totalesAnio['resultado'] >= 0 ? 'Ganancia' : 'Pérdida' }} del período fiscal</div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($empresas as $empresa)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-building me-2 text-secondary"></i>{{ $empresa->razon_social }}
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mes</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Egresos</th>
                        <th class="text-end">Resultado</th>
                        <th class="text-end pe-3">Acumulado {{ $anio }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meses as $datosMes)
                        @php $e = $datosMes['empresas'][$empresa->id]; @endphp
                        <tr>
                            <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                            <td class="text-end font-monospace text-success small">${{ number_format($e['ingresos'], 2, ',', '.') }}</td>
                            <td class="text-end font-monospace text-danger small">${{ number_format($e['egresos'], 2, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-semibold {{ $e['resultado'] < 0 ? 'text-warning-emphasis' : '' }}">${{ number_format($e['resultado'], 2, ',', '.') }}</td>
                            <td class="text-end pe-3 font-monospace small {{ $e['acumulado'] < 0 ? 'text-warning-emphasis' : 'text-primary' }}">${{ number_format($e['acumulado'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Consolidado --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-collection me-2 text-dark"></i>Consolidado (incluye gastos NO ARCA, sin asignar a una empresa puntual)
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mes</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Egresos compras</th>
                        <th class="text-end">Gastos NO ARCA</th>
                        <th class="text-end">Resultado</th>
                        <th class="text-end pe-3">Acumulado {{ $anio }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meses as $datosMes)
                        <tr>
                            <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                            <td class="text-end font-monospace text-success small">${{ number_format($datosMes['total']['ingresos'], 2, ',', '.') }}</td>
                            <td class="text-end font-monospace text-danger small">${{ number_format($datosMes['total']['egresos'] - $datosMes['total']['gastos_no_arca'], 2, ',', '.') }}</td>
                            <td class="text-end font-monospace text-danger small">${{ number_format($datosMes['total']['gastos_no_arca'], 2, ',', '.') }}</td>
                            <td class="text-end font-monospace small fw-semibold {{ $datosMes['total']['resultado'] < 0 ? 'text-warning-emphasis' : '' }}">${{ number_format($datosMes['total']['resultado'], 2, ',', '.') }}</td>
                            <td class="text-end pe-3 font-monospace small fw-semibold {{ $datosMes['total']['acumulado'] < 0 ? 'text-warning-emphasis' : 'text-primary' }}">${{ number_format($datosMes['total']['acumulado'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @php
                    $totalGastosNoArca = collect($meses)->sum(fn ($m) => $m['total']['gastos_no_arca']);
                    $totalEgresosCompras = $totalesAnio['egresos'] - $totalGastosNoArca;
                @endphp
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">Total {{ $anio }}</td>
                        <td class="text-end font-monospace">${{ number_format($totalesAnio['ingresos'], 2, ',', '.') }}</td>
                        <td class="text-end font-monospace">${{ number_format($totalEgresosCompras, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace">${{ number_format($totalGastosNoArca, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace">${{ number_format($totalesAnio['resultado'], 2, ',', '.') }}</td>
                        <td class="text-end pe-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
