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
            <h5 class="mb-0 fw-bold text-dark">CampaÃ±as AgrÃ­colas</h5>
            <small class="text-muted">PerÃ­odos productivos de la empresa</small>
        </div>
        @can('agricultura.campanas.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nueva campaÃ±a
        </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Nombre de campaÃ±aâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroActivo">
                        <option value="">Todos</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <span class="text-muted small">{{ $campanas->total() }} campaÃ±as</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">CampaÃ±a</th>
                        <th class="text-center">Siembras</th>
                        <th class="text-center">Labores</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campanas as $campana)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $campana->nombre }}</div>
                            @if ($campana->descripcion)
                                <small class="text-muted">{{ Str::limit($campana->descripcion, 60) }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary rounded-pill">
                                {{ $campana->siembras_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                {{ $campana->labores_count }}
                            </span>
                        </td>
                        <td>
                            @if ($campana->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger">Inactiva</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('agricultura.campanas.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $campana->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $campana->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $campana->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="{{ $campana->activo ? 'Â¿Desactivar esta campaÃ±a?' : 'Â¿Activar esta campaÃ±a?' }}"
                                    title="{{ $campana->activo ? 'Desactivar' : 'Activar' }}">
                                <i class="bi bi-{{ $campana->activo ? 'toggle-on' : 'toggle-off' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-calendar3 display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron campaÃ±as.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campanas->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $campanas->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar3 me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar campaÃ±a' : 'Nueva campaÃ±a' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-campana">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre" placeholder="Ej: 2024/25, CampaÃ±a Fina 2025">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">DescripciÃ³n</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                          wire:model="descripcion" rows="3"
                                          placeholder="Observaciones sobre la campaÃ±aâ€¦"></textarea>
                                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if ($modoEdicion)
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-campana-activo">
                                    <label class="form-check-label fw-semibold" for="switch-campana-activo">
                                        CampaÃ±a activa
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-campana" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="bi bi-check-lg me-1"></i>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear campaÃ±a' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
