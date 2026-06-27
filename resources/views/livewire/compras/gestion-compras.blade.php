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
            <h5 class="mb-0 fw-bold text-dark">Compras</h5>
            <small class="text-muted">Registro de facturas, remitos y órdenes de compra</small>
        </div>
        @can('compras.crear')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nueva compra
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Búsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Proveedor, N° comprobante…"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Proveedor</label>
                    <select class="form-select" wire:model.live="filtroProveedor">
                        <option value="">Todos</option>
                        @foreach ($proveedoresOpciones as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
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
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Desde</label>
                    <input type="date" class="form-control" wire:model.live="filtroFechaDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                    <input type="date" class="form-control" wire:model.live="filtroFechaHasta">
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $compras->total() }}</span>
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
                        <th class="ps-4">Fecha</th>
                        <th>Comprobante</th>
                        <th>Proveedor</th>
                        <th>Establecimiento</th>
                        <th class="text-center">Ítems</th>
                        <th>Estado</th>
                        <th class="text-end">Total</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($compras as $compra)
                    @php
                        $badgeEstado = [
                            'pendiente' => 'bg-warning-subtle text-warning-emphasis',
                            'recibida'  => 'bg-info-subtle text-info-emphasis',
                            'pagada'    => 'bg-success-subtle text-success',
                            'cancelada' => 'bg-secondary-subtle text-secondary',
                        ][$compra->estado] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $compra->fecha->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $compra->tipo_comprobante_label }}</div>
                            @if ($compra->numero_comprobante)
                                <span class="font-monospace text-muted" style="font-size:.8rem;">{{ $compra->numero_comprobante }}</span>
                            @endif
                        </td>
                        <td>{{ $compra->proveedor->nombre ?? '—' }}</td>
                        <td><small class="text-muted">{{ $compra->establecimiento->nombre ?? '—' }}</small></td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $compra->items_count }}</span>
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">{{ $compra->estado_label }}</span>
                            @if ($compra->stock_registrado)
                                <i class="bi bi-boxes text-success ms-1" title="Stock registrado"></i>
                            @endif
                        </td>
                        <td class="text-end fw-bold">
                            ${{ number_format((float)$compra->total, 2, ',', '.') }}
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('compras.editar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $compra->id }}')"
                                    wire:loading.attr="disabled" title="Ver / Editar">
                                <i class="bi bi-pencil"></i>
                            </button>

                            {{-- Cambio rápido de estado --}}
                            <div class="btn-group me-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar estado">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @foreach ($estados as $val => $etq)
                                    @if ($val !== $compra->estado)
                                    <li>
                                        <button class="dropdown-item small"
                                                wire:click="cambiarEstado('{{ $compra->id }}', '{{ $val }}')">
                                            {{ $etq }}
                                        </button>
                                    </li>
                                    @endif
                                    @endforeach
                                </ul>
                            </div>

                            @if (!$compra->stock_registrado && $compra->estado !== 'cancelada')
                            <button class="btn btn-sm btn-outline-success"
                                    wire:click="registrarEnStock('{{ $compra->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="¿Registrar ítems de esta compra en el inventario de insumos?"
                                    title="Registrar en stock">
                                <i class="bi bi-boxes"></i>
                            </button>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-cart display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron compras.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($compras->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $compras->links() }}
        </div>
        @endif
    </div>

    {{-- Modal compra --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-cart me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar compra' : 'Registrar compra' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-compra">

                        {{-- Cabecera --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Datos del comprobante</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_comprobante') is-invalid @enderror"
                                        wire:model="tipo_comprobante">
                                    @foreach ($tiposComprobante as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_comprobante') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">N° comprobante</label>
                                <input type="text" class="form-control font-monospace @error('numero_comprobante') is-invalid @enderror"
                                       wire:model="numero_comprobante" placeholder="0001-00000001">
                                @error('numero_comprobante') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha vencimiento</label>
                                <input type="date" class="form-control @error('fecha_vencimiento') is-invalid @enderror"
                                       wire:model="fecha_vencimiento">
                                @error('fecha_vencimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Proveedor</label>
                                <select class="form-select @error('id_proveedor') is-invalid @enderror"
                                        wire:model="id_proveedor">
                                    <option value="">Sin proveedor registrado</option>
                                    @foreach ($proveedoresOpciones as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_proveedor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Establecimiento</label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model="id_establecimiento">
                                    <option value="">General / sin asignar</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select @error('estado') is-invalid @enderror"
                                        wire:model="estado">
                                    @foreach ($estados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Ítems --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted text-uppercase fw-bold small mb-0">Ítems</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    wire:click="agregarItem">
                                <i class="bi bi-plus me-1"></i> Agregar ítem
                            </button>
                        </div>
                        @error('items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:180px;">Insumo (catálogo)</th>
                                        <th style="min-width:200px;">Descripción <span class="text-danger">*</span></th>
                                        <th style="min-width:90px;">Cantidad <span class="text-danger">*</span></th>
                                        <th style="min-width:70px;">Unidad</th>
                                        <th style="min-width:120px;">Precio unit. <span class="text-danger">*</span></th>
                                        <th style="min-width:110px;" class="text-end">Subtotal</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $i => $item)
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm"
                                                    wire:model.live="items.{{ $i }}.id_insumo">
                                                <option value="">Libre / sin vincular</option>
                                                @foreach ($insumosOpciones as $ins)
                                                    <option value="{{ $ins->id }}">{{ $ins->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-sm @error("items.{$i}.descripcion") is-invalid @enderror"
                                                   wire:model="items.{{ $i }}.descripcion"
                                                   placeholder="Descripción del ítem">
                                            @error("items.{$i}.descripcion") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0"
                                                   class="form-control form-control-sm @error("items.{$i}.cantidad") is-invalid @enderror"
                                                   wire:model.live="items.{{ $i }}.cantidad"
                                                   placeholder="0">
                                            @error("items.{$i}.cantidad") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-sm"
                                                   wire:model="items.{{ $i }}.unidad"
                                                   placeholder="kg, lt…">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0"
                                                       class="form-control @error("items.{$i}.precio_unitario") is-invalid @enderror"
                                                       wire:model.live="items.{{ $i }}.precio_unitario"
                                                       placeholder="0.00">
                                                @error("items.{$i}.precio_unitario") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            ${{ number_format((float)($item['subtotal'] ?? 0), 2, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            @if (count($items) > 1)
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0"
                                                    wire:click="quitarItem({{ $i }})"
                                                    title="Quitar ítem">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            Agregue al menos un ítem.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Totales --}}
                        <div class="row justify-content-end mb-3">
                            <div class="col-md-5">
                                <div class="card border-0 bg-light">
                                    <div class="card-body py-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Subtotal</span>
                                            <span class="fw-semibold">${{ number_format((float)$subtotal, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted">IVA</span>
                                                <div class="input-group input-group-sm" style="width:90px;">
                                                    <input type="number" step="0.5" min="0" max="100"
                                                           class="form-control form-control-sm"
                                                           wire:model.live="iva_porc"
                                                           placeholder="0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <span>${{ number_format((float)$iva_importe, 2, ',', '.') }}</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between fw-bold fs-5">
                                            <span>Total</span>
                                            <span class="text-primary">${{ number_format((float)$total, 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Observaciones --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Observaciones</label>
                            <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                      wire:model="observaciones" rows="2"></textarea>
                            @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-compra" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar compra' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
