<div>

    {{-- Flash --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- KPIs de hoy --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-0">
                    <div class="fs-2 fw-bold text-primary">{{ number_format((float)$totalKgHoy, 0, ',', '.') }}</div>
                    <div class="small text-muted">kg registrados hoy</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-0">
                    <div class="fs-2 fw-bold text-success">$ {{ number_format((float)$totalCostoHoy, 0, ',', '.') }}</div>
                    <div class="small text-muted">costo total hoy</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-0">
                    <div class="fs-2 fw-bold text-info">{{ $registrosHoy }}</div>
                    <div class="small text-muted">registros hoy</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaHasta">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Corral</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroCorral">
                        <option value="">Todos</option>
                        @foreach ($corrales as $corral)
                            <option value="{{ $corral->id }}">{{ $corral->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Tropa</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroTropa">
                        <option value="">Todas</option>
                        @foreach ($tropasFilter as $tropa)
                            <option value="{{ $tropa->id }}">{{ $tropa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end d-flex align-items-end gap-2 justify-content-end">
                    @can('feedlot.consumos.registrar')
                    <a href="#" class="btn btn-outline-secondary btn-sm"
                       wire:click.prevent="exportarCsv">
                        <i class="bi bi-download me-1"></i> CSV
                    </a>
                    <button class="btn btn-primary btn-sm" wire:click="abrirModalRegistrar">
                        <i class="bi bi-plus-lg me-1"></i> Registrar consumo
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Totales del filtro --}}
    <div class="d-flex gap-4 mb-3 small text-muted">
        <span><i class="bi bi-box me-1"></i> Total kg (filtro): <strong class="text-dark">{{ number_format((float)$totalKgFiltro, 0, ',', '.') }} kg</strong></span>
        <span><i class="bi bi-currency-dollar me-1"></i> Total costo: <strong class="text-dark">$ {{ number_format((float)$totalCostoFiltro, 0, ',', '.') }}</strong></span>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fecha</th>
                        <th>Corral</th>
                        <th>Tropa</th>
                        <th>Alimento</th>
                        <th class="text-end">Cantidad (kg)</th>
                        <th class="text-end">$/kg</th>
                        <th class="text-end">Total ($)</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($consumos as $consumo)
                    <tr>
                        <td class="ps-3 small font-monospace">{{ $consumo->fecha->format('d/m/Y') }}</td>
                        <td class="small">{{ $consumo->corral?->nombre ?? '—' }}</td>
                        <td class="small">{{ $consumo->tropa?->nombre ?? '—' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border small">
                                @if ($consumo->insumo)
                                    <i class="bi bi-box me-1"></i>
                                @endif
                                {{ $consumo->nombre_alimento }}
                            </span>
                        </td>
                        <td class="text-end font-monospace small fw-semibold">{{ number_format((float)$consumo->cantidad_kg, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace small text-muted">
                            {{ $consumo->costo_unitario !== null ? '$ ' . number_format((float)$consumo->costo_unitario, 2, ',', '.') : '—' }}
                        </td>
                        <td class="text-end font-monospace small fw-semibold">
                            {{ $consumo->costo_total !== null ? '$ ' . number_format((float)$consumo->costo_total, 0, ',', '.') : '—' }}
                        </td>
                        <td class="text-end pe-3">
                            @can('feedlot.consumos.registrar')
                            <button class="btn btn-outline-danger btn-sm"
                                    wire:click="eliminar('{{ $consumo->id }}')"
                                    wire:confirm="¿Eliminar este registro de consumo?"
                                    title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-journal-x d-block fs-2 mb-2 opacity-50"></i>
                            No hay registros para el período seleccionado.
                            @can('feedlot.consumos.registrar')
                            <a href="#" wire:click.prevent="abrirModalRegistrar" class="d-block small mt-1">Registrar el primero</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($consumos->hasPages())
        <div class="card-footer bg-white border-top py-2 px-3">
            {{ $consumos->links() }}
        </div>
        @endif
    </div>

    {{-- ===== MODAL REGISTRAR ===== --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-journal-plus me-2 text-primary"></i>
                        Registrar consumo de alimento
                    </h6>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        {{-- Fecha --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                   wire:model="fecha">
                            @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Corral --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Corral</label>
                            <select class="form-select @error('idCorral') is-invalid @enderror"
                                    wire:model.live="idCorral">
                                <option value="">— Sin asignar —</option>
                                @foreach ($corrales as $corral)
                                    <option value="{{ $corral->id }}">{{ $corral->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idCorral')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Tropa --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Tropa</label>
                            <select class="form-select @error('idTropa') is-invalid @enderror" wire:model="idTropa">
                                <option value="">— Sin asignar —</option>
                                @foreach ($tropasModal as $tropa)
                                    <option value="{{ $tropa->id }}">{{ $tropa->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idTropa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Insumo del catálogo --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Insumo (catálogo)
                                <span class="text-muted fw-normal">— o escribí abajo</span>
                            </label>
                            <select class="form-select @error('idInsumo') is-invalid @enderror"
                                    wire:model.live="idInsumo">
                                <option value="">— Seleccionar del catálogo —</option>
                                @foreach ($insumos as $insumo)
                                    <option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idInsumo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Descripción libre --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Descripción alimento
                                @if (!$idInsumo) <span class="text-danger">*</span> @endif
                            </label>
                            <input type="text" class="form-control @error('descripcionAlimento') is-invalid @enderror"
                                   wire:model="descripcionAlimento"
                                   placeholder="Ej: Silaje de maíz, heno de alfalfa..."
                                   {{ $idInsumo ? 'readonly' : '' }}>
                            @error('descripcionAlimento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Cantidad --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Cantidad (kg) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('cantidadKg') is-invalid @enderror"
                                       wire:model.live="cantidadKg" step="0.01" min="0.01"
                                       placeholder="0.00">
                                <span class="input-group-text text-muted small">kg</span>
                            </div>
                            @error('cantidadKg')<div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>@enderror
                        </div>
                        {{-- Costo unitario --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Costo unitario ($/kg)</label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">$</span>
                                <input type="number" class="form-control @error('costoUnitario') is-invalid @enderror"
                                       wire:model.live="costoUnitario" step="0.0001" min="0"
                                       placeholder="0.0000">
                            </div>
                            @error('costoUnitario')<div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>@enderror
                        </div>
                        {{-- Costo total --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Costo total ($)</label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small">$</span>
                                <input type="number" class="form-control bg-light @error('costoTotal') is-invalid @enderror"
                                       wire:model="costoTotal" step="0.01" min="0"
                                       placeholder="calculado automático"
                                       readonly>
                            </div>
                            @error('costoTotal')<div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Observaciones --}}
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1">Observaciones</label>
                            <textarea class="form-control" wire:model="observaciones" rows="2"
                                      placeholder="Lote, procedencia, condición del alimento..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 gap-2">
                    <button type="button" class="btn btn-light btn-sm" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="guardar" wire:loading.attr="disabled">
                        <span wire:loading wire:target="guardar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="guardar" class="bi bi-check-lg me-1"></i>
                        Registrar consumo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
