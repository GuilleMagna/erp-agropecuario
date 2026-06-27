<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Ventas de Hacienda</h5>
            <small class="text-muted">Registro de ventas, remates y consignaciones de hacienda</small>
        </div>
        @can('ventas.hacienda.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nueva venta
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
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Comprador, corredor, NÂ° guÃ­aâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
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
                        <th>CategorÃ­a</th>
                        <th>Comprador / Corredor</th>
                        <th>Establecimiento</th>
                        <th>OperaciÃ³n</th>
                        <th class="text-center">Cabezas</th>
                        <th class="text-end">Peso total (kg)</th>
                        <th class="text-end">Importe</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                    @php
                        $badgeEstado = [
                            'confirmada' => 'bg-info-subtle text-info-emphasis',
                            'cobrada'    => 'bg-success-subtle text-success',
                            'cancelada'  => 'bg-danger-subtle text-danger',
                        ][$venta->estado] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $venta->fecha->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $venta->categoria_label }}</span>
                        </td>
                        <td>
                            <div>{{ $venta->comprador ?? 'â€”' }}</div>
                            @if ($venta->corredor_feria)
                                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $venta->corredor_feria }}</small>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $venta->establecimiento?->nombre ?? 'â€”' }}</small></td>
                        <td><small>{{ $venta->tipo_operacion_label }}</small></td>
                        <td class="text-center fw-semibold">{{ number_format($venta->cantidad_cabezas, 0, ',', '.') }}</td>
                        <td class="text-end font-monospace">
                            {{ $venta->peso_total_kg !== null ? number_format((float)$venta->peso_total_kg, 0, ',', '.') : 'â€”' }}
                        </td>
                        <td class="text-end fw-bold">
                            {{ $venta->moneda === 'USD' ? 'U$S' : '$' }} {{ number_format((float)$venta->importe_total, 2, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">{{ $venta->estado_label }}</span>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('ventas.hacienda.registrar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $venta->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        data-bs-toggle="dropdown" title="Cambiar estado">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @foreach ($estados as $val => $etq)
                                    @if ($val !== $venta->estado)
                                    <li>
                                        <button class="dropdown-item small"
                                                wire:click="cambiarEstado('{{ $venta->id }}', '{{ $val }}')">
                                            {{ $etq }}
                                        </button>
                                    </li>
                                    @endif
                                    @endforeach
                                </ul>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-cursor display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron ventas de hacienda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if ($ventas->count())
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="ps-4 text-muted small">Total de la pÃ¡gina</td>
                        <td class="text-center fw-semibold">
                            {{ number_format($ventas->sum(fn($v) => $v->cantidad_cabezas), 0, ',', '.') }} cab.
                        </td>
                        <td class="text-end font-monospace fw-semibold">
                            {{ number_format($ventas->sum(fn($v) => (float)$v->peso_total_kg), 0, ',', '.') }} kg
                        </td>
                        <td class="text-end fw-bold">
                            ${{ number_format($ventas->sum(fn($v) => (float)$v->importe_total), 2, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if ($ventas->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $ventas->links() }}
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
                        <i class="bi bi-cursor me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar venta de hacienda' : 'Registrar venta de hacienda' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-venta-hacienda">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">OperaciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo de operaciÃ³n <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_operacion') is-invalid @enderror"
                                        wire:model="tipo_operacion">
                                    @foreach ($tiposOperacion as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_operacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select @error('estado') is-invalid @enderror" wire:model="estado">
                                    @foreach ($estados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Moneda</label>
                                <select class="form-select @error('moneda') is-invalid @enderror" wire:model="moneda">
                                    @foreach ($monedas as $val => $etq)
                                        <option value="{{ $val }}">{{ $val }}</option>
                                    @endforeach
                                </select>
                                @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Establecimiento de origen</label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model="id_establecimiento">
                                    <option value="">Sin asignar</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">NÂ° guÃ­a / tropa</label>
                                <input type="text" class="form-control font-monospace @error('numero_guia') is-invalid @enderror"
                                       wire:model="numero_guia" placeholder="NÂ° guÃ­a de hacienda">
                                @error('numero_guia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Animales</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CategorÃ­a <span class="text-danger">*</span></label>
                                <select class="form-select @error('categoria') is-invalid @enderror" wire:model="categoria">
                                    <option value="">Seleccionarâ€¦</option>
                                    @foreach ($categorias as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Cabezas <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1"
                                       class="form-control @error('cantidad_cabezas') is-invalid @enderror"
                                       wire:model.live="cantidad_cabezas" placeholder="0">
                                @error('cantidad_cabezas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Peso promedio (kg)</label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control font-monospace @error('peso_promedio_kg') is-invalid @enderror"
                                       wire:model.live="peso_promedio_kg" placeholder="0.00">
                                @error('peso_promedio_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Peso total (kg)</label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control font-monospace @error('peso_total_kg') is-invalid @enderror"
                                       wire:model.live="peso_total_kg" placeholder="Calculado automÃ¡tico">
                                @error('peso_total_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Comprador y valores</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Comprador</label>
                                <input type="text" class="form-control @error('comprador') is-invalid @enderror"
                                       wire:model="comprador" placeholder="Nombre o razÃ³n social">
                                @error('comprador') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Corredor / Feria</label>
                                <input type="text" class="form-control @error('corredor_feria') is-invalid @enderror"
                                       wire:model="corredor_feria" placeholder="Nombre del corredor o feria">
                                @error('corredor_feria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Precio / kg</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.0001" min="0"
                                           class="form-control font-monospace @error('precio_kg') is-invalid @enderror"
                                           wire:model.live="precio_kg" placeholder="0.0000">
                                    @error('precio_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Precio / cabeza</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control font-monospace @error('precio_cabeza') is-invalid @enderror"
                                           wire:model.live="precio_cabeza" placeholder="0.00">
                                    @error('precio_cabeza') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">Alternativo al precio/kg</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Importe total <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold text-success">{{ $moneda === 'USD' ? 'U$S' : '$' }}</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control font-monospace fw-bold @error('importe_total') is-invalid @enderror"
                                           wire:model="importe_total" placeholder="0.00">
                                    @error('importe_total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">Calculado automÃ¡ticamente. Ajustable manualmente.</div>
                            </div>
                        </div>

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
                    <button type="submit" form="form-venta-hacienda" class="btn btn-success"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar venta' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
