<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Personal</h5>
            <small class="text-muted">Registro de empleados y contratos</small>
        </div>
        @can('rrhh.personal.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo empleado
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
                               placeholder="Nombre, apellido, DNI, CUILâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo de contrato</label>
                    <select class="form-select" wire:model.live="filtroContrato">
                        <option value="">Todos</option>
                        @foreach ($tiposContrato as $val => $etq)
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
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroActivo">
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                        <option value="">Todos</option>
                    </select>
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
                        <th class="ps-4">Empleado</th>
                        <th>DNI / CUIL</th>
                        <th>CategorÃ­a</th>
                        <th>Contrato</th>
                        <th>Establecimiento</th>
                        <th>Ingreso</th>
                        <th class="text-end">Sueldo base</th>
                        <th class="text-center">Jornales</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empleados as $emp)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $emp->apellido }}, {{ $emp->nombre }}</div>
                            @if ($emp->email)
                                <small class="text-muted">{{ $emp->email }}</small>
                            @endif
                        </td>
                        <td>
                            @if ($emp->dni)
                                <div class="font-monospace small">DNI: {{ $emp->dni }}</div>
                            @endif
                            @if ($emp->cuil)
                                <div class="font-monospace small text-muted">{{ $emp->cuil }}</div>
                            @endif
                            @if (!$emp->dni && !$emp->cuil)
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>
                            @if ($emp->categoria)
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">{{ $emp->categoria_label }}</span>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td><small>{{ $emp->tipo_contrato_label }}</small></td>
                        <td><small class="text-muted">{{ $emp->establecimiento?->nombre ?? 'â€”' }}</small></td>
                        <td class="text-nowrap"><small>{{ $emp->fecha_ingreso->format('d/m/Y') }}</small></td>
                        <td class="text-end font-monospace">
                            @if ($emp->sueldo_base !== null)
                                ${{ number_format((float)$emp->sueldo_base, 2, ',', '.') }}
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $emp->jornales_count }}</span>
                        </td>
                        <td>
                            @if ($emp->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('rrhh.personal.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $emp->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $emp->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $emp->id }}')"
                                    wire:confirm="{{ $emp->activo ? 'Â¿Dar de baja a este empleado?' : 'Â¿Reactivar este empleado?' }}"
                                    wire:loading.attr="disabled"
                                    title="{{ $emp->activo ? 'Dar de baja' : 'Reactivar' }}">
                                <i class="bi bi-{{ $emp->activo ? 'x-circle' : 'arrow-counterclockwise' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-people display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron empleados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($empleados->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $empleados->links() }}
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
                        <i class="bi bi-person-badge me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar empleado' : 'Nuevo empleado' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-personal">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">IdentificaciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('apellido') is-invalid @enderror"
                                       wire:model="apellido" placeholder="Apellido">
                                @error('apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre" placeholder="Nombre/s">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">DNI</label>
                                <input type="text" class="form-control font-monospace @error('dni') is-invalid @enderror"
                                       wire:model="dni" placeholder="12.345.678">
                                @error('dni') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CUIL</label>
                                <input type="text" class="form-control font-monospace @error('cuil') is-invalid @enderror"
                                       wire:model="cuil" placeholder="XX-XXXXXXXX-X">
                                @error('cuil') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">TelÃ©fono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                       wire:model="telefono" placeholder="+54 ...">
                                @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       wire:model="email" placeholder="empleado@ejemplo.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">DirecciÃ³n</label>
                                <input type="text" class="form-control @error('direccion') is-invalid @enderror"
                                       wire:model="direccion" placeholder="Calle, nÃºmero, localidad">
                                @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Contrato y funciÃ³n</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo de contrato <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_contrato') is-invalid @enderror"
                                        wire:model="tipo_contrato">
                                    @foreach ($tiposContrato as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_contrato') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">CategorÃ­a / Puesto</label>
                                <select class="form-select @error('categoria') is-invalid @enderror" wire:model="categoria">
                                    <option value="">Sin especificar</option>
                                    @foreach ($categorias as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
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
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha de ingreso <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_ingreso') is-invalid @enderror"
                                       wire:model="fecha_ingreso">
                                @error('fecha_ingreso') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha de egreso</label>
                                <input type="date" class="form-control @error('fecha_egreso') is-invalid @enderror"
                                       wire:model="fecha_egreso">
                                @error('fecha_egreso') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sueldo / Jornal base ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control font-monospace @error('sueldo_base') is-invalid @enderror"
                                           wire:model="sueldo_base" placeholder="0.00">
                                    @error('sueldo_base') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            @if ($modoEdicion)
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-emp-activo">
                                    <label class="form-check-label fw-semibold" for="switch-emp-activo">Empleado activo</label>
                                </div>
                            </div>
                            @endif
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Datos bancarios</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">CBU</label>
                                <input type="text" class="form-control font-monospace @error('cbu') is-invalid @enderror"
                                       wire:model="cbu" placeholder="22 dÃ­gitos" maxlength="22">
                                @error('cbu') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Banco</label>
                                <input type="text" class="form-control @error('banco') is-invalid @enderror"
                                       wire:model="banco" placeholder="Nombre del banco">
                                @error('banco') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <button type="submit" form="form-personal" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar empleado' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
