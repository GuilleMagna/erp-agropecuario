<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3 py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabs ──────────────────────────────────────────────────────────────── --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link {{ $vistaActiva === 'ingresos' ? 'active fw-semibold' : '' }}"
                    wire:click="$set('vistaActiva','ingresos')">
                <i class="bi bi-cash-coin me-1"></i>Ingresos por mes
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $vistaActiva === 'catalogo' ? 'active fw-semibold' : '' }}"
                    wire:click="$set('vistaActiva','catalogo')">
                <i class="bi bi-building me-1"></i>Inmuebles
                <span class="badge bg-secondary ms-1">{{ $catalogo->count() }}</span>
            </button>
        </li>
    </ul>

    {{-- ── VISTA INGRESOS ───────────────────────────────────────────────── --}}
    @if ($vistaActiva === 'ingresos')

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button class="btn btn-outline-secondary btn-sm" wire:click="mesPrevio">
                        <i class="bi bi-chevron-left"></i>
                    </button>

                    <input type="month" class="form-control form-control-sm"
                           style="width:160px"
                           wire:model.live="mesSeleccionado">

                    <span class="fw-semibold text-dark fs-6 text-capitalize">{{ $mesLabel }}</span>

                    <button class="btn btn-outline-secondary btn-sm" wire:click="mesSiguiente">
                        <i class="bi bi-chevron-right"></i>
                    </button>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        @if ($hayDirtyCambios)
                            <span class="text-warning small fw-semibold">
                                <i class="bi bi-exclamation-circle me-1"></i>Cambios sin guardar
                            </span>
                        @endif

                        <span class="text-muted small">
                            Ingresos: <strong class="text-success">$ {{ number_format($totalIngresosMes, 2, ',', '.') }}</strong>
                        </span>

                        <button class="btn btn-primary btn-sm"
                                wire:click="guardarMes"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="guardarMes">
                                <i class="bi bi-floppy me-1"></i>Guardar mes
                            </span>
                            <span wire:loading wire:target="guardarMes">
                                <span class="spinner-border spinner-border-sm me-1"></span>Guardando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:25%">Inmueble</th>
                            <th class="text-end" style="width:15%">Gastos del mes</th>
                            <th style="width:18%">Ingreso (alquiler)</th>
                            <th style="width:15%">Fecha de cobro</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inmueblesActivos as $inmueble)
                            @php $gastoMes = (float) ($gastosMesPorInmueble[$inmueble->id] ?? 0); @endphp
                            <tr>
                                <td class="ps-2">
                                    <div class="fw-medium">{{ $inmueble->nombre }}</div>
                                    <small class="text-muted">{{ $inmueble->localidad }}</small>
                                </td>
                                <td class="text-end font-monospace text-danger small">
                                    $ {{ number_format($gastoMes, 2, ',', '.') }}
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input type="text"
                                               class="form-control text-end font-monospace"
                                               wire:model.live="ingresos.{{ $inmueble->id }}.importe"
                                               placeholder="0,00"
                                               style="min-width:100px">
                                    </div>
                                </td>
                                <td>
                                    <input type="date"
                                           class="form-control form-control-sm"
                                           wire:model.live="ingresos.{{ $inmueble->id }}.fecha_cobro">
                                </td>
                                <td>
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           wire:model.live="ingresos.{{ $inmueble->id }}.notas"
                                           placeholder="—">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-building fs-2 d-block mb-2 opacity-25"></i>
                                    No hay inmuebles activos. Agregalos en la pestaña "Inmuebles".
                                </td>
                            </tr>
                        @endforelse

                        @if ($inmueblesActivos->count() > 0)
                            <tr class="table-primary">
                                <td class="fw-bold">TOTAL {{ strtoupper($mesLabel) }}</td>
                                <td class="text-end fw-bold font-monospace">
                                    $ {{ number_format($gastosMesPorInmueble->sum(), 2, ',', '.') }}
                                </td>
                                <td class="fw-bold font-monospace">$ {{ number_format($totalIngresosMes, 2, ',', '.') }}</td>
                                <td colspan="2"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- ── VISTA CATÁLOGO ───────────────────────────────────────────────── --}}
    @if ($vistaActiva === 'catalogo')

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
                <i class="bi bi-plus-circle me-1"></i>Nuevo inmueble
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Localidad</th>
                            <th class="text-center">Gastos NO ARCA vinculados</th>
                            <th class="text-center">Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($catalogo as $inmueble)
                            <tr>
                                <td class="fw-medium">{{ $inmueble->nombre }}</td>
                                <td>{{ $inmueble->localidad ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">{{ $inmueble->gastosNoArca()->count() }}</span>
                                </td>
                                <td class="text-center">
                                    <button wire:click="toggleActivo('{{ $inmueble->id }}')"
                                            class="btn btn-sm btn-link p-0 border-0">
                                        <span class="badge rounded-pill {{ $inmueble->activo ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $inmueble->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </button>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            wire:click="abrirModalEditar('{{ $inmueble->id }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-building fs-2 d-block mb-2 opacity-25"></i>
                                    No hay inmuebles registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- ── Modal inmueble ───────────────────────────────────────────────── --}}
    @if ($modalAbierto)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-building me-2 text-primary"></i>
                            {{ $modoEdicion ? 'Editar inmueble' : 'Nuevo inmueble' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   wire:model="nombre" placeholder="Ej: Veneto V">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Localidad</label>
                            <input type="text" class="form-control" wire:model="localidad" placeholder="Ej: Carlos Paz">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="activo" id="chkActivoInmueble">
                            <label class="form-check-label small" for="chkActivoInmueble">Activo (aparece en la grilla de ingresos)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="cerrarModal">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="guardarInmueble" wire:loading.attr="disabled">
                            <span wire:loading wire:target="guardarInmueble" class="spinner-border spinner-border-sm me-1"></span>
                            <i wire:loading.remove wire:target="guardarInmueble" class="bi bi-floppy me-1"></i>
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
