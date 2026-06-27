<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Sanidad</h5>
            <small class="text-muted">Vacunaciones, tratamientos y eventos sanitarios</small>
        </div>
        @can('ganaderia.sanidad.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar evento
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
                               placeholder="Producto, veterinarioâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                    <select class="form-select" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo de evento</label>
                    <select class="form-select" wire:model.live="filtroTipoEvento">
                        <option value="">Todos</option>
                        @foreach ($tiposEvento as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $eventos->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Evento</th>
                        <th>Establecimiento</th>
                        <th>Animal / Grupo</th>
                        <th>Producto</th>
                        <th>Fecha</th>
                        <th>Veterinario</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                    @php
                        $badgeTipo = [
                            'vacunacion'      => 'bg-success-subtle text-success',
                            'desparasitacion' => 'bg-info-subtle text-info-emphasis',
                            'tratamiento'     => 'bg-warning-subtle text-warning-emphasis',
                            'diagnostico'     => 'bg-primary-subtle text-primary',
                            'castracion'      => 'bg-secondary-subtle text-secondary',
                            'otro'            => 'bg-light text-muted',
                        ][$evento->tipo_evento] ?? 'bg-light text-muted';
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <span class="badge rounded-pill {{ $badgeTipo }}">{{ $evento->tipo_label }}</span>
                        </td>
                        <td>{{ $evento->establecimiento->nombre ?? 'â€”' }}</td>
                        <td>
                            @if ($evento->animal)
                                <span class="font-monospace">{{ $evento->animal->caravana ?? 'â€”' }}</span>
                                <small class="text-muted d-block">{{ $evento->animal->categoria_label }}</small>
                            @elseif ($evento->categoria_afectada)
                                <span class="text-muted">{{ \App\Models\Animal::CATEGORIAS[$evento->categoria_afectada] ?? $evento->categoria_afectada }}</span>
                                @if ($evento->cantidad_afectada)
                                    <small class="text-muted d-block">{{ $evento->cantidad_afectada }} cabezas</small>
                                @endif
                            @else
                                <span class="text-muted">Todo el rodeo</span>
                            @endif
                        </td>
                        <td>
                            @if ($evento->producto)
                                <div>{{ $evento->producto }}</div>
                                @if ($evento->dosis) <small class="text-muted">{{ $evento->dosis }}</small> @endif
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>{{ $evento->fecha->format('d/m/Y') }}</td>
                        <td>{{ $evento->veterinario ?? 'â€”' }}</td>
                        <td class="pe-4 text-end">
                            @can('ganaderia.sanidad.registrar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $evento->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-shield-plus display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron eventos sanitarios.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($eventos->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $eventos->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-plus me-2 text-success"></i>
                        {{ $modoEdicion ? 'Editar evento sanitario' : 'Registrar evento sanitario' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-sanidad">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Contexto</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Establecimiento <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model.live="id_establecimiento">
                                    <option value="">Seleccionarâ€¦</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Animal individual <small class="text-muted fw-normal">(opcional)</small></label>
                                <select class="form-select @error('id_animal') is-invalid @enderror"
                                        wire:model="id_animal">
                                    <option value="">Evento grupal / todo el rodeo</option>
                                    @foreach ($animalesOpciones as $a)
                                        <option value="{{ $a->id }}">
                                            {{ $a->caravana ?? '(sin caravana)' }}
                                            â€” {{ \App\Models\Animal::CATEGORIAS[$a->categoria] ?? $a->categoria }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_animal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">CategorÃ­a afectada</label>
                                <select class="form-select @error('categoria_afectada') is-invalid @enderror"
                                        wire:model="categoria_afectada">
                                    <option value="">Sin especificar</option>
                                    @foreach ($categorias as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria_afectada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Detalle del evento</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo de evento <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_evento') is-invalid @enderror" wire:model="tipo_evento">
                                    @foreach ($tiposEvento as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_evento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror" wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Cabezas</label>
                                <input type="number" min="1"
                                       class="form-control @error('cantidad_afectada') is-invalid @enderror"
                                       wire:model="cantidad_afectada" placeholder="â€”">
                                @error('cantidad_afectada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Veterinario</label>
                                <input type="text" class="form-control @error('veterinario') is-invalid @enderror"
                                       wire:model="veterinario" placeholder="Nombre">
                                @error('veterinario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Producto / Vacuna / Medicamento</label>
                                <input type="text" class="form-control @error('producto') is-invalid @enderror"
                                       wire:model="producto" placeholder="Ej: Vacuna Clostridiosis, Ivermectina">
                                @error('producto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Dosis</label>
                                <input type="text" class="form-control @error('dosis') is-invalid @enderror"
                                       wire:model="dosis" placeholder="Ej: 2 ml/animal">
                                @error('dosis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-sanidad" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar evento' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
