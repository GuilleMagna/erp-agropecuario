<div wire:poll.30s>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Sincronización de comprobantes ARCA
            </h5>
            <small class="text-muted">La descarga automática se ejecuta fuera de cPanel mediante GitHub Actions.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('compras.importar-arca') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-upload me-1"></i> Importar ARCA
            </a>
            <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver a Compras
            </a>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-3">
        <i class="bi bi-shield-check fs-4 mt-1"></i>
        <div>
            <div class="fw-semibold mb-1">Sincronización externa segura</div>
            <div class="small">
                El servidor recibe los comprobantes por un endpoint HTTPS autenticado y no ejecuta
                Node, Python, navegadores ni procesos del sistema. La ejecución manual se inicia desde
                el workflow <strong>Sincronizar comprobantes ARCA</strong> en GitHub Actions.
            </div>
        </div>
    </div>

    @php
        $estadoConfig = [
            'running' => ['En ejecución', 'primary', 'arrow-repeat'],
            'success' => ['Exitosa', 'success', 'check-circle'],
            'failure' => ['Fallida', 'danger', 'x-circle'],
            'cancelled' => ['Cancelada', 'secondary', 'slash-circle'],
        ];
    @endphp

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Última ejecución</h6>
            @if ($ultimaEjecucion?->run_url)
                <a href="{{ $ultimaEjecucion->run_url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-github me-1"></i> Ver en GitHub
                </a>
            @endif
        </div>
        <div class="card-body">
            @if ($ultimaEjecucion)
                @php($config = $estadoConfig[$ultimaEjecucion->estado] ?? [$ultimaEjecucion->estado, 'secondary', 'question-circle'])
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <span class="badge text-bg-{{ $config[1] }} fs-6">
                        <i class="bi bi-{{ $config[2] }} me-1"></i>{{ $config[0] }}
                    </span>
                    <span class="text-muted small">Inicio: {{ $ultimaEjecucion->iniciado_at?->format('d/m/Y H:i:s') }}</span>
                    @if ($ultimaEjecucion->finalizado_at)
                        <span class="text-muted small">Fin: {{ $ultimaEjecucion->finalizado_at->format('d/m/Y H:i:s') }}</span>
                    @endif
                    @if ($ultimaEjecucion->desde && $ultimaEjecucion->hasta)
                        <span class="text-muted small">
                            Período: {{ $ultimaEjecucion->desde->format('d/m/Y') }}–{{ $ultimaEjecucion->hasta->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
                <div class="row g-2 text-center mb-3">
                    @foreach ([
                        ['Empresas', $ultimaEjecucion->empresas_procesadas.'/'.$ultimaEjecucion->empresas_total, 'building'],
                        ['Recibidos', $ultimaEjecucion->recibidos, 'download'],
                        ['Importados', $ultimaEjecucion->importadas, 'check-lg'],
                        ['Duplicados', $ultimaEjecucion->duplicadas, 'files'],
                        ['Errores', $ultimaEjecucion->errores, 'exclamation-triangle'],
                    ] as [$titulo, $valor, $icono])
                        <div class="col-6 col-md">
                            <div class="border rounded p-2 h-100">
                                <i class="bi bi-{{ $icono }} text-primary"></i>
                                <div class="fs-5 fw-semibold">{{ $valor }}</div>
                                <div class="small text-muted">{{ $titulo }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($ultimaEjecucion->eventos)
                    <details>
                        <summary class="small fw-semibold text-primary" style="cursor:pointer">Ver registro de eventos</summary>
                        <div class="list-group list-group-flush mt-2 small">
                            @foreach (array_reverse($ultimaEjecucion->eventos) as $evento)
                                <div class="list-group-item px-0 py-2">
                                    <span class="font-monospace text-muted me-2">
                                        {{ \Illuminate\Support\Carbon::parse($evento['fecha'])->format('H:i:s') }}
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary me-2">{{ $evento['tipo'] }}</span>
                                    {{ $evento['mensaje'] ?? '' }}
                                    @if (($evento['tipo'] ?? '') === 'empresa')
                                        <span class="text-muted ms-2">
                                            Recibidos: {{ $evento['recibidos'] ?? 0 }},
                                            importados: {{ $evento['importadas'] ?? 0 }},
                                            duplicados: {{ $evento['duplicadas'] ?? 0 }},
                                            errores: {{ $evento['errores'] ?? 0 }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            @else
                <p class="text-muted mb-0">Todavía no hay ejecuciones registradas. La próxima sincronización aparecerá aquí.</p>
            @endif
        </div>
    </div>

    @if ($ejecuciones->count() > 1)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body"><h6 class="mb-0 fw-semibold">Ejecuciones recientes</h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Inicio</th>
                            <th>Estado</th>
                            <th class="text-end">Importados</th>
                            <th class="text-end">Duplicados</th>
                            <th class="text-end">Errores</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ejecuciones->skip(1) as $ejecucion)
                            @php($config = $estadoConfig[$ejecucion->estado] ?? [$ejecucion->estado, 'secondary', 'question-circle'])
                            <tr>
                                <td>{{ $ejecucion->iniciado_at?->format('d/m/Y H:i:s') }}</td>
                                <td><span class="badge text-bg-{{ $config[1] }}">{{ $config[0] }}</span></td>
                                <td class="text-end">{{ $ejecucion->importadas }}</td>
                                <td class="text-end">{{ $ejecucion->duplicadas }}</td>
                                <td class="text-end">{{ $ejecucion->errores }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($empresasArca->isEmpty())
        <div class="alert alert-warning">
            No hay empresas activas para ARCA. Configuralas desde
            <a href="{{ route('admin.empresas.index') }}" class="alert-link">Sistema → Empresas</a>.
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Empresas habilitadas</h6>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach ($empresasArca as $empresa)
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-building me-1"></i>{{ $empresa->razon_social }}
                            <span class="font-monospace ms-1">{{ $empresa->arca_cuit_representado ?? $empresa->cuit }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
