<div>
    <style>
        /* Separan los bloques de la liquidación: el débito y el crédito que
           salen del libro, su saldo técnico, lo que se pagó a cuenta
           (retenciones y devoluciones) y el saldo final. */
        .iva-corte {
            border-left: 2px solid var(--bs-border-color, #dee2e6) !important;
            padding-left: 1.25rem !important;
        }

        /* El aire del otro lado: sin esto el número de la columna anterior
           queda pegado a la línea. */
        .iva-corte-antes {
            padding-right: 1.25rem !important;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">IVA Mensual</h5>
            <small class="text-muted">Débito (ventas) vs. crédito (compras) vs. devoluciones, mes a mes por empresa — {{ $anio }}</small>
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
                <div class="col-md-auto ms-md-auto">
                    <label class="form-label small fw-semibold text-muted mb-1 d-block">Agrupar tablas por</label>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Agrupación del reporte">
                        <button type="button" wire:click="$set('agrupacion', 'empresa')"
                                class="btn {{ $agrupacion === 'empresa' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-building me-1"></i>Empresa
                        </button>
                        <button type="button" wire:click="$set('agrupacion', 'tipo_iva')"
                                class="btn {{ $agrupacion === 'tipo_iva' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-percent me-1"></i>Tipo de IVA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards resumen anual --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-danger">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">IVA Crédito {{ $anio }}</div>
                    <div class="fs-5 fw-bold text-danger">${{ number_format($totalesAnio['credito'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Compras (real, ARCA)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-success">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">IVA Débito {{ $anio }}</div>
                    <div class="fs-5 fw-bold text-success">${{ number_format($totalesAnio['debito'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Ventas (estimado, 10,5%)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 border-primary">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Devolución {{ $anio }}</div>
                    <div class="fs-5 fw-bold text-primary">${{ number_format($totalesAnio['devolucion'], 2, ',', '.') }}</div>
                    <div class="text-muted small">Reintegros acreditados</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-5 {{ $totalesAnio['saldo'] >= 0 ? 'border-warning' : 'border-info' }}">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Saldo {{ $anio }}</div>
                    <div class="fs-5 fw-bold {{ $totalesAnio['saldo'] >= 0 ? 'text-warning-emphasis' : 'text-info' }}">${{ number_format($totalesAnio['saldo'], 2, ',', '.') }}</div>
                    <div class="text-muted small">{{ $totalesAnio['saldo'] >= 0 ? 'A pagar' : 'A favor' }}</div>
                </div>
            </div>
        </div>
    </div>

    @php
        $bloques = [
            'debito'        => ['titulo' => 'IVA Débito fiscal (ventas, estimado 10,5%)', 'icono' => 'bi-bag', 'color' => 'success'],
            'credito'       => ['titulo' => 'IVA Crédito fiscal (compras)', 'icono' => 'bi-receipt', 'color' => 'danger'],
            'saldo_tecnico' => ['titulo' => 'Saldo técnico (débito − crédito)', 'icono' => 'bi-calculator', 'color' => 'info'],
            'retenido'      => ['titulo' => 'IVA retenido (ventas de granos)', 'icono' => 'bi-shield-check', 'color' => 'secondary'],
            'devolucion'    => ['titulo' => 'Devolución de IVA (reintegros acreditados)', 'icono' => 'bi-arrow-return-left', 'color' => 'primary'],
            'saldo'         => ['titulo' => 'Saldo final según signo del saldo técnico', 'icono' => 'bi-calculator-fill', 'color' => 'warning'],
        ];
    @endphp

    @if ($agrupacion === 'empresa')
        @foreach ($empresas as $empresa)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom fw-semibold">
                <i class="bi bi-building me-2 text-primary"></i>{{ $empresa->razon_social }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mes</th>
                            <th class="text-end">IVA Débito</th>
                            <th class="text-end iva-corte-antes">IVA Crédito</th>
                            <th class="text-end iva-corte iva-corte-antes">Saldo técnico</th>
                            <th class="text-end iva-corte">Retenciones</th>
                            <th class="text-end iva-corte-antes">Devoluciones</th>
                            <th class="text-end pe-3 iva-corte">Saldo final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meses as $datosMes)
                            @php $valores = $datosMes['empresas'][$empresa->id]; @endphp
                            <tr>
                                <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                                <td class="text-end font-monospace small">
                                    ${{ number_format($valores['debito'], 2, ',', '.') }}
                                    <a href="{{ route('ventas.granos.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}" target="_blank" class="text-muted ms-1" title="Ver ventas de granos"><i class="bi bi-basket"></i></a>
                                    <a href="{{ route('ventas.hacienda.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}" target="_blank" class="text-muted ms-1" title="Ver ventas de hacienda"><i class="bi bi-cursor"></i></a>
                                </td>
                                <td class="text-end font-monospace small iva-corte-antes">
                                    ${{ number_format($valores['credito'], 2, ',', '.') }}
                                    <a href="{{ route('compras.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id, 'filtroConIva' => 1]) }}" target="_blank" class="text-muted ms-1" title="Ver los comprobantes que suman al crédito fiscal"><i class="bi bi-box-arrow-up-right"></i></a>
                                </td>
                                <td class="text-end font-monospace fw-semibold small iva-corte iva-corte-antes {{ $valores['saldo_tecnico'] < 0 ? 'text-info' : '' }}">${{ number_format($valores['saldo_tecnico'], 2, ',', '.') }}</td>
                                <td class="text-end font-monospace small iva-corte">
                                    ${{ number_format($valores['retenido'], 2, ',', '.') }}
                                    <a href="{{ route('ventas.granos.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id, 'filtroConRetencion' => 1]) }}" target="_blank" class="text-muted ms-1" title="Ver las liquidaciones con retención de IVA"><i class="bi bi-box-arrow-up-right"></i></a>
                                </td>
                                <td class="text-end font-monospace small iva-corte-antes">
                                    ${{ number_format($valores['devolucion'], 2, ',', '.') }}
                                    <a href="{{ route('finanzas.reintegros.index', ['filtroPeriodo' => $datosMes['periodo'], 'empresa' => $empresa->id]) }}" target="_blank" class="text-muted ms-1" title="Ver devoluciones"><i class="bi bi-box-arrow-up-right"></i></a>
                                </td>
                                <td class="text-end pe-3 font-monospace fw-bold small iva-corte {{ $valores['saldo'] < 0 ? 'text-info' : 'text-warning-emphasis' }}">${{ number_format($valores['saldo'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3">Total {{ $anio }}</td>
                            @foreach (['debito', 'credito', 'saldo_tecnico', 'retenido', 'devolucion', 'saldo'] as $clave)
                                @php $totalEmpresa = collect($meses)->sum(fn ($m) => $m['empresas'][$empresa->id][$clave]); @endphp
                                <td @class([
                                    'text-end', 'font-monospace',
                                    'pe-3' => $clave === 'saldo',
                                    'iva-corte' => in_array($clave, ['saldo_tecnico', 'retenido', 'saldo']),
                                    'iva-corte-antes' => in_array($clave, ['credito', 'saldo_tecnico', 'devolucion']),
                                    'text-info' => in_array($clave, ['saldo_tecnico', 'saldo']) && $totalEmpresa < 0,
                                ])>${{ number_format($totalEmpresa, 2, ',', '.') }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endforeach
    @else
        @foreach ($bloques as $clave => $info)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom fw-semibold">
                <i class="bi {{ $info['icono'] }} me-2 text-{{ $info['color'] }}"></i>{{ $info['titulo'] }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Mes</th>
                            @foreach ($empresas as $empresa)
                                <th @class(['text-end', 'iva-corte-antes' => $loop->last])>{{ $empresa->razon_social }}</th>
                            @endforeach
                            <th class="text-end pe-3 iva-corte">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meses as $datosMes)
                        <tr>
                            <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                            @foreach ($empresas as $empresa)
                                @php $valor = $datosMes['empresas'][$empresa->id][$clave]; @endphp
                                <td @class([
                                    'text-end', 'font-monospace', 'small',
                                    'iva-corte-antes' => $loop->last,
                                    'text-info' => in_array($clave, ['saldo_tecnico', 'saldo']) && $valor < 0,
                                ])>
                                    ${{ number_format($valor, 2, ',', '.') }}
                                    @if ($clave === 'debito')
                                        <a href="{{ route('ventas.granos.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}" target="_blank" class="text-muted ms-1" title="Ver ventas de granos"><i class="bi bi-basket"></i></a>
                                        <a href="{{ route('ventas.hacienda.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}" target="_blank" class="text-muted ms-1" title="Ver ventas de hacienda"><i class="bi bi-cursor"></i></a>
                                    @elseif ($clave === 'credito')
                                        <a href="{{ route('compras.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id, 'filtroConIva' => 1]) }}" target="_blank" class="text-muted ms-1" title="Ver los comprobantes que suman al crédito fiscal"><i class="bi bi-box-arrow-up-right"></i></a>
                                    @elseif ($clave === 'retenido')
                                        <a href="{{ route('ventas.granos.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id, 'filtroConRetencion' => 1]) }}" target="_blank" class="text-muted ms-1" title="Ver las liquidaciones con retención de IVA"><i class="bi bi-box-arrow-up-right"></i></a>
                                    @elseif ($clave === 'devolucion')
                                        <a href="{{ route('finanzas.reintegros.index', ['filtroPeriodo' => $datosMes['periodo'], 'empresa' => $empresa->id]) }}" target="_blank" class="text-muted ms-1" title="Ver devoluciones"><i class="bi bi-box-arrow-up-right"></i></a>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end pe-3 font-monospace fw-semibold small iva-corte">${{ number_format($datosMes['total'][$clave], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3">Total {{ $anio }}</td>
                            @foreach ($empresas as $empresa)
                                @php $sumaEmpresa = collect($meses)->sum(fn ($m) => $m['empresas'][$empresa->id][$clave]); @endphp
                                <td @class(['text-end', 'font-monospace', 'iva-corte-antes' => $loop->last])>${{ number_format($sumaEmpresa, 2, ',', '.') }}</td>
                            @endforeach
                            <td class="text-end pe-3 font-monospace iva-corte">${{ number_format($totalesAnio[$clave], 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endforeach
    @endif

    <div class="text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        El saldo final parte del saldo técnico (débito − crédito). Si es positivo, resta retenciones y suma devoluciones; si es negativo, suma retenciones y resta devoluciones. El débito es una estimación (10,5% sobre ventas).
    </div>

</div>
