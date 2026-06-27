<div>

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 py-2" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">

        {{-- Datos fiscales --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold py-3">
                    <i class="bi bi-building me-2 text-primary"></i>Datos de la empresa
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Razón social <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('razon_social') is-invalid @enderror"
                               wire:model="razon_social" placeholder="Nombre o razón social completa">
                        @error('razon_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">CUIT <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace @error('cuit') is-invalid @enderror"
                                   wire:model="cuit" placeholder="20-12345678-9">
                            @error('cuit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Condición fiscal <span class="text-danger">*</span></label>
                            <select class="form-select @error('condicion_fiscal') is-invalid @enderror"
                                    wire:model="condicion_fiscal">
                                <option value="">— Seleccionar —</option>
                                @foreach ($condicionesFiscales as $valor => $label)
                                    <option value="{{ $valor }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('condicion_fiscal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Domicilio fiscal <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" class="form-control @error('domicilio_fiscal') is-invalid @enderror"
                               wire:model="domicilio_fiscal" placeholder="Calle 1234, Ciudad, Provincia">
                        @error('domicilio_fiscal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Moneda principal <span class="text-danger">*</span></label>
                        <select class="form-select @error('moneda_default') is-invalid @enderror"
                                wire:model="moneda_default">
                            @foreach ($monedas as $valor => $label)
                                <option value="{{ $valor }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('moneda_default')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text text-muted" style="font-size:.78rem;">
                            La moneda predeterminada para nuevas operaciones.
                        </div>
                    </div>

                    <button class="btn btn-primary" wire:click="guardar" wire:loading.attr="disabled">
                        <span wire:loading wire:target="guardar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="guardar" class="bi bi-floppy me-1"></i>
                        Guardar configuración
                    </button>

                </div>
            </div>
        </div>

        {{-- Información complementaria --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom fw-semibold py-3">
                    <i class="bi bi-info-circle me-2 text-secondary"></i>Estado actual
                </div>
                <div class="card-body">
                    @php $empresa = auth()->user()->empresa; @endphp
                    <div class="mb-3">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Razón social</div>
                        <div class="fw-semibold">{{ $empresa?->razon_social ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">CUIT</div>
                        <div class="fw-semibold font-monospace">{{ $empresa?->cuit ?? '—' }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Condición fiscal</div>
                        <div class="fw-semibold">{{ \App\Livewire\Sistema\ConfiguracionEmpresa::CONDICIONES_FISCALES[$empresa?->condicion_fiscal] ?? ($empresa?->condicion_fiscal ?? '—') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Moneda principal</div>
                        <div class="fw-semibold">
                            <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $empresa?->moneda_default ?? 'ARS' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Estado</div>
                        <span class="badge rounded-pill {{ $empresa?->activa ? 'bg-success' : 'bg-danger' }}">
                            {{ $empresa?->activa ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm border-start border-warning border-3">
                <div class="card-body py-3">
                    <div class="d-flex gap-2">
                        <i class="bi bi-exclamation-triangle text-warning fs-5 flex-shrink-0 mt-1"></i>
                        <div class="small text-muted">
                            Los cambios en la razón social y CUIT afectan todos los comprobantes y reportes generados desde este sistema. Verificá los datos antes de guardar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
