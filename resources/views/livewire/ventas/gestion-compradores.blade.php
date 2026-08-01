<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Compradores / Corredores</h5>
            <small class="text-muted">Catálogo compartido para ventas de granos y hacienda</small>
        </div>
        @can('ventas.compradores.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo comprador
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted mb-1">Búsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Nombre…" wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Categoría</label>
                    <select class="form-select" wire:model.live="filtroCategoria">
                        <option value="">Todas</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
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
                <div class="col-md-2 text-end">
                    <span class="text-muted small">
                        {{ $compradores->total() }} {{ $compradores->total() === 1 ? 'comprador' : 'compradores' }}
                    </span>
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
                        <th class="ps-4">Nombre</th>
                        <th>CUIT</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($compradores as $comprador)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $comprador->nombre }}</td>
                        <td><small class="text-muted font-monospace">{{ $comprador->cuit ?? '—' }}</small></td>
                        <td>
                            @if ($comprador->categoriaVenta)
                                <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $comprador->categoriaVenta->nombre }}</span>
                            @else
                                <span class="text-muted small">Sin categoría</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $comprador->activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $comprador->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('ventas.compradores.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $comprador->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="toggleActivo('{{ $comprador->id }}')"
                                    wire:loading.attr="disabled"
                                    title="{{ $comprador->activo ? 'Desactivar' : 'Activar' }}">
                                <i class="bi {{ $comprador->activo ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-people display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron compradores.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($compradores->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $compradores->links() }}
        </div>
        @endif
    </div>

    {{-- Modal --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block"
         wire:key="modal-comprador"
         role="dialog"
         aria-modal="true"
         aria-labelledby="titulo-modal-comprador"
         tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="titulo-modal-comprador">
                        <i class="bi bi-person-badge me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar comprador' : 'Nuevo comprador' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-comprador">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   wire:model="nombre" placeholder="Nombre o razón social">
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CUIT</label>
                            <input type="text" class="form-control font-monospace @error('cuit') is-invalid @enderror"
                                   wire:model="cuit" placeholder="XX-XXXXXXXX-X">
                            @error('cuit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Categoría</label>
                            @if (!$formNuevaCategoriaAbierto)
                            <div class="input-group">
                                <select class="form-select @error('id_categoria_venta') is-invalid @enderror"
                                        wire:model="id_categoria_venta">
                                    <option value="">Sin categoría</option>
                                    @foreach ($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary" wire:click="abrirFormNuevaCategoria" title="Nueva categoría">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            @error('id_categoria_venta') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @else
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text" class="form-control form-control-sm @error('nuevaCategoriaNombre') is-invalid @enderror"
                                               wire:model="nuevaCategoriaNombre" placeholder="Nombre de la categoría">
                                        @error('nuevaCategoriaNombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6">
                                        <select class="form-select form-select-sm @error('nuevaCategoriaTipo') is-invalid @enderror"
                                                wire:model="nuevaCategoriaTipo">
                                            @foreach ($tiposCantidad as $val => $etq)
                                                <option value="{{ $val }}">{{ $etq }}</option>
                                            @endforeach
                                        </select>
                                        @error('nuevaCategoriaTipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="mt-2 text-end">
                                    <button type="button" class="btn btn-sm btn-light" wire:click="$set('formNuevaCategoriaAbierto', false)">Cancelar</button>
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="guardarNuevaCategoria">Guardar categoría</button>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" wire:model="activo" id="comprador-activo">
                            <label class="form-check-label" for="comprador-activo">Activo</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-comprador" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear comprador' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
