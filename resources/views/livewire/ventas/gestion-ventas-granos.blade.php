<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Ventas de Granos</h5>
            <small class="text-muted">Registro de liquidaciones y operaciones de comercializaciÃ³n de granos</small>
        </div>
        @can('ventas.granos.registrar')
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
                               placeholder="Comprador, corredor, NÂ° comprobanteâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Cereal</label>
                    <select class="form-select" wire:model.live="filtroCereal">
                        <option value="">Todos</option>
                        @foreach ($cereales as $val => $etq)
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
                        <th>Cereal</th>
                        <th>Comprador / Corredor</th>
                        <th>Establecimiento</th>
                        <th>Tipo</th>
                        <th class="text-end">Cantidad (tn)</th>
                        <th class="text-end">Precio / tn</th>
                        <th class="text-end">Importe</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                    @php
                        $badgeEstado = [
                            'borrador'   => 'bg-secondary-subtle text-secondary',
                            'confirmada' => 'bg-info-subtle text-info-emphasis',
                            'cobrada'    => 'bg-success-subtle text-success',
                            'cancelada'  => 'bg-danger-subtle text-danger',
                        ][$venta->estado] ?? 'bg-secondary-subtle text-secondary';

                        $badgeCereal = [
                            'soja'    => 'bg-warning-subtle text-warning-emphasis',
                            'maiz'    => 'bg-success-subtle text-success',
                            'trigo'   => 'bg-warning-subtle text-warning-emphasis',
                            'girasol' => 'bg-warning-subtle text-warning-emphasis',
                        ][$venta->cereal] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $venta->fecha->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeCereal }}">{{ $venta->cereal_label }}</span>
                        </td>
                        <td>
                            <div>{{ $venta->comprador ?? 'â€”' }}</div>
                            @if ($venta->corredor)
                                <small class="text-muted"><i class="bi bi-person me-1"></i>{{ $venta->corredor }}</small>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $venta->establecimiento?->nombre ?? 'â€”' }}</small></td>
                        <td><small>{{ $venta->tipo_venta_label }}</small></td>
                        <td class="text-end font-monospace">{{ number_format((float)$venta->cantidad_tn, 3, ',', '.') }}</td>
                        <td class="text-end">
                            <span class="font-monospace">{{ number_format((float)$venta->precio_tn, 2, ',', '.') }}</span>
                            <small class="text-muted ms-1">{{ $venta->moneda }}</small>
                        </td>
                        <td class="text-end fw-bold">
                            {{ $venta->moneda === 'USD' ? 'U$S' : '$' }} {{ number_format((float)$venta->importe_total, 2, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">{{ $venta->estado_label }}</span>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('ventas.granos.registrar')
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
                            <i class="bi bi-bag display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron ventas de granos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if ($ventas->count())
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="ps-4 text-muted small">Total de la pÃ¡gina</td>
                        <td class="text-end font-monospace fw-semibold">
                            {{ number_format($ventas->sum(fn($v) => (float)$v->cantidad_tn), 3, ',', '.') }} tn
                        </td>
                        <td></td>
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
                        <i class="bi bi-bag me-2 text-success"></i>
                        {{ $modoEdicion ? 'Editar venta de granos' : 'Registrar venta de granos' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-venta-granos">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">OperaciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Cereal <span class="text-danger">*</span></label>
                                <select class="form-select @error('cereal') is-invalid @enderror" wire:model="cereal">
                                    <option value="">Seleccionarâ€¦</option>
                                    @foreach ($cereales as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('cereal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo de venta <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_venta') is-invalid @enderror" wire:model="tipo_venta">
                                    @foreach ($tiposVenta as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_venta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha entrega</label>
                                <input type="date" class="form-control @error('fecha_entrega') is-invalid @enderror"
                                       wire:model="fecha_entrega">
                                @error('fecha_entrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Establecimiento</label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model="id_establecimiento">
                                    <option value="">Sin asignar</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CampaÃ±a</label>
                                <select class="form-select @error('id_campana') is-invalid @enderror"
                                        wire:model="id_campana">
                                    <option value="">Sin campaÃ±a</option>
                                    @foreach ($campanas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_campana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Comprador y liquidaciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Comprador</label>
                                <input type="text" class="form-control @error('comprador') is-invalid @enderror"
                                       wire:model="comprador" placeholder="Nombre o razÃ³n social">
                                @error('comprador') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">CUIT comprador</label>
                                <input type="text" class="form-control font-monospace @error('cuit_comprador') is-invalid @enderror"
                                       wire:model="cuit_comprador" placeholder="XX-XXXXXXXX-X">
                                @error('cuit_comprador') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">NÂ° comprobante</label>
                                <input type="text" class="form-control font-monospace @error('numero_comprobante') is-invalid @enderror"
                                       wire:model="numero_comprobante" placeholder="LiquidaciÃ³n / CPAâ€¦">
                                @error('numero_comprobante') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Corredor / Acopiador</label>
                                <input type="text" class="form-control @error('corredor') is-invalid @enderror"
                                       wire:model="corredor" placeholder="Nombre del corredor">
                                @error('corredor') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Valores</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Cantidad (tn) <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" min="0"
                                       class="form-control font-monospace @error('cantidad_tn') is-invalid @enderror"
                                       wire:model.live="cantidad_tn" placeholder="0.000">
                                @error('cantidad_tn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Precio / tn <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control font-monospace @error('precio_tn') is-invalid @enderror"
                                       wire:model.live="precio_tn" placeholder="0.00">
                                @error('precio_tn') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Importe total</label>
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
                    <button type="submit" form="form-venta-granos" class="btn btn-success"
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
