<div>

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
            'credito'    => ['titulo' => 'IVA Crédito fiscal (compras)', 'icono' => 'bi-receipt', 'color' => 'danger'],
            'debito'     => ['titulo' => 'IVA Débito fiscal (ventas, estimado 10,5%)', 'icono' => 'bi-bag', 'color' => 'success'],
            'devolucion' => ['titulo' => 'Devolución de IVA (reintegros acreditados)', 'icono' => 'bi-arrow-return-left', 'color' => 'primary'],
            'saldo'      => ['titulo' => 'Saldo de IVA (débito − crédito + devolución)', 'icono' => 'bi-calculator', 'color' => 'warning'],
        ];
    @endphp

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
                            <th class="text-end">{{ $empresa->razon_social }}</th>
                        @endforeach
                        <th class="text-end pe-3">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meses as $datosMes)
                    <tr>
                        <td class="ps-3">{{ $datosMes['nombre'] }}</td>
                        @foreach ($empresas as $empresa)
                            @php $valor = $datosMes['empresas'][$empresa->id][$clave]; @endphp
                            <td class="text-end font-monospace small {{ $clave === 'saldo' && $valor < 0 ? 'text-info' : '' }}">
                                ${{ number_format($valor, 2, ',', '.') }}
                                @if ($clave === 'credito')
                                    <a href="{{ route('compras.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}"
                                       target="_blank" class="text-muted ms-1" title="Ver comprobantes de compra del período">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @elseif ($clave === 'debito')
                                    <a href="{{ route('ventas.granos.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}"
                                       target="_blank" class="text-muted ms-1" title="Ver ventas de granos del período">
                                        <i class="bi bi-basket"></i>
                                    </a>
                                    <a href="{{ route('ventas.hacienda.index', ['filtroFechaDesde' => $datosMes['desde'], 'filtroFechaHasta' => $datosMes['hasta'], 'empresa' => $empresa->id]) }}"
                                       target="_blank" class="text-muted ms-1" title="Ver ventas de hacienda del período">
                                        <i class="bi bi-cursor"></i>
                                    </a>
                                @elseif ($clave === 'devolucion')
                                    <a href="{{ route('finanzas.reintegros.index', ['filtroPeriodo' => $datosMes['periodo'], 'empresa' => $empresa->id]) }}"
                                       target="_blank" class="text-muted ms-1" title="Ver reintegros de IVA del período">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-end pe-3 font-monospace fw-semibold small">
                            ${{ number_format($datosMes['total'][$clave], 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">Total {{ $anio }}</td>
                        @foreach ($empresas as $empresa)
                            @php $sumaEmpresa = collect($meses)->sum(fn ($m) => $m['empresas'][$empresa->id][$clave]); @endphp
                            <td class="text-end font-monospace">${{ number_format($sumaEmpresa, 2, ',', '.') }}</td>
                        @endforeach
                        <td class="text-end pe-3 font-monospace">${{ number_format($totalesAnio[$clave], 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach

    <div class="text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        El IVA débito es una estimación (10,5% sobre el neto de venta de granos y hacienda del período); no descuenta el IVA de comisiones/deducciones del corredor.
    </div>

</div>
