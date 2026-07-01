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
            <button class="nav-link {{ $vistaActiva === 'pagos' ? 'active fw-semibold' : '' }}"
                    wire:click="$set('vistaActiva','pagos')">
                <i class="bi bi-calendar-check me-1"></i>Pagos por mes
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $vistaActiva === 'catalogo' ? 'active fw-semibold' : '' }}"
                    wire:click="$set('vistaActiva','catalogo')">
                <i class="bi bi-list-ul me-1"></i>Catálogo de servicios
                <span class="badge bg-secondary ms-1">{{ $catalogo->count() }}</span>
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
                            <th style="width:35%">Servicio / Gasto</th>
                            <th class="text-muted small" style="width:10%">Categoría</th>
                            <th style="width:18%">Importe</th>
                            <th style="width:15%">Fecha de pago</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $categoriaActual = null;
                            $totalCategoria  = 0;
                        @endphp

                        @forelse ($gastosActivos as $gasto)
                            @php
                                $cat = \App\Models\GastoNoArca::CATEGORIAS[$gasto->categoria] ?? $gasto->categoria;
                                $imp = \Illuminate\Support\Arr::get($pagos, $gasto->id . '.importe', '');
                                $pagoImporte = strlen(trim($imp)) > 0 ? (float) str_replace(['.', ','], ['', '.'], $imp) : 0;
                            @endphp

                            {{-- Separador de categoría --}}
                            @if ($categoriaActual !== $gasto->categoria)
                                @if ($categoriaActual !== null && $totalCategoria > 0)
                                    <tr class="table-light">
                                        <td colspan="2" class="text-end text-muted small fw-semibold pe-3">
                                            Subtotal {{ \App\Models\GastoNoArca::CATEGORIAS[$categoriaActual] ?? $categoriaActual }}
                                        </td>
                                        <td class="fw-semibold small">$ {{ number_format($totalCategoria, 2, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                @endif
                                @php $categoriaActual = $gasto->categoria; $totalCategoria = 0; @endphp
                                <tr>
                                    <td colspan="5" class="py-1 px-3">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-tag me-1"></i>{{ $cat }}
                                        </span>
                                    </td>
                                </tr>
                            @endif
                            @php $totalCategoria += $pagoImporte; @endphp

                            <tr>
                                <td class="ps-4">{{ $gasto->nombre }}</td>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-list-ul fs-2 d-block mb-2 opacity-25"></i>
                                    No hay servicios. Agregalos en "Catálogo de servicios".
                                </td>
                            </tr>
                        @endforelse

                        {{-- Último subtotal de categoría --}}
                        @if ($categoriaActual !== null && $totalCategoria > 0)
                            <tr class="table-light">
                                <td colspan="2" class="text-end text-muted small fw-semibold pe-3">
                                    Subtotal {{ \App\Models\GastoNoArca::CATEGORIAS[$categoriaActual] ?? $categoriaActual }}
                                </td>
                                <td class="fw-semibold small">$ {{ number_format($totalCategoria, 2, ',', '.') }}</td>
                                <td colspan="2"></td>
                            </tr>
                        @endif

                        {{-- Total general --}}
                        @if ($gastosActivos->count() > 0)
                            <tr class="table-primary">
                                <td colspan="2" class="text-end fw-bold pe-3">TOTAL {{ strtoupper($mesLabel) }}</td>
                                <td class="fw-bold font-monospace">$ {{ number_format($totalMes, 2, ',', '.') }}</td>
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
                            <th class="text-center">Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($catalogo as $gasto)
                            <tr>
                                <td class="text-muted small">{{ $gasto->orden }}</td>
                                <td class="fw-medium">{{ $gasto->nombre }}</td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        {{ \App\Models\GastoNoArca::CATEGORIAS[$gasto->categoria] ?? $gasto->categoria }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button wire:click="toggleActivo('{{ $gasto->id }}')"
                                            class="btn btn-sm btn-link p-0 border-0">
                                        <span class="badge rounded-pill {{ $gasto->activo ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $gasto->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </button>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            wire:click="abrirModalEditar('{{ $gasto->id }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-list-ul fs-2 d-block mb-2 opacity-25"></i>
                                    No hay servicios registrados.
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
