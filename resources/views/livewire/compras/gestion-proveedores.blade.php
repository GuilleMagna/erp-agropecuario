<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Proveedores</h5>
            <small class="text-muted">Directorio de proveedores de la empresa</small>
        </div>
        @can('compras.proveedores.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo proveedor
        </button>
        @endcan
    </div>

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
                               placeholder="Nombre, CUIT, email…"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Rubro</label>
                    <select class="form-select" wire:model.live="filtroRubro">
                        <option value="">Todos</option>
                        @foreach ($rubros as $val => $etq)
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
                    <span class="text-muted small">{{ $proveedores->total() }} proveedores</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nombre / Razón social</th>
                        <th>CUIT</th>
                        <th>Rubro</th>
                        <th>Contacto</th>
                        <th class="text-center">Compras</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proveedores as $prov)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $prov->nombre }}</div>
                            @if ($prov->razon_social && $prov->razon_social !== $prov->nombre)
                                <small class="text-muted">{{ $prov->razon_social }}</small>
                            @endif
                        </td>
                        <td><span class="font-monospace small">{{ $prov->cuit ?? '—' }}</span></td>
                        <td>
                            @if ($prov->rubro)
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">{{ $prov->rubro_label }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($prov->telefono)
                                <div class="small"><i class="bi bi-telephone me-1 text-muted"></i>{{ $prov->telefono }}</div>
                            @endif
                            @if ($prov->email)
                                <div class="small"><i class="bi bi-envelope me-1 text-muted"></i>{{ $prov->email }}</div>
                            @endif
                            @if (!$prov->telefono && !$prov->email)
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $prov->compras_count }}</span>
                        </td>
                        <td>
                            @if ($prov->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('compras.proveedores.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $prov->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $prov->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $prov->id }}')"
                                    wire:confirm="{{ $prov->activo ? '¿Dar de baja este proveedor?' : '¿Reactivar este proveedor?' }}"
                                    wire:loading.attr="disabled"
                                    title="{{ $prov->activo ? 'Dar de baja' : 'Reactivar' }}">
                                <i class="bi bi-{{ $prov->activo ? 'x-circle' : 'arrow-counterclockwise' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-building display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron proveedores.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($proveedores->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $proveedores->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-building me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar proveedor' : 'Nuevo proveedor' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-proveedor">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Identificación</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Nombre comercial <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre" placeholder="Nombre del proveedor">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Rubro</label>
                                <select class="form-select @error('rubro') is-invalid @enderror" wire:model="rubro">
                                    <option value="">Sin especificar</option>
                                    @foreach ($rubros as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('rubro') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Razón social</label>
                                <input type="text" class="form-control @error('razon_social') is-invalid @enderror"
                                       wire:model="razon_social" placeholder="Razón social para facturación">
                                @error('razon_social') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CUIT</label>
                                <input type="text" class="form-control font-monospace @error('cuit') is-invalid @enderror"
                                       wire:model="cuit" placeholder="XX-XXXXXXXX-X">
                                @error('cuit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Contacto</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Teléfono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                       wire:model="telefono" placeholder="+54 ...">
                                @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       wire:model="email" placeholder="contacto@proveedor.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dirección</label>
                                <input type="text" class="form-control @error('direccion') is-invalid @enderror"
                                       wire:model="direccion" placeholder="Calle y número">
                                @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Ciudad</label>
                                <input type="text" class="form-control @error('ciudad') is-invalid @enderror"
                                       wire:model="ciudad">
                                @error('ciudad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Provincia</label>
                                <input type="text" class="form-control @error('provincia') is-invalid @enderror"
                                       wire:model="provincia" placeholder="Ej: Córdoba">
                                @error('provincia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if ($modoEdicion)
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-prov-activo">
                                    <label class="form-check-label fw-semibold" for="switch-prov-activo">Proveedor activo</label>
                                </div>
                            </div>
                            @endif
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-proveedor" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear proveedor' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
