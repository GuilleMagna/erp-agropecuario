<div>
    {{-- Alertas --}}
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Cabecera --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Comprobantes de Compra</h1>
        <div class="d-flex gap-2">
            @can('compras.crear')
                <a href="{{ route('compras.importar-arca') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-upload me-1"></i> Importar ARCA
                </a>
                <button type="button" class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Compra
                </button>
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" placeholder="Buscar número o proveedor…"
                           wire:model.live.debounce.400ms="busqueda">
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filtroProveedor">
                        <option value="">Todos los proveedores</option>
                        @foreach ($proveedoresOpciones as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filtroActividad">
                        <option value="">Todas las actividades</option>
                        @foreach ($actividades as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaDesde"
                           title="Desde">
                </div>
                <div class="col-md-1">
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroFechaHasta"
                           title="Hasta">
                </div>
                <div class="col-md-1">
                    <select class="form-select form-select-sm" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $e)
                            <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:40px;">
                                @php
                                    $idsEnPagina = $compras->pluck('id')->toArray();
                                    $todosSeleccionados = count($idsEnPagina) > 0
                                        && count(array_diff($idsEnPagina, $seleccionados)) === 0;
                                @endphp
                                <input type="checkbox" class="form-check-input"
                                    wire:click="toggleTodos({{ json_encode($idsEnPagina) }})"
                                    @checked($todosSeleccionados)>
                            </th>
                            <th>Fecha</th>
                            <th>Comprobante</th>
                            <th>Proveedor</th>
                            <th>Actividad</th>
                            <th>Imputación</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compras as $compra)
                            <tr class="{{ in_array($compra->id, $seleccionados) ? 'table-primary' : '' }}">
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input"
                                        wire:model.live="seleccionados"
                                        value="{{ $compra->id }}">
                                </td>
                                <td class="text-nowrap">{{ $compra->fecha->format('d/m/Y') }}</td>
                                <td class="text-nowrap">
                                    <span class="badge bg-secondary">{{ $compra->tipo_comprobante_label }}</span>
                                    @if ($compra->numero_comprobante)
                                        <small class="text-muted ms-1">{{ $compra->numero_comprobante }}</small>
                                    @endif
                                </td>
                                <td>{{ $compra->proveedor?->nombre ?? '—' }}</td>
                                <td>
                                    @if ($compra->actividad)
                                        @php
                                            $colorMap = ['agricultura'=>'success','ganaderia'=>'warning','feedlot'=>'info','general'=>'secondary'];
                                            $color = $colorMap[$compra->actividad] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }} bg-opacity-75">{{ $compra->actividad_label }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    @if ($compra->lote)
                                        <i class="bi bi-map me-1"></i>{{ $compra->lote->nombre }}
                                    @endif
                                    @if ($compra->campana)
                                        @if ($compra->lote) · @endif
                                        <i class="bi bi-calendar2-range me-1"></i>{{ $compra->campana->nombre }}
                                    @endif
                                    @if (!$compra->lote && !$compra->campana) — @endif
                                </td>
                                <td class="text-end text-nowrap fw-semibold">
                                    ${{ number_format($compra->total, 2, ',', '.') }}
                                </td>
                                <td>
                                    @php
                                        $badgeMap = ['pendiente'=>'warning','recibida'=>'primary','pagada'=>'success','cancelada'=>'secondary'];
                                        $badge = $badgeMap[$compra->estado] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ $compra->estado_label }}</span>
                                    @if ($compra->stock_registrado)
                                        <span class="badge bg-info ms-1" title="Stock registrado">
                                            <i class="bi bi-box-seam"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    @can('compras.editar')
                                        <button class="btn btn-sm btn-outline-primary py-0 px-1"
                                                wire:click="abrirModalEditar('{{ $compra->id }}')"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @if (!$compra->stock_registrado && $compra->items_count > 0)
                                            <button class="btn btn-sm btn-outline-info py-0 px-1 ms-1"
                                                    wire:click="registrarEnStock('{{ $compra->id }}')"
                                                    wire:confirm="¿Registrar este comprobante en stock?"
                                                    title="Registrar en stock">
                                                <i class="bi bi-box-seam"></i>
                                            </button>
                                        @endif
                                        <div class="btn-group ms-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 dropdown-toggle"
                                                    data-bs-toggle="dropdown" title="Cambiar estado">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @foreach ($estados as $k => $v)
                                                    @if ($k !== $compra->estado)
                                                        <li>
                                                            <button class="dropdown-item small"
                                                                    wire:click="cambiarEstado('{{ $compra->id }}', '{{ $k }}')">
                                                                {{ $v }}
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
                                <td colspan="9" class="text-center text-muted py-4">
                                    No se encontraron comprobantes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($compras->hasPages())
            <div class="card-footer">
                {{ $compras->links() }}
            </div>
        @endif
    </div>

    {{-- ── BARRA FLOTANTE DE SELECCIÓN MASIVA ─────────────────────────────── --}}
    @if (count($seleccionados) > 0)
        <div class="position-fixed bottom-0 start-50 translate-middle-x mb-4"
             style="z-index:1050; min-width:420px;">
            <div class="card shadow-lg border-primary">
                <div class="card-body d-flex align-items-center gap-3 py-2 px-3">
                    <span class="badge bg-primary fs-6">{{ count($seleccionados) }}</span>
                    <span class="text-body-secondary small">comprobante(s) seleccionado(s)</span>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-primary btn-sm" wire:click="abrirModalMasivo">
                            <i class="bi bi-pencil-square me-1"></i> Editar selección
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" wire:click="limpiarSeleccion">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── MODAL INDIVIDUAL ────────────────────────────────────────────────── --}}
    <div class="modal fade {{ $modalAbierto ? 'show d-block' : '' }}"
         tabindex="-1" style="{{ $modalAbierto ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $modoEdicion ? 'Editar Comprobante' : 'Nuevo Comprobante' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Cabecera del comprobante --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tipo Comprobante *</label>
                            <select class="form-select form-select-sm @error('tipo_comprobante') is-invalid @enderror"
                                    wire:model="tipo_comprobante">
                                @foreach ($tiposComprobante as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('tipo_comprobante') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Número</label>
                            <input type="text" class="form-control form-control-sm @error('numero_comprobante') is-invalid @enderror"
                                   wire:model="numero_comprobante" placeholder="0001-00012345">
                            @error('numero_comprobante') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Fecha *</label>
                            <input type="date" class="form-control form-control-sm @error('fecha') is-invalid @enderror"
                                   wire:model="fecha">
                            @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Vencimiento</label>
                            <input type="date" class="form-control form-control-sm"
                                   wire:model="fecha_vencimiento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Proveedor</label>
                            <select class="form-select form-select-sm" wire:model="id_proveedor">
                                <option value="">— Sin proveedor —</option>
                                @foreach ($proveedoresOpciones as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Establecimiento</label>
                            <select class="form-select form-select-sm" wire:model="id_establecimiento">
                                <option value="">— Todos —</option>
                                @foreach ($establecimientos as $e)
                                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Estado</label>
                            <select class="form-select form-select-sm" wire:model="estado">
                                @foreach ($estados as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">IVA %</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   class="form-control form-control-sm @error('iva_porc') is-invalid @enderror"
                                   wire:model.live="iva_porc" placeholder="21">
                            @error('iva_porc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Imputación --}}
                    <div class="border rounded p-2 mb-3 bg-light">
                        <div class="small fw-semibold text-muted mb-2">
                            <i class="bi bi-tags me-1"></i> Imputación
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Actividad</label>
                                <select class="form-select form-select-sm" wire:model="actividad">
                                    @foreach ($actividades as $k => $v)
                                        <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Lote</label>
                                <select class="form-select form-select-sm" wire:model="id_lote">
                                    <option value="">— Sin lote —</option>
                                    @foreach ($lotes as $l)
                                        <option value="{{ $l->id }}">{{ $l->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Campaña</label>
                                <select class="form-select form-select-sm" wire:model="id_campana">
                                    <option value="">— Sin campaña —</option>
                                    @foreach ($campanas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Ítems --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Ítems</h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="agregarItem">
                            <i class="bi bi-plus-lg me-1"></i> Agregar ítem
                        </button>
                    </div>
                    @error('items') <div class="alert alert-danger py-1 small">{{ $message }}</div> @enderror

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:22%">Insumo (opcional)</th>
                                    <th style="width:25%">Descripción *</th>
                                    <th style="width:10%">Cantidad *</th>
                                    <th style="width:8%">Unidad</th>
                                    <th style="width:13%">Precio unit. *</th>
                                    <th style="width:13%">Subtotal</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $i => $item)
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm"
                                                    wire:model.live="items.{{ $i }}.id_insumo">
                                                <option value="">— Sin insumo —</option>
                                                @foreach ($insumosOpciones as $ins)
                                                    <option value="{{ $ins->id }}">{{ $ins->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm @error('items.'.$i.'.descripcion') is-invalid @enderror"
                                                   wire:model="items.{{ $i }}.descripcion">
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0"
                                                   class="form-control form-control-sm @error('items.'.$i.'.cantidad') is-invalid @enderror"
                                                   wire:model.live="items.{{ $i }}.cantidad">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                   wire:model="items.{{ $i }}.unidad" placeholder="kg">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control form-control-sm @error('items.'.$i.'.precio_unitario') is-invalid @enderror"
                                                   wire:model.live="items.{{ $i }}.precio_unitario">
                                        </td>
                                        <td class="text-end align-middle fw-semibold">
                                            ${{ number_format((float)($item['subtotal'] ?? 0), 2, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            @if (count($items) > 1)
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                                        wire:click="quitarItem({{ $i }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Totales --}}
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted small">Subtotal</td>
                                    <td class="text-end fw-semibold">${{ number_format((float)$subtotal, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">IVA ({{ $iva_porc ?: '0' }}%)</td>
                                    <td class="text-end">${{ number_format((float)$iva_importe, 2, ',', '.') }}</td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td>Total</td>
                                    <td class="text-end fs-5">${{ number_format((float)$total, 2, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-semibold">Observaciones</label>
                        <textarea class="form-control form-control-sm" rows="2" wire:model="observaciones"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="guardar"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MODAL ACCIÓN MASIVA ─────────────────────────────────────────────── --}}
    <div class="modal fade {{ $modalMasivoAbierto ? 'show d-block' : '' }}"
         tabindex="-1" style="{{ $modalMasivoAbierto ? 'background:rgba(0,0,0,.5)' : '' }}">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edición masiva
                        <span class="badge bg-primary ms-2">{{ count($seleccionados) }} seleccionados</span>
                    </h5>
                    <button type="button" class="btn-close"
                            wire:click="$set('modalMasivoAbierto', false)"></button>
                </div>
                <div class="modal-body">
                    {{-- Selector de acción --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">¿Qué desea modificar?</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="radio" class="btn-check" name="accionMasiva" id="am-estado"
                                   wire:model.live="accionMasiva" value="estado">
                            <label class="btn btn-sm btn-outline-secondary" for="am-estado">
                                <i class="bi bi-arrow-repeat me-1"></i>Estado
                            </label>

                            <input type="radio" class="btn-check" name="accionMasiva" id="am-actividad"
                                   wire:model.live="accionMasiva" value="actividad">
                            <label class="btn btn-sm btn-outline-secondary" for="am-actividad">
                                <i class="bi bi-tags me-1"></i>Actividad
                            </label>

                            <input type="radio" class="btn-check" name="accionMasiva" id="am-imputacion"
                                   wire:model.live="accionMasiva" value="imputacion">
                            <label class="btn btn-sm btn-outline-secondary" for="am-imputacion">
                                <i class="bi bi-diagram-3 me-1"></i>Imputación completa
                            </label>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Campos según acción --}}
                    @if ($accionMasiva === 'estado')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nuevo estado *</label>
                            <select class="form-select @error('valorMasivoEstado') is-invalid @enderror"
                                    wire:model="valorMasivoEstado">
                                <option value="">— Seleccionar —</option>
                                @foreach ($estados as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('valorMasivoEstado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    @elseif ($accionMasiva === 'actividad')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Actividad *</label>
                            <select class="form-select @error('valorMasivoActividad') is-invalid @enderror"
                                    wire:model="valorMasivoActividad">
                                <option value="">— Seleccionar —</option>
                                @foreach ($actividades as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('valorMasivoActividad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    @elseif ($accionMasiva === 'imputacion')
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Actividad *</label>
                                <select class="form-select @error('valorMasivoActividad') is-invalid @enderror"
                                        wire:model="valorMasivoActividad">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($actividades as $k => $v)
                                        <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                                @error('valorMasivoActividad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Lote (opcional)</label>
                                <select class="form-select" wire:model="lotesMasivo">
                                    <option value="">— Sin lote —</option>
                                    @foreach ($lotes as $l)
                                        <option value="{{ $l->id }}">{{ $l->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Campaña (opcional)</label>
                                <select class="form-select" wire:model="campanaMasivo">
                                    <option value="">— Sin campaña —</option>
                                    @foreach ($campanas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            wire:click="$set('modalMasivoAbierto', false)">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="aplicarMasivo"
                            wire:loading.attr="disabled" wire:target="aplicarMasivo">
                        <span wire:loading wire:target="aplicarMasivo" class="spinner-border spinner-border-sm me-1"></span>
                        Aplicar a {{ count($seleccionados) }} comprobante(s)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
