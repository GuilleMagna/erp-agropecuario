<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Sincronizar comprobantes desde ARCA
            </h5>
            <small class="text-muted">Automatiza el login en el portal ARCA/AFIP y descarga los comprobantes recibidos</small>
        </div>
        <a href="{{ route('compras.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver a Compras
        </a>
    </div>

    @if ($empresasArca->isEmpty())
        {{-- Sin empresas configuradas --}}
        <div class="alert alert-warning d-flex align-items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4 mt-1"></i>
            <div>
                <div class="fw-semibold mb-1">Ninguna empresa tiene ARCA configurado</div>
                <div class="small">
                    Para sincronizar comprobantes, configurá las credenciales ARCA de al menos una empresa en
                    <a href="{{ route('admin.empresas.index') }}" class="alert-link">Sistema → Empresas</a>.
                </div>
            </div>
        </div>
    @else
        {{-- Formulario --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Seleccioná empresa y período</h6>

                <div class="row g-3 align-items-end">

                    {{-- Empresa --}}
                    <div class="col-sm-12 col-md-4">
                        <label class="form-label small fw-medium">Empresa</label>
                        <select wire:model="idEmpresa"
                                class="form-select @error('idEmpresa') is-invalid @enderror">
                            <option value="">— Seleccionar —</option>
                            @foreach ($todasEmpresas as $emp)
                                <option value="{{ $emp->id }}"
                                    @if (!$emp->arca_activo) disabled @endif>
                                    {{ $emp->razon_social }}
                                    @if (!$emp->arca_activo) (sin ARCA) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('idEmpresa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-medium">Desde</label>
                        <input type="date" wire:model="desde"
                               class="form-control @error('desde') is-invalid @enderror">
                        @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-medium">Hasta</label>
                        <input type="date" wire:model="hasta"
                               class="form-control @error('hasta') is-invalid @enderror">
                        @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <button wire:click="sincronizar"
                                wire:loading.attr="disabled"
                                class="btn btn-primary w-100"
                                @if($estado === 'procesando') disabled @endif>
                            <span wire:loading.remove wire:target="sincronizar">
                                <i class="bi bi-cloud-arrow-down me-1"></i> Sincronizar
                            </span>
                            <span wire:loading wire:target="sincronizar">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Consultando... (hasta 90 seg)
                            </span>
                        </button>
                    </div>
                </div>

                <div class="mt-3 d-flex align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" wire:model="debug" id="chkDebug">
                        <label class="form-check-label small text-muted" for="chkDebug">
                            Guardar screenshots de debug
                        </label>
                    </div>
                    <span class="text-muted small">|</span>
                    <small class="text-muted">
                        <i class="bi bi-terminal me-1"></i>
                        Con navegador visible:
                        <code>php artisan arca:sincronizar --debug</code>
                    </small>
                </div>
            </div>
        </div>

        {{-- Empresas con ARCA activo --}}
        @if ($empresasArca->count() > 1)
            <div class="mb-4">
                <small class="text-muted fw-semibold text-uppercase">Empresas con ARCA configurado</small>
                <div class="d-flex gap-2 mt-1 flex-wrap">
                    @foreach ($empresasArca as $emp)
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-building me-1"></i>{{ $emp->razon_social }}
                            <span class="font-monospace ms-1">{{ $emp->arca_cuit_representado ?? $emp->cuit }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Resultado OK --}}
        @if ($estado === 'ok')
            <div class="mb-3">
                <div class="text-muted small fw-semibold mb-2">
                    <i class="bi bi-building me-1"></i>{{ $empresaSincronizada }}
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="display-6 fw-bold text-success">{{ $resultado['importadas'] }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-check-circle me-1"></i>Importadas</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="display-6 fw-bold text-secondary">{{ $resultado['duplicadas'] }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-files me-1"></i>Ya existían</div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center py-3">
                        <div class="display-6 fw-bold {{ $resultado['errores'] > 0 ? 'text-warning' : 'text-secondary' }}">
                            {{ $resultado['errores'] }}
                        </div>
                        <div class="small text-muted mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Con errores</div>
                    </div>
                </div>
            </div>

            @if ($resultado['importadas'] > 0)
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Se importaron <strong>{{ $resultado['importadas'] }} comprobante(s)</strong> de <strong>{{ $empresaSincronizada }}</strong>.
                    <a href="{{ route('compras.index') }}" class="alert-link ms-2">Ver en compras →</a>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    No hay comprobantes nuevos en el período para <strong>{{ $empresaSincronizada }}</strong>.
                </div>
            @endif
        @endif

        {{-- Error --}}
        @if ($estado === 'error')
            <div class="alert alert-danger">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Error:</strong> {{ $mensajeError }}
                <div class="mt-2 small">
                    Para diagnosticar con navegador visible:<br>
                    <code>php artisan arca:sincronizar --debug --desde {{ $desde }} --hasta {{ $hasta }}</code>
                </div>
            </div>
        @endif
    @endif
</div>
