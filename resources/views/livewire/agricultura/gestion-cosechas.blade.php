<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Cosechas</h5>
            <small class="text-muted">Registro de cosechas y rindes por siembra</small>
        </div>
        @can('agricultura.cosecha.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar cosecha
        </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Cultivo o variedadâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">CampaÃ±a</label>
                    <select class="form-select" wire:model.live="filtroCampana">
                        <option value="">Todas</option>
                        @foreach ($campanas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Siembra</label>
                    <select class="form-select" wire:model.live="filtroSiembra">
                        <option value="">Todas</option>
                        @foreach ($siembrasOpciones as $s)
                            <option value="{{ $s->id }}">
                                {{ \App\Models\Siembra::CULTIVOS[$s->cultivo] ?? $s->cultivo }}
                                â€” {{ $s->lote->nombre ?? '?' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $cosechas->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Siembra</th>
                        <th>CampaÃ±a / Lote</th>
                        <th>Fecha</th>
                        <th>Superficie</th>
                        <th>Rinde</th>
                        <th>ProducciÃ³n</th>
                        <th>Humedad</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cosechas as $cosecha)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">
                                {{ $cosecha->siembra ? ($cultivos[$cosecha->siembra->cultivo] ?? $cosecha->siembra->cultivo) : 'â€”' }}
                            </div>
                            @if ($cosecha->siembra?->variedad)
                                <small class="text-muted">{{ $cosecha->siembra->variedad }}</small>
                            @endif
                        </td>
                        <td>
                            <div>{{ $cosecha->siembra?->campana->nombre ?? 'â€”' }}</div>
                            <small class="text-muted">{{ $cosecha->siembra?->lote->nombre ?? 'â€”' }}</small>
                        </td>
                        <td>{{ $cosecha->fecha_cosecha->format('d/m/Y') }}</td>
                        <td>{{ number_format($cosecha->superficie_cosechada_ha, 1) }} ha</td>
                        <td>
                            <span class="fw-semibold">{{ number_format($cosecha->rinde_kg_ha, 0) }}</span>
                            <small class="text-muted">kg/ha</small>
                        </td>
                        <td>
                            @if ($cosecha->produccion_total_kg !== null)
                                <span class="fw-semibold">{{ number_format($cosecha->produccion_total_kg / 1000, 1) }}</span>
                                <small class="text-muted">t</small>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>
                            @if ($cosecha->humedad_porc !== null)
                                {{ number_format($cosecha->humedad_porc, 1) }} %
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('agricultura.cosecha.registrar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $cosecha->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-basket display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron cosechas registradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cosechas->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $cosechas->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-basket me-2 text-success"></i>
                        {{ $modoEdicion ? 'Editar cosecha' : 'Registrar cosecha' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-cosecha">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Siembra cosechada
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Siembra <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_siembra') is-invalid @enderror"
                                        wire:model="id_siembra">
                                    <option value="">Seleccionar siembraâ€¦</option>
                                    @foreach ($siembrasOpciones as $s)
                                        <option value="{{ $s->id }}">
                                            {{ \App\Models\Siembra::CULTIVOS[$s->cultivo] ?? $s->cultivo }}
                                            @if($s->variedad) â€” {{ $s->variedad }}@endif
                                            | Lote: {{ $s->lote->nombre ?? '?' }}
                                            | {{ $s->campana->nombre ?? '?' }}
                                            ({{ $s->fecha_siembra->format('d/m/Y') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_siembra') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Datos de cosecha
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha de cosecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_cosecha') is-invalid @enderror"
                                       wire:model="fecha_cosecha">
                                @error('fecha_cosecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Superficie cosechada <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('superficie_cosechada_ha') is-invalid @enderror"
                                           wire:model="superficie_cosechada_ha" placeholder="0.00">
                                    <span class="input-group-text">ha</span>
                                    @error('superficie_cosechada_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Rinde <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('rinde_kg_ha') is-invalid @enderror"
                                           wire:model="rinde_kg_ha" placeholder="0.00">
                                    <span class="input-group-text">kg/ha</span>
                                    @error('rinde_kg_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Humedad</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100"
                                           class="form-control @error('humedad_porc') is-invalid @enderror"
                                           wire:model="humedad_porc" placeholder="13.5">
                                    <span class="input-group-text">%</span>
                                    @error('humedad_porc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"
                                          placeholder="Condiciones de cosecha, calidad del granoâ€¦"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-cosecha" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="bi bi-check-lg me-1"></i>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar cosecha' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
