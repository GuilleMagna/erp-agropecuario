<div>

    {{-- Flash --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.300ms="busqueda"
                           placeholder="Buscar corral...">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos los establecimientos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filtroActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Deshabilitados</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    @can('feedlot.corrales.gestionar')
                    <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
                        <i class="bi bi-plus-lg me-1"></i> Nuevo corral
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Corral</th>
                        <th>Establecimiento</th>
                        <th class="text-center">Capacidad</th>
                        <th style="min-width:160px;">Ocupación</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($corrales as $corral)
                    @php
                        $ocupacion = (int)($corral->ocupacion_actual ?? 0);
                        $pct       = $corral->capacidad_cabezas > 0
                            ? min(100, round($ocupacion / $corral->capacidad_cabezas * 100))
                            : 0;
                        $barColor  = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                    @endphp
                    <tr class="{{ !$corral->activo ? 'opacity-50' : '' }}">
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $corral->nombre }}</div>
                            @if ($corral->codigo)
                                <small class="text-muted font-monospace">{{ $corral->codigo }}</small>
                            @endif
                            @if ($corral->superficie_m2)
                                <small class="text-muted ms-2">{{ number_format($corral->superficie_m2, 0, ',', '.') }} m²</small>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $corral->establecimiento?->nombre ?? '—' }}</td>
                        <td class="text-center font-monospace small fw-semibold">{{ number_format($corral->capacidad_cabezas, 0, ',', '.') }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar {{ $barColor }}" style="width:{{ $pct }}%"></div>
                                </div>
                                <small class="text-muted text-nowrap">{{ $ocupacion }}/{{ $corral->capacidad_cabezas }}</small>
                            </div>
                            <div style="font-size:.72rem;" class="text-muted">{{ $pct }}% de capacidad</div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill {{ $corral->activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $corral->activo ? 'Activo' : 'Deshabilitado' }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            @can('feedlot.corrales.gestionar')
                            <button class="btn btn-outline-primary btn-sm"
                                    wire:click="abrirModalEditar('{{ $corral->id }}')"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-{{ $corral->activo ? 'warning' : 'success' }} btn-sm ms-1"
                                    wire:click="toggleActivo('{{ $corral->id }}')"
                                    wire:confirm="{{ $corral->activo ? '¿Deshabilitar este corral?' : '¿Habilitar este corral?' }}"
                                    title="{{ $corral->activo ? 'Deshabilitar' : 'Habilitar' }}">
                                <i class="bi bi-{{ $corral->activo ? 'pause-circle' : 'play-circle' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-grid d-block fs-2 mb-2 opacity-50"></i>
                            No hay corrales registrados.
                            @can('feedlot.corrales.gestionar')
                            <a href="#" wire:click.prevent="abrirModalCrear" class="d-block small mt-1">Crear el primero</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($corrales->hasPages())
        <div class="card-footer bg-white border-top py-2 px-3">
            {{ $corrales->links() }}
        </div>
        @endif
    </div>

    {{-- ===== MODAL CREAR / EDITAR ===== --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-grid me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar corral' : 'Nuevo corral' }}
                    </h6>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted mb-1">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   wire:model="nombre" placeholder="Nombre del corral">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Código</label>
                            <input type="text" class="form-control font-monospace @error('codigo') is-invalid @enderror"
                                   wire:model="codigo" placeholder="C-01">
                            @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                            <select class="form-select @error('idEstablecimiento') is-invalid @enderror"
                                    wire:model="idEstablecimiento">
                                <option value="">— Sin asignar —</option>
                                @foreach ($establecimientos as $est)
                                    <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idEstablecimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Capacidad (cabezas) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('capacidadCabezas') is-invalid @enderror"
                                   wire:model="capacidadCabezas" min="1">
                            @error('capacidadCabezas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Superficie (m²)</label>
                            <input type="number" class="form-control @error('superficieM2') is-invalid @enderror"
                                   wire:model="superficieM2" step="0.01" min="0">
                            @error('superficieM2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1">Observaciones</label>
                            <textarea class="form-control" wire:model="observaciones" rows="2"
                                      placeholder="Condiciones del corral, tipo de piso, instalaciones..."></textarea>
                        </div>
                        @if ($modoEdicion)
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="activo" id="chkActivo">
                                <label class="form-check-label small" for="chkActivo">Corral activo / habilitado</label>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-top py-2 gap-2">
                    <button type="button" class="btn btn-light btn-sm" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="guardar" wire:loading.attr="disabled">
                        <span wire:loading wire:target="guardar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="guardar" class="bi bi-check-lg me-1"></i>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear corral' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
