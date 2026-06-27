<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Movimientos de stock</h5>
            <small class="text-muted">Entradas, salidas y ajustes de inventario</small>
        </div>
        @can('insumos.movimientos.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar movimiento
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Insumo</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Nombre o cÃ³digoâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label small fw-semibold text-muted mb-1">Desde</label>
                    <input type="date" class="form-control" wire:model.live="filtroFechaDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                    <input type="date" class="form-control" wire:model.live="filtroFechaHasta">
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
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Establecimiento</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">P. Unit.</th>
                        <th class="text-end pe-4">Importe</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $mov)
                    @php
                        $esPositivo = in_array($mov->tipo, \App\Models\MovimientoInsumo::TIPOS_POSITIVOS);
                        $badgeTipo  = match($mov->tipo) {
                            'entrada'         => 'bg-success-subtle text-success',
                            'salida'          => 'bg-danger-subtle text-danger',
                            'ajuste_positivo' => 'bg-info-subtle text-info-emphasis',
                            'ajuste_negativo' => 'bg-warning-subtle text-warning-emphasis',
                            default           => 'bg-secondary-subtle text-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $mov->fecha->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $mov->insumo->nombre ?? 'â€”' }}</div>
                            @if ($mov->insumo?->codigo)
                                <small class="text-muted font-monospace">{{ $mov->insumo->codigo }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeTipo }}">{{ $mov->tipo_label }}</span>
                        </td>
                        <td><small class="text-muted">{{ $mov->motivo_label }}</small></td>
                        <td><small class="text-muted">{{ $mov->establecimiento->nombre ?? 'General' }}</small></td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $esPositivo ? 'text-success' : 'text-danger' }}">
                                {{ $esPositivo ? '+' : 'âˆ’' }}{{ number_format((float)$mov->cantidad, 2, ',', '.') }}
                            </span>
                            <small class="text-muted ms-1">{{ $mov->insumo->unidad ?? '' }}</small>
                        </td>
                        <td class="text-end">
                            @if ($mov->precio_unitario !== null)
                                <small>${{ number_format((float)$mov->precio_unitario, 2, ',', '.') }}</small>
                            @else
                                <small class="text-muted">â€”</small>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if ($mov->importe_total !== null)
                                <span class="fw-semibold">${{ number_format((float)$mov->importe_total, 2, ',', '.') }}</span>
                            @else
                                <small class="text-muted">â€”</small>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('insumos.movimientos.registrar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $mov->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-arrow-repeat display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron movimientos de stock.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movimientos->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $movimientos->links() }}
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
                        <i class="bi bi-arrow-repeat me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar movimiento' : 'Registrar movimiento' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-movimiento-insumo">

                        {{-- Tipo de movimiento --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block mb-2">Tipo de movimiento <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" wire:model.live="tipo"
                                       value="entrada" id="mov-entrada" autocomplete="off">
                                <label class="btn btn-outline-success" for="mov-entrada">
                                    <i class="bi bi-arrow-down-circle me-1"></i> Entrada
                                </label>

                                <input type="radio" class="btn-check" wire:model.live="tipo"
                                       value="salida" id="mov-salida" autocomplete="off">
                                <label class="btn btn-outline-danger" for="mov-salida">
                                    <i class="bi bi-arrow-up-circle me-1"></i> Salida
                                </label>

                                <input type="radio" class="btn-check" wire:model.live="tipo"
                                       value="ajuste_positivo" id="mov-ajpos" autocomplete="off">
                                <label class="btn btn-outline-info" for="mov-ajpos">
                                    <i class="bi bi-plus-circle me-1"></i> Ajuste +
                                </label>

                                <input type="radio" class="btn-check" wire:model.live="tipo"
                                       value="ajuste_negativo" id="mov-ajneg" autocomplete="off">
                                <label class="btn btn-outline-warning" for="mov-ajneg">
                                    <i class="bi bi-dash-circle me-1"></i> Ajuste âˆ’
                                </label>
                            </div>
                            @error('tipo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Detalle</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Insumo <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_insumo') is-invalid @enderror"
                                        wire:model="id_insumo">
                                    <option value="">Seleccionar insumoâ€¦</option>
                                    @foreach ($insumosOpciones as $ins)
                                        <option value="{{ $ins->id }}">
                                            {{ $ins->nombre }}
                                            @if ($ins->codigo) ({{ $ins->codigo }}) @endif
                                            â€” {{ $ins->unidad }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_insumo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                                <select class="form-select @error('motivo') is-invalid @enderror"
                                        wire:model="motivo">
                                    <option value="">Seleccionar motivoâ€¦</option>
                                    @foreach ($motivosFiltrados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
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
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01"
                                       class="form-control @error('cantidad') is-invalid @enderror"
                                       wire:model.live="cantidad" placeholder="0.00">
                                @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        @if ($tipo === 'entrada')
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Valores (opcional)</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Precio unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('precio_unitario') is-invalid @enderror"
                                           wire:model.live="precio_unitario" placeholder="0.00">
                                    @error('precio_unitario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Importe total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('importe_total') is-invalid @enderror"
                                           wire:model="importe_total" placeholder="0.00">
                                    @error('importe_total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Proveedor</label>
                                <input type="text" class="form-control @error('proveedor') is-invalid @enderror"
                                       wire:model="proveedor" placeholder="Nombre del proveedor">
                                @error('proveedor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">NÂ° remito / comprobante</label>
                                <input type="text" class="form-control font-monospace @error('numero_remito') is-invalid @enderror"
                                       wire:model="numero_remito" placeholder="R-0001-00001234">
                                @error('numero_remito') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <button type="submit" form="form-movimiento-insumo" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar movimiento' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
