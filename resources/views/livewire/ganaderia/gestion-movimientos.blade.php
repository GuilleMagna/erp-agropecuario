<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Movimientos de hacienda</h5>
            <small class="text-muted">Entradas, salidas y transferencias de stock</small>
        </div>
        @can('ganaderia.movimientos.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar movimiento
        </button>
        @endcan
    </div>

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
                               placeholder="Procedencia / destinoâ€¦"
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
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        @foreach ($tiposMovimiento as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
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
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $movimientos->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tipo</th>
                        <th>Establecimiento</th>
                        <th>Fecha</th>
                        <th>CategorÃ­a</th>
                        <th class="text-end">Cabezas</th>
                        <th class="text-end">Peso total</th>
                        <th class="text-end">Importe</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $mov)
                    @php
                        $esPositivo = in_array($mov->tipo, \App\Models\Movimiento::TIPOS_POSITIVOS);
                        $badgeTipo  = $esPositivo
                            ? 'bg-success-subtle text-success'
                            : 'bg-danger-subtle text-danger';
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <span class="badge rounded-pill {{ $badgeTipo }}">
                                {{ $mov->tipo_label }}
                            </span>
                            @if ($mov->procedencia_destino)
                                <br><small class="text-muted">{{ $mov->procedencia_destino }}</small>
                            @endif
                        </td>
                        <td>{{ $mov->establecimiento->nombre ?? 'â€”' }}</td>
                        <td>{{ $mov->fecha->format('d/m/Y') }}</td>
                        <td>{{ \App\Models\Animal::CATEGORIAS[$mov->categoria] ?? $mov->categoria }}</td>
                        <td class="text-end fw-semibold">
                            {{ $esPositivo ? '+' : '-' }}{{ $mov->cantidad }}
                        </td>
                        <td class="text-end">
                            @if ($mov->peso_total_kg !== null)
                                {{ number_format($mov->peso_total_kg, 0) }} kg
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($mov->importe_total !== null)
                                ${{ number_format($mov->importe_total, 0) }}
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('ganaderia.movimientos.registrar')
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
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-arrow-left-right display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron movimientos.
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

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-left-right me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar movimiento' : 'Registrar movimiento' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-movimiento">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Datos del movimiento</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Establecimiento <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model="id_establecimiento">
                                    <option value="">Seleccionarâ€¦</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror" wire:model="tipo">
                                    @foreach ($tiposMovimiento as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror" wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CategorÃ­a <span class="text-danger">*</span></label>
                                <select class="form-select @error('categoria') is-invalid @enderror" wire:model="categoria">
                                    @foreach ($categorias as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Cabezas <span class="text-danger">*</span></label>
                                <input type="number" min="1"
                                       class="form-control @error('cantidad') is-invalid @enderror"
                                       wire:model="cantidad" placeholder="0">
                                @error('cantidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Procedencia / Destino</label>
                                <input type="text" class="form-control @error('procedencia_destino') is-invalid @enderror"
                                       wire:model="procedencia_destino"
                                       placeholder="Nombre del vendedor, comprador o establecimiento">
                                @error('procedencia_destino') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Valores (opcional)</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Peso total</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('peso_total_kg') is-invalid @enderror"
                                           wire:model="peso_total_kg" placeholder="0.00">
                                    <span class="input-group-text">kg</span>
                                    @error('peso_total_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Precio por cabeza</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('precio_cabeza') is-invalid @enderror"
                                           wire:model="precio_cabeza" placeholder="0.00">
                                    @error('precio_cabeza') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <button type="submit" form="form-movimiento" class="btn btn-primary"
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
