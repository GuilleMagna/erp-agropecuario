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
            <h5 class="mb-0 fw-bold text-dark">Siembras</h5>
            <small class="text-muted">Registro de siembras por lote y campaÃ±a</small>
        </div>
        @can('agricultura.siembra.crear')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nueva siembra
        </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Cultivo, variedadâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">CampaÃ±a</label>
                    <select class="form-select" wire:model.live="filtroCampana">
                        <option value="">Todas</option>
                        @foreach ($campanas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Lote</label>
                    <select class="form-select" wire:model.live="filtroLote">
                        <option value="">Todos</option>
                        @foreach ($lotes as $l)
                            <option value="{{ $l->id }}">{{ $l->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Cultivo</label>
                    <select class="form-select" wire:model.live="filtroCultivo">
                        <option value="">Todos</option>
                        @foreach ($cultivos as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        @foreach ($estados as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $siembras->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Cultivo</th>
                        <th>CampaÃ±a</th>
                        <th>Lote</th>
                        <th>Fecha</th>
                        <th>Superficie</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siembras as $siembra)
                    @php
                        $badgeEstado = [
                            'planificada' => 'bg-warning-subtle text-warning-emphasis',
                            'sembrada'    => 'bg-primary-subtle text-primary',
                            'en_cultivo'  => 'bg-info-subtle text-info-emphasis',
                            'cosechada'   => 'bg-success-subtle text-success',
                            'perdida'     => 'bg-danger-subtle text-danger',
                        ][$siembra->estado] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $siembra->cultivo_label }}</div>
                            @if ($siembra->variedad)
                                <small class="text-muted">{{ $siembra->variedad }}</small>
                            @endif
                        </td>
                        <td>{{ $siembra->campana->nombre ?? 'â€”' }}</td>
                        <td>{{ $siembra->lote->nombre ?? 'â€”' }}</td>
                        <td>{{ $siembra->fecha_siembra->format('d/m/Y') }}</td>
                        <td>{{ number_format($siembra->superficie_sembrada_ha, 1) }} ha</td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">
                                {{ $siembra->estado_label }}
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            @can('agricultura.siembra.editar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $siembra->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-flower1 display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron siembras.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($siembras->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $siembras->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-flower1 me-2 text-success"></i>
                        {{ $modoEdicion ? 'Editar siembra' : 'Nueva siembra' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-siembra">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            IdentificaciÃ³n
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CampaÃ±a <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_campana') is-invalid @enderror"
                                        wire:model="id_campana">
                                    <option value="">Seleccionar campaÃ±aâ€¦</option>
                                    @foreach ($campanas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_campana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lote <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_lote') is-invalid @enderror"
                                        wire:model="id_lote">
                                    <option value="">Seleccionar loteâ€¦</option>
                                    @foreach ($lotes as $l)
                                        <option value="{{ $l->id }}">{{ $l->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_lote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cultivo <span class="text-danger">*</span></label>
                                <select class="form-select @error('cultivo') is-invalid @enderror"
                                        wire:model="cultivo">
                                    <option value="">Seleccionar cultivoâ€¦</option>
                                    @foreach ($cultivos as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('cultivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Variedad</label>
                                <input type="text" class="form-control @error('variedad') is-invalid @enderror"
                                       wire:model="variedad" placeholder="Ej: DM 4250, SY 210">
                                @error('variedad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Detalles de siembra
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha de siembra <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_siembra') is-invalid @enderror"
                                       wire:model="fecha_siembra">
                                @error('fecha_siembra') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Superficie sembrada <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('superficie_sembrada_ha') is-invalid @enderror"
                                           wire:model="superficie_sembrada_ha" placeholder="0.00">
                                    <span class="input-group-text">ha</span>
                                    @error('superficie_sembrada_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Densidad de siembra</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('densidad_siembra') is-invalid @enderror"
                                           wire:model="densidad_siembra" placeholder="0.00">
                                    <span class="input-group-text">kg/ha</span>
                                    @error('densidad_siembra') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select @error('estado') is-invalid @enderror"
                                        wire:model="estado">
                                    @foreach ($estados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"
                                          placeholder="Condiciones de la siembra, notasâ€¦"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-siembra" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="bi bi-check-lg me-1"></i>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar siembra' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
