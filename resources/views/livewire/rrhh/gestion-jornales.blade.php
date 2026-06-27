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
            <h5 class="mb-0 fw-bold text-dark">Jornales</h5>
            <small class="text-muted">Registro diario de trabajo y liquidaciones</small>
        </div>
        @can('rrhh.jornales.registrar')
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success"
                    wire:click="liquidarPendientes"
                    wire:loading.attr="disabled"
                    wire:confirm="Â¿Liquidar todos los jornales pendientes del filtro actual?">
                <i class="bi bi-check2-all me-1"></i> Liquidar pendientes
            </button>
            <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
                <i class="bi bi-plus-lg me-1"></i> Nuevo jornal
            </button>
        </div>
        @endcan
    </div>

    {{-- Resumen financiero --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Pendiente de pago</div>
                            <div class="fs-4 fw-bold text-warning-emphasis">
                                ${{ number_format((float)$totalPendiente, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="bg-warning-subtle rounded-3 p-2">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Liquidado</div>
                            <div class="fs-4 fw-bold text-success">
                                ${{ number_format((float)$totalLiquidado, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="bg-success-subtle rounded-3 p-2">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Total filtro</div>
                            <div class="fs-4 fw-bold text-dark">
                                ${{ number_format((float)$totalPendiente + (float)$totalLiquidado, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="bg-primary-subtle rounded-3 p-2">
                            <i class="bi bi-cash-stack text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
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
                               placeholder="Empleado, tareaâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Empleado</label>
                    <select class="form-select" wire:model.live="filtroEmpleado">
                        <option value="">Todos</option>
                        @foreach ($empleadosOpciones as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->apellido }}, {{ $emp->nombre }}</option>
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
                        <th>Empleado</th>
                        <th>Establecimiento</th>
                        <th>Jornada</th>
                        <th class="text-center">Horas</th>
                        <th>Tarea</th>
                        <th class="text-end">Importe</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jornales as $jornal)
                    @php
                        $badgeEstado = [
                            'pendiente' => 'bg-warning-subtle text-warning-emphasis',
                            'liquidado' => 'bg-success-subtle text-success',
                            'anulado'   => 'bg-danger-subtle text-danger',
                        ][$jornal->estado] ?? 'bg-secondary-subtle text-secondary';

                        $badgeJornada = [
                            'completa'   => 'bg-primary-subtle text-primary',
                            'media'      => 'bg-info-subtle text-info-emphasis',
                            'hora_extra' => 'bg-warning-subtle text-warning-emphasis',
                            'feriado'    => 'bg-success-subtle text-success',
                            'ausencia'   => 'bg-danger-subtle text-danger',
                        ][$jornal->tipo_jornada] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <tr class="{{ $jornal->tipo_jornada === 'ausencia' ? 'opacity-75' : '' }}">
                        <td class="ps-4 text-nowrap">{{ $jornal->fecha->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold">
                                {{ $jornal->empleado ? $jornal->empleado->apellido . ', ' . $jornal->empleado->nombre : 'â€”' }}
                            </div>
                            @if ($jornal->empleado?->categoria)
                                <small class="text-muted">{{ $jornal->empleado->categoria_label }}</small>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $jornal->establecimiento?->nombre ?? 'â€”' }}</small></td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeJornada }}">{{ $jornal->tipo_jornada_label }}</span>
                        </td>
                        <td class="text-center">
                            {{ $jornal->horas_trabajadas !== null ? number_format((float)$jornal->horas_trabajadas, 1, ',', '') . ' hs' : 'â€”' }}
                        </td>
                        <td><small>{{ $jornal->tarea ?? 'â€”' }}</small></td>
                        <td class="text-end fw-semibold {{ $jornal->tipo_jornada === 'ausencia' ? 'text-muted' : '' }}">
                            @if ($jornal->tipo_jornada !== 'ausencia')
                                ${{ number_format((float)$jornal->importe, 2, ',', '.') }}
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">{{ $jornal->estado_label }}</span>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('rrhh.jornales.registrar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $jornal->id }}')"
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
                                    @if ($val !== $jornal->estado)
                                    <li>
                                        <button class="dropdown-item small"
                                                wire:click="cambiarEstado('{{ $jornal->id }}', '{{ $val }}')">
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
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-calendar2-check display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron jornales.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if ($jornales->count())
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="ps-4 text-muted small">Total de la pÃ¡gina</td>
                        <td class="text-end fw-bold">
                            ${{ number_format($jornales->sum(fn($j) => $j->tipo_jornada !== 'ausencia' ? (float)$j->importe : 0), 2, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if ($jornales->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $jornales->links() }}
        </div>
        @endif
    </div>

    {{-- Modal --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar2-check me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar jornal' : 'Registrar jornal' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-jornal">

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Empleado</label>
                                <select class="form-select @error('id_empleado') is-invalid @enderror"
                                        wire:model.live="id_empleado">
                                    <option value="">Sin asignar</option>
                                    @foreach ($empleadosOpciones as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->apellido }}, {{ $emp->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_empleado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if ($sueldoReferencia)
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>Sueldo/jornal base: <strong>{{ $sueldoReferencia }}</strong>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-7">
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
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Tipo de jornada <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_jornada') is-invalid @enderror"
                                        wire:model="tipo_jornada">
                                    @foreach ($tiposJornada as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_jornada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Horas trabajadas</label>
                                <div class="input-group">
                                    <input type="number" step="0.5" min="0" max="24"
                                           class="form-control font-monospace @error('horas_trabajadas') is-invalid @enderror"
                                           wire:model="horas_trabajadas" placeholder="0">
                                    <span class="input-group-text">hs</span>
                                    @error('horas_trabajadas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Importe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control font-monospace @error('importe') is-invalid @enderror"
                                           wire:model="importe" placeholder="0.00">
                                    @error('importe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select @error('estado') is-invalid @enderror" wire:model="estado">
                                    @foreach ($estados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Tarea realizada</label>
                                <input type="text" class="form-control @error('tarea') is-invalid @enderror"
                                       wire:model="tarea" placeholder="DescripciÃ³n de la tarea (siembra, cosecha, fumigaciÃ³nâ€¦)">
                                @error('tarea') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <button type="submit" form="form-jornal" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar jornal' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
