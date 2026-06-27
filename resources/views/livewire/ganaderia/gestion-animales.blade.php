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
            <h5 class="mb-0 fw-bold text-dark">Animales</h5>
            <small class="text-muted">PadrÃ³n individual de hacienda</small>
        </div>
        @can('ganaderia.animales.crear')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo animal
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
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
                               placeholder="Caravana, colorâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
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
                    <label class="form-label small fw-semibold text-muted mb-1">Raza</label>
                    <select class="form-select" wire:model.live="filtroRaza">
                        <option value="">Todas</option>
                        @foreach ($razas as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold text-muted mb-1">Sexo</label>
                    <select class="form-select" wire:model.live="filtroSexo">
                        <option value="">Todos</option>
                        @foreach ($sexos as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Bajas</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $animales->total() }}</span>
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
                        <th class="ps-4">Caravana</th>
                        <th>CategorÃ­a</th>
                        <th>Raza</th>
                        <th>Establecimiento</th>
                        <th>Ingreso</th>
                        <th>Peso actual</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($animales as $animal)
                    <tr>
                        <td class="ps-4">
                            @if ($animal->caravana)
                                <span class="fw-semibold font-monospace">{{ $animal->caravana }}</span>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                            <br><small class="text-muted">{{ $animal->sexo === 'macho' ? 'â™‚' : 'â™€' }}</small>
                        </td>
                        <td>{{ $animal->categoria_label }}</td>
                        <td>{{ $animal->raza_label ?: 'â€”' }}</td>
                        <td>{{ $animal->establecimiento->nombre ?? 'â€”' }}</td>
                        <td>{{ $animal->fecha_ingreso->format('d/m/Y') }}</td>
                        <td>
                            @if ($animal->peso_actual_kg !== null)
                                <span class="fw-semibold">{{ number_format($animal->peso_actual_kg, 0) }}</span>
                                <small class="text-muted">kg</small>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>
                            @if ($animal->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">Baja</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('ganaderia.animales.editar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $animal->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $animal->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $animal->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="{{ $animal->activo ? 'Â¿Dar de baja este animal?' : 'Â¿Reactivar este animal?' }}"
                                    title="{{ $animal->activo ? 'Dar de baja' : 'Reactivar' }}">
                                <i class="bi bi-{{ $animal->activo ? 'x-circle' : 'arrow-counterclockwise' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-heart-pulse display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron animales.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($animales->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $animales->links() }}
        </div>
        @endif
    </div>

    {{-- Modal --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-heart-pulse me-2 text-danger"></i>
                        {{ $modoEdicion ? 'Editar animal' : 'Nuevo animal' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-animal">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">IdentificaciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
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
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Caravana / Carimbo</label>
                                <input type="text" class="form-control font-monospace @error('caravana') is-invalid @enderror"
                                       wire:model="caravana" placeholder="Ej: 12345">
                                @error('caravana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Color</label>
                                <input type="text" class="form-control @error('color') is-invalid @enderror"
                                       wire:model="color" placeholder="Ej: Negro, Overo">
                                @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">ClasificaciÃ³n zootÃ©cnica</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                                <select class="form-select @error('sexo') is-invalid @enderror" wire:model="sexo">
                                    @foreach ($sexos as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('sexo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">CategorÃ­a <span class="text-danger">*</span></label>
                                <select class="form-select @error('categoria') is-invalid @enderror" wire:model="categoria">
                                    @foreach ($categorias as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Raza</label>
                                <select class="form-select @error('raza') is-invalid @enderror" wire:model="raza">
                                    <option value="">Sin especificar</option>
                                    @foreach ($razas as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('raza') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Fechas y peso</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha de nacimiento</label>
                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                       wire:model="fecha_nacimiento">
                                @error('fecha_nacimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha de ingreso <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_ingreso') is-invalid @enderror"
                                       wire:model="fecha_ingreso">
                                @error('fecha_ingreso') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Peso de ingreso</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0"
                                           class="form-control @error('peso_ingreso_kg') is-invalid @enderror"
                                           wire:model="peso_ingreso_kg" placeholder="0.0">
                                    <span class="input-group-text">kg</span>
                                    @error('peso_ingreso_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"
                                          placeholder="Notas adicionales sobre el animalâ€¦"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if ($modoEdicion)
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-animal-activo">
                                    <label class="form-check-label fw-semibold" for="switch-animal-activo">
                                        Animal activo en el sistema
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-animal" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar animal' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
