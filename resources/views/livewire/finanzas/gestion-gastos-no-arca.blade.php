<div>
    <style>
        .fila-importe-pendiente > * {
            --bs-table-bg: #fff1f2;
            --bs-table-hover-bg: #ffe8ea;
            background-color: var(--bs-table-bg);
            transition: background-color .2s ease;
        }
    </style>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3 py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-warning alert-dismissible fade show mb-3 py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabs ──────────────────────────────────────────────────────────────── --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link {{ $vistaActiva === 'pagos' ? 'active fw-semibold' : '' }}"
                    wire:click="$set('vistaActiva','pagos')">
                <i class="bi bi-calendar-check me-1"></i>Pagos por mes
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $vistaActiva === 'catalogo' ? 'active fw-semibold' : '' }}"
                    wire:click="$set('vistaActiva','catalogo')">
                <i class="bi bi-list-ul me-1"></i>Catálogo de servicios
                <span class="badge bg-secondary ms-1">{{ $totalCatalogo }}</span>
            </button>
        </li>
    </ul>

    {{-- ── VISTA PAGOS ──────────────────────────────────────────────────── --}}
    @if ($vistaActiva === 'pagos')

        {{-- Selector de mes --}}
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

                    <button class="btn btn-outline-secondary btn-sm" wire:click="meSiguiente">
                        <i class="bi bi-chevron-right"></i>
                    </button>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        @if ($hayDirtyCambios)
                            <span class="text-warning small fw-semibold">
                                <i class="bi bi-exclamation-circle me-1"></i>Cambios sin guardar
                            </span>
                        @endif

                        <span class="text-muted small">
                            Total: <strong class="text-dark">$ {{ number_format($totalMes, 2, ',', '.') }}</strong>
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

        {{-- Grilla de pagos --}}
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:32%">Servicio / Gasto</th>
                            <th class="text-muted small" style="width:8%">Categoría</th>
                            <th style="width:18%">Importe</th>
                            <th style="width:15%">Fecha de pago</th>
                            <th>Notas</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gastosGrilla as $categoriaKey => $gastosCategoria)
                            @php
                                $cat = \App\Models\GastoNoArca::CATEGORIAS[$categoriaKey] ?? $categoriaKey;
                                $totalCategoria = 0;
                            @endphp

                            {{-- Encabezado de categoría --}}
                            <tr>
                                <td colspan="6" class="py-1 px-3">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-tag me-1"></i>{{ $cat }}
                                    </span>
                                </td>
                            </tr>

                            @foreach ($gastosCategoria as $gasto)
                                @php
                                    $imp = \Illuminate\Support\Arr::get($pagos, $gasto->id . '.importe', '');
                                    $pagoImporte = strlen(trim($imp)) > 0 ? (float) str_replace(['.', ','], ['', '.'], $imp) : 0;
                                    $totalCategoria += $pagoImporte;
                                @endphp

                                <tr @class(['fila-importe-pendiente' => trim((string) $imp) === '' && $gasto->activo])>
                                    <td class="ps-4">
                                        {{ $gasto->nombre }}
                                        @unless ($gasto->activo)
                                            <span class="badge bg-secondary-subtle text-secondary border ms-1"
                                                  title="Dado de baja: se muestra sólo porque tiene un pago cargado en este mes">
                                                dado de baja
                                            </span>
                                        @endunless
                                    </td>
                                    <td></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text"
                                                   class="form-control text-end font-monospace"
                                                   wire:model.live="pagos.{{ $gasto->id }}.importe"
                                                   placeholder="0,00"
                                                   style="min-width:100px">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="date"
                                               class="form-control form-control-sm"
                                               wire:model.live="pagos.{{ $gasto->id }}.fecha_pago">
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               wire:model.live="pagos.{{ $gasto->id }}.notas"
                                               placeholder="—">
                                    </td>
                                    <td class="text-end">
                                        @if ($gasto->activo)
                                            <button class="btn btn-sm btn-link text-danger p-0 border-0"
                                                    title="Este servicio no se paga más: darlo de baja"
                                                    wire:click="darDeBaja('{{ $gasto->id }}')"
                                                    wire:confirm="¿Dar de baja «{{ $gasto->nombre }}»? Deja de aparecer en la grilla de pagos. Los importes ya cargados se conservan y podés reactivarlo desde el catálogo.">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-link text-success p-0 border-0"
                                                    title="Reactivar servicio"
                                                    wire:click="toggleActivo('{{ $gasto->id }}')"
                                                    wire:confirm="¿Reactivar «{{ $gasto->nombre }}»? Vuelve a aparecer todos los meses.">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Subtotal de categoría --}}
                            @if ($totalCategoria > 0)
                                <tr class="table-light">
                                    <td colspan="2" class="text-end text-muted small fw-semibold pe-3">
                                        Subtotal {{ $cat }}
                                    </td>
                                    <td class="fw-semibold small">$ {{ number_format($totalCategoria, 2, ',', '.') }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-list-ul fs-2 d-block mb-2 opacity-25"></i>
                                    No hay servicios. Agregalos en "Catálogo de servicios".
                                </td>
                            </tr>
                        @endforelse

                        {{-- Total general --}}
                        @if ($gastosGrilla->count() > 0)
                            <tr class="table-primary">
                                <td colspan="2" class="text-end fw-bold pe-3">TOTAL {{ strtoupper($mesLabel) }}</td>
                                <td class="fw-bold font-monospace">$ {{ number_format($totalMes, 2, ',', '.') }}</td>
                                <td colspan="3"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- ── VISTA CATÁLOGO ───────────────────────────────────────────────── --}}
    @if ($vistaActiva === 'catalogo')

        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
            <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" style="width:220px"
                       wire:model.live="busqueda" placeholder="Buscar servicio...">
                <select class="form-select form-select-sm" style="width:180px"
                        wire:model.live="filtroCategoria">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" style="width:150px"
                        wire:model.live="filtroEstado">
                    <option value="">Activos e inactivos</option>
                    <option value="activos">Sólo activos</option>
                    <option value="inactivos">Sólo dados de baja</option>
                </select>
            </div>
            <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
                <i class="bi bi-plus-circle me-1"></i>Nuevo servicio
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Inmueble</th>
                            <th class="text-center">Pagos</th>
                            <th class="text-center">Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($catalogo as $gasto)
                            <tr @class(['opacity-75' => !$gasto->activo])>
                                <td class="text-muted small">{{ $gasto->orden }}</td>
                                <td class="fw-medium">{{ $gasto->nombre }}</td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        {{ \App\Models\GastoNoArca::CATEGORIAS[$gasto->categoria] ?? $gasto->categoria }}
                                    </span>
                                </td>
                                <td>
                                    @if ($gasto->inmueble)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <i class="bi bi-building me-1"></i>{{ $gasto->inmueble->nombre }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center text-muted small">
                                    {{ $gasto->pagos_count ?: '—' }}
                                </td>
                                <td class="text-center">
                                    <button wire:click="toggleActivo('{{ $gasto->id }}')"
                                            wire:confirm="{{ $gasto->activo
                                                ? '¿Dar de baja «' . $gasto->nombre . '»? Deja de aparecer en la grilla de pagos, pero se conserva el historial.'
                                                : '¿Reactivar «' . $gasto->nombre . '»? Vuelve a aparecer todos los meses.' }}"
                                            class="btn btn-sm btn-link p-0 border-0">
                                        <span class="badge rounded-pill {{ $gasto->activo ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $gasto->activo ? 'Activo' : 'Dado de baja' }}
                                        </span>
                                    </button>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            title="Editar"
                                            wire:click="abrirModalEditar('{{ $gasto->id }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if ($gasto->pagos_count === 0)
                                        <button class="btn btn-sm btn-outline-danger"
                                                title="Eliminar definitivamente (no tiene pagos cargados)"
                                                wire:click="eliminarServicio('{{ $gasto->id }}')"
                                                wire:confirm="¿Eliminar definitivamente «{{ $gasto->nombre }}»? No tiene pagos cargados, así que no se pierde historial.">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-danger" disabled
                                                title="No se puede eliminar: tiene {{ $gasto->pagos_count }} pago(s). Usá la baja.">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-list-ul fs-2 d-block mb-2 opacity-25"></i>
                                    No hay servicios que coincidan con el filtro.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- ── Modal servicio ───────────────────────────────────────────────── --}}
    @if ($modalAbierto)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-tag me-2 text-primary"></i>
                            {{ $modoEdicion ? 'Editar servicio' : 'Nuevo servicio' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   wire:model="nombre" placeholder="Ej: Aguas santafesinas">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-8">
                                <label class="form-label small fw-semibold">Categoría</label>
                                <select class="form-select" wire:model="categoria">
                                    @foreach ($categorias as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Orden</label>
                                <input type="number" class="form-control" wire:model="orden" min="0">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label small fw-semibold">Inmueble (opcional)</label>
                            <select class="form-select" wire:model="id_inmueble">
                                <option value="">Ninguno / gasto general</option>
                                @foreach ($inmuebles as $inmueble)
                                    <option value="{{ $inmueble->id }}">{{ $inmueble->nombre }} ({{ $inmueble->localidad }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Si lo asignás, este gasto se suma al reporte de ese inmueble.</small>
                        </div>
                        <div class="mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="activo" id="chkActivo">
                                <label class="form-check-label small" for="chkActivo">Activo (aparece en la grilla de pagos)</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="cerrarModal">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="guardarServicio" wire:loading.attr="disabled">
                            <span wire:loading wire:target="guardarServicio" class="spinner-border spinner-border-sm me-1"></span>
                            <i wire:loading.remove wire:target="guardarServicio" class="bi bi-floppy me-1"></i>
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
