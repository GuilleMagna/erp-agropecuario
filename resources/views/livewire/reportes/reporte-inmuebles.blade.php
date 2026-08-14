<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Inmuebles</h5>
            <small class="text-muted">Ingresos por alquiler vs. gastos (expensas, servicios) mes a mes y acumulado — {{ $anio }}</small>
        </div>
        @can('reportes.exportar')
        <button class="btn btn-outline-success btn-sm" wire:click="exportarCsv" wire:loading.attr="disabled">
            <span wire:loading wire:target="exportarCsv" class="spinner-border spinner-border-sm me-1"></span>
            <i wire:loading.remove wire:target="exportarCsv" class="bi bi-file-earmark-spreadsheet me-1"></i>
            Exportar CSV
        </button>
        @endcan
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
                    <div class="text-muted small">Alquileres cobrados</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-danger">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Gastos {{ $anio }}</div>
                    <div class="fs-5 fw-bold text-danger">${{ number_format($totalesAnio['gastos'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Expensas, EPE, gas, agua, etc.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-5 {{ $totalesAnio['resultado'] >= 0 ? 'border-primary' : 'border-warning' }}">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Resultado {{ $anio }}</div>
                    <div class="fs-5 fw-bold {{ $totalesAnio['resultado'] >= 0 ? 'text-primary' : 'text-warning-emphasis' }}">${{ number_format($totalesAnio['resultado'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Neto del año</div>
                </div>
            </div>
        </div>
    </div>

    @forelse ($inmuebles as $inmueble)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-building me-2 text-secondary"></i>{{ $inmueble->nombre }}
            @if ($inmueble->localidad)
                <span class="text-muted fw-normal">({{ $inmueble->localidad }})</span>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mes</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Gastos</th>
                        <th class="text-end">Resultado</th>
                        <th class="text-end pe-3">Acumulado {{ $anio }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meses as $datosMes)
                        @php $i = $datosMes['inmuebles'][$inmueble->id]; @endphp
                        <tr>
                            <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                            <td class="text-end font-monospace text-success small">
                                ${{ number_format($i['ingresos'], 2, ',', '.') }}
                                <a href="{{ route('finanzas.inmuebles.index', ['mesSeleccionado' => $datosMes['periodo']]) }}"
                                   target="_blank" class="text-muted ms-1" title="Ver ingresos del período">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                            <td class="text-end font-monospace text-danger small">
                                ${{ number_format($i['gastos'], 2, ',', '.') }}
                                <a href="{{ route('finanzas.gastos.index', ['mesSeleccionado' => $datosMes['periodo']]) }}"
                                   target="_blank" class="text-muted ms-1" title="Ver gastos del período">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                            <td class="text-end font-monospace small fw-semibold {{ $i['resultado'] < 0 ? 'text-warning-emphasis' : '' }}">${{ number_format($i['resultado'], 2, ',', '.') }}</td>
                            <td class="text-end pe-3 font-monospace small {{ $i['acumulado'] < 0 ? 'text-warning-emphasis' : 'text-primary' }}">${{ number_format($i['acumulado'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
        <div class="alert alert-secondary">No hay inmuebles cargados todavía.</div>
    @endforelse

    {{-- Consolidado --}}
    @if ($inmuebles->count() > 1)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom fw-semibold">
            <i class="bi bi-collection me-2 text-dark"></i>Consolidado
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mes</th>
                        <th class="text-end">Ingresos</th>
                        <th class="text-end">Gastos</th>
                        <th class="text-end pe-3">Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meses as $datosMes)
                        <tr>
                            <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                            <td class="text-end font-monospace text-success small">${{ number_format($datosMes['total']['ingresos'], 2, ',', '.') }}</td>
                            <td class="text-end font-monospace text-danger small">${{ number_format($datosMes['total']['gastos'], 2, ',', '.') }}</td>
                            <td class="text-end pe-3 font-monospace small fw-semibold {{ $datosMes['total']['resultado'] < 0 ? 'text-warning-emphasis' : '' }}">${{ number_format($datosMes['total']['resultado'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">Total {{ $anio }}</td>
                        <td class="text-end font-monospace">${{ number_format($totalesAnio['ingresos'], 2, ',', '.') }}</td>
                        <td class="text-end font-monospace">${{ number_format($totalesAnio['gastos'], 2, ',', '.') }}</td>
                        <td class="text-end pe-3 font-monospace">${{ number_format($totalesAnio['resultado'], 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

</div>
