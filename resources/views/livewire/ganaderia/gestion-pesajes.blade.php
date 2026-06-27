<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Pesajes</h5>
            <small class="text-muted">Control de peso individual y grupal</small>
        </div>
        @can('ganaderia.pesajes.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar pesaje
        </button>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Caravana</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="NÂ° de caravanaâ€¦"
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
                    <label class="form-label small fw-semibold text-muted mb-1">CategorÃ­a</label>
                    <select class="form-select" wire:model.live="filtroCategoria">
                        <option value="">Todas</option>
                        @foreach ($categorias as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Modo</label>
                    <select class="form-select" wire:model.live="modoRegistro">
                        <option value="">Todos</option>
                        <option value="individual">Individual</option>
                        <option value="grupal">Grupal</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <span class="text-muted small">{{ $pesajes->total() }} pesajes</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Animal / Grupo</th>
                        <th>Establecimiento</th>
                        <th>Fecha</th>
                        <th>Cabezas</th>
                        <th class="text-end">Peso</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pesajes as $pesaje)
                    <tr>
                        <td class="ps-4">
                            @if ($pesaje->animal)
                                <span class="fw-semibold font-monospace">{{ $pesaje->animal->caravana ?? 'â€”' }}</span>
                                <br><small class="text-muted">{{ $pesaje->animal->categoria_label }}</small>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">Grupal</span>
                                @if ($pesaje->categoria)
                                    <small class="text-muted ms-1">{{ \App\Models\Animal::CATEGORIAS[$pesaje->categoria] ?? $pesaje->categoria }}</small>
                                @endif
                            @endif
                        </td>
                        <td>{{ $pesaje->establecimiento->nombre ?? 'â€”' }}</td>
                        <td>{{ $pesaje->fecha->format('d/m/Y') }}</td>
                        <td>{{ $pesaje->cantidad }}</td>
                        <td class="text-end">
                            <span class="fw-semibold">{{ number_format($pesaje->peso_kg, 1) }}</span>
                            <small class="text-muted">kg{{ $pesaje->cantidad > 1 ? ' prom.' : '' }}</small>
                        </td>
                        <td class="pe-4 text-end">
                            @can('ganaderia.pesajes.registrar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $pesaje->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-speedometer display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron pesajes.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pesajes->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $pesajes->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-speedometer me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar pesaje' : 'Registrar pesaje' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-pesaje">

                        <div class="row g-3 mb-3">
                            <div class="col-12">
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
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       wire:model.live="esIndividual" id="switch-individual">
                                <label class="form-check-label fw-semibold" for="switch-individual">
                                    Pesaje individual (por caravana)
                                </label>
                            </div>
                        </div>

                        @if ($esIndividual)
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Animal</label>
                                <select class="form-select @error('id_animal') is-invalid @enderror"
                                        wire:model="id_animal">
                                    <option value="">Sin animal registrado</option>
                                    @foreach ($animalesOpciones as $a)
                                        <option value="{{ $a->id }}">
                                            {{ $a->caravana ?? '(sin caravana)' }}
                                            â€” {{ \App\Models\Animal::CATEGORIAS[$a->categoria] ?? $a->categoria }}
                                            {{ $a->raza ? '(' . $a->raza . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_animal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        @else
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CategorÃ­a</label>
                                <select class="form-select @error('categoria') is-invalid @enderror"
                                        wire:model="categoria">
                                    <option value="">Sin especificar</option>
                                    @foreach ($categorias as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cantidad de cabezas <span class="text-danger">*</span></label>
                                <input type="number" min="1"
                                       class="form-control @error('cantidad') is-invalid @enderror"
                                       wire:model="cantidad">
                                @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Peso {{ $esIndividual ? '' : 'promedio' }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0"
                                           class="form-control @error('peso_kg') is-invalid @enderror"
                                           wire:model="peso_kg" placeholder="0.0">
                                    <span class="input-group-text">kg</span>
                                    @error('peso_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
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
                    <button type="submit" form="form-pesaje" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar pesaje' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
