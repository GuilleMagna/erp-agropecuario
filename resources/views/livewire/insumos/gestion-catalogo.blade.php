<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">CatÃ¡logo de insumos</h5>
            <small class="text-muted">Semillas, agroquÃ­micos, fertilizantes y mÃ¡s</small>
        </div>
        @can('insumos.catalogo.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo insumo
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Nombre, cÃ³digo, marcaâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
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
                    <span class="text-muted small">{{ $insumos->total() }} insumos</span>
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
                        <th>Tipo</th>
                        <th>Unidad</th>
                        <th>Marca</th>
                        <th class="text-end">Stock actual</th>
                        <th class="text-end">Precio ref.</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($insumos as $insumo)
                    @php
                        $stockActual = (float)($insumo->total_entradas ?? 0) - (float)($insumo->total_salidas ?? 0);
                        $stockBajo   = $insumo->stock_minimo !== null && $stockActual < (float) $insumo->stock_minimo;

                        $badgeTipo = [
                            'semilla'      => 'bg-success-subtle text-success',
                            'agroquimico'  => 'bg-warning-subtle text-warning-emphasis',
                            'fertilizante' => 'bg-primary-subtle text-primary',
                            'combustible'  => 'bg-danger-subtle text-danger',
                            'veterinario'  => 'bg-info-subtle text-info-emphasis',
                            'herramienta'  => 'bg-secondary-subtle text-secondary',
                            'repuesto'     => 'bg-secondary-subtle text-secondary',
                            'otro'         => 'bg-light text-muted',
                        ][$insumo->tipo] ?? 'bg-light text-muted';
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $insumo->nombre }}</div>
                            @if ($insumo->codigo)
                                <small class="text-muted font-monospace">{{ $insumo->codigo }}</small>
                            @endif
                        </td>
                        <td><span class="badge rounded-pill {{ $badgeTipo }}">{{ $insumo->tipo_label }}</span></td>
                        <td><small class="text-muted">{{ $insumo->unidad }}</small></td>
                        <td><small>{{ $insumo->marca ?? 'â€”' }}</small></td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $stockBajo ? 'text-danger' : '' }}">
                                {{ number_format($stockActual, 2, ',', '.') }}
                            </span>
                            <small class="text-muted ms-1">{{ $insumo->unidad }}</small>
                            @if ($stockBajo)
                                <i class="bi bi-exclamation-triangle-fill text-danger ms-1"
                                   title="Stock bajo mÃ­nimo ({{ number_format((float)$insumo->stock_minimo, 2, ',', '.') }} {{ $insumo->unidad }})"></i>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($insumo->precio_referencia !== null)
                                <small>${{ number_format((float)$insumo->precio_referencia, 2, ',', '.') }}</small>
                            @else
                                <small class="text-muted">â€”</small>
                            @endif
                        </td>
                        <td>
                            @if ($insumo->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('insumos.catalogo.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $insumo->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $insumo->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $insumo->id }}')"
                                    wire:confirm="{{ $insumo->activo ? 'Â¿Dar de baja este insumo?' : 'Â¿Reactivar este insumo?' }}"
                                    wire:loading.attr="disabled"
                                    title="{{ $insumo->activo ? 'Dar de baja' : 'Reactivar' }}">
                                <i class="bi bi-{{ $insumo->activo ? 'x-circle' : 'arrow-counterclockwise' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-box-seam display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron insumos en el catÃ¡logo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($insumos->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $insumos->links() }}
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
                        <i class="bi bi-box-seam me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar insumo' : 'Nuevo insumo' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-insumo">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">IdentificaciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre"
                                       placeholder="Nombre del insumo">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CÃ³digo</label>
                                <input type="text"
                                       class="form-control font-monospace @error('codigo') is-invalid @enderror"
                                       wire:model="codigo" placeholder="SKU / cÃ³digo interno">
                                @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror" wire:model="tipo">
                                    @foreach ($tipos as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unidad de medida <span class="text-danger">*</span></label>
                                <select class="form-select @error('unidad') is-invalid @enderror" wire:model="unidad">
                                    @foreach ($unidades as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('unidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Marca</label>
                                <input type="text"
                                       class="form-control @error('marca') is-invalid @enderror"
                                       wire:model="marca" placeholder="Ej: Bayer, YPF">
                                @error('marca') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">DescripciÃ³n</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                          wire:model="descripcion" rows="2"
                                          placeholder="InformaciÃ³n adicional del productoâ€¦"></textarea>
                                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Stock y precio</h6>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Stock mÃ­nimo</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('stock_minimo') is-invalid @enderror"
                                           wire:model="stock_minimo" placeholder="â€”">
                                    <span class="input-group-text">{{ $unidad }}</span>
                                    @error('stock_minimo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">Alerta cuando el stock cae por debajo de este valor.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Precio de referencia</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('precio_referencia') is-invalid @enderror"
                                           wire:model="precio_referencia" placeholder="0.00">
                                    @error('precio_referencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            @if ($modoEdicion)
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-insumo-activo">
                                    <label class="form-check-label fw-semibold" for="switch-insumo-activo">
                                        Activo
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-insumo" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear insumo' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
