<div>

    {{-- Mensajes flash --}}
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

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Lotes</h5>
            <small class="text-muted">Unidades de manejo productivo por establecimiento</small>
        </div>
        @can('campos.lotes.crear')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo lote
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text"
                               class="form-control border-start-0 ps-0"
                               placeholder="Nombre o cÃ³digoâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                    <select class="form-select" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        @foreach ($tiposLote as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $lotes->total() }} lotes</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Lote</th>
                        <th>Establecimiento</th>
                        <th>Tipo</th>
                        <th>Superficie</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lotes as $lote)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $lote->nombre }}</div>
                            @if ($lote->codigo)
                                <small class="text-muted font-monospace">{{ $lote->codigo }}</small>
                            @endif
                        </td>
                        <td>
                            @if ($lote->establecimiento)
                                {{ $lote->establecimiento->nombre }}
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeTipo = [
                                    'agricola' => 'bg-success-subtle text-success',
                                    'ganadero' => 'bg-warning-subtle text-warning-emphasis',
                                    'mixto'    => 'bg-info-subtle text-info-emphasis',
                                    'forestal' => 'bg-secondary-subtle text-secondary',
                                    'sin_uso'  => 'bg-light text-muted',
                                ][$lote->tipo] ?? 'bg-light text-muted';
                            @endphp
                            <span class="badge rounded-pill {{ $badgeTipo }}">
                                {{ $tiposLote[$lote->tipo] ?? $lote->tipo }}
                            </span>
                        </td>
                        <td>
                            @if ($lote->superficie_ha !== null)
                                <span class="fw-semibold">{{ number_format($lote->superficie_ha, 1) }} ha</span>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>
                            @if ($lote->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('campos.lotes.editar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $lote->id }}')"
                                    wire:loading.attr="disabled"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $lote->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $lote->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="{{ $lote->activo ? 'Â¿Desactivar este lote?' : 'Â¿Activar este lote?' }}"
                                    title="{{ $lote->activo ? 'Desactivar' : 'Activar' }}">
                                <i class="bi bi-{{ $lote->activo ? 'toggle-on' : 'toggle-off' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-map display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron lotes.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($lotes->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $lotes->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear/editar --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-map me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar lote' : 'Nuevo lote' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>

                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-lote">

                        {{-- IdentificaciÃ³n --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            IdentificaciÃ³n
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre" placeholder="Ej: Lote 1 Norte">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CÃ³digo</label>
                                <input type="text" class="form-control font-monospace @error('codigo') is-invalid @enderror"
                                       wire:model="codigo" placeholder="Ej: L01-N">
                                @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Establecimiento <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model="id_establecimiento">
                                    <option value="">Seleccionar establecimientoâ€¦</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror"
                                        wire:model="tipo">
                                    @foreach ($tiposLote as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Superficie y ubicaciÃ³n --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Superficie y ubicaciÃ³n
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Superficie</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('superficie_ha') is-invalid @enderror"
                                           wire:model="superficie_ha" placeholder="0.00">
                                    <span class="input-group-text">ha</span>
                                    @error('superficie_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Latitud</label>
                                <input type="number" step="any"
                                       class="form-control @error('latitud') is-invalid @enderror"
                                       wire:model="latitud" placeholder="-38.123456">
                                @error('latitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Longitud</label>
                                <input type="number" step="any"
                                       class="form-control @error('longitud') is-invalid @enderror"
                                       wire:model="longitud" placeholder="-62.654321">
                                @error('longitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- DescripciÃ³n y estado --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Notas
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">DescripciÃ³n</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                          wire:model="descripcion" rows="3"
                                          placeholder="Observaciones sobre el lote, historial, caracterÃ­sticasâ€¦"></textarea>
                                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if ($modoEdicion)
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-activo-lote">
                                    <label class="form-check-label fw-semibold" for="switch-activo-lote">
                                        Lote activo
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>

                    </form>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">
                        Cancelar
                    </button>
                    <button type="submit" form="form-lote" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="bi bi-check-lg me-1"></i>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear lote' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
