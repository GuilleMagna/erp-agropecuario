<div>

    {{-- Mensajes flash --}}
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

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Establecimientos</h5>
            <small class="text-muted">Campos y propiedades de la empresa</small>
        </div>
        @can('admin.establecimientos.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo establecimiento
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted mb-1">Búsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text"
                               class="form-control border-start-0 ps-0"
                               placeholder="Nombre, localidad, provincia…"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Provincia</label>
                    <select class="form-select" wire:model.live="filtroProvincia">
                        <option value="">Todas las provincias</option>
                        @foreach ($provincias as $prov)
                            <option value="{{ $prov }}">{{ $prov }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <span class="text-muted small">
                        {{ $establecimientos->total() }} {{ Str::plural('establecimiento', $establecimientos->total()) }}
                    </span>
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
                        <th class="ps-4">Establecimiento</th>
                        <th>Ubicación</th>
                        <th>Superficie</th>
                        <th>Tenencia</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($establecimientos as $est)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $est->nombre }}</div>
                            @if ($est->partida_catastral)
                                <small class="text-muted">Catastro: {{ $est->partida_catastral }}</small>
                            @endif
                        </td>
                        <td>
                            @if ($est->provincia || $est->localidad)
                                <div>{{ $est->localidad ?? '—' }}</div>
                                <small class="text-muted">{{ $est->provincia ?? '' }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($est->superficie_total_ha !== null)
                                <span class="fw-semibold">{{ number_format($est->superficie_total_ha, 1) }} ha</span>
                                @if ($est->superficie_agricola_ha || $est->superficie_ganadera_ha)
                                    <br>
                                    <small class="text-muted">
                                        @if ($est->superficie_agricola_ha)
                                            <i class="bi bi-flower1"></i> {{ number_format($est->superficie_agricola_ha, 0) }}
                                        @endif
                                        @if ($est->superficie_ganadera_ha)
                                            <i class="bi bi-heart-pulse ms-1"></i> {{ number_format($est->superficie_ganadera_ha, 0) }}
                                        @endif
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeTenencia = [
                                    'propio'    => 'bg-primary-subtle text-primary',
                                    'arrendado' => 'bg-warning-subtle text-warning-emphasis',
                                    'mixto'     => 'bg-purple-subtle text-purple',
                                    'usufructo' => 'bg-info-subtle text-info-emphasis',
                                ][$est->tipo_tenencia] ?? 'bg-secondary-subtle text-secondary';
                            @endphp
                            <span class="badge rounded-pill {{ $badgeTenencia }}">
                                {{ $tiposTenencia[$est->tipo_tenencia] ?? $est->tipo_tenencia }}
                            </span>
                        </td>
                        <td>
                            @if ($est->responsable)
                                <div>{{ $est->responsable->nombre_completo }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($est->activo)
                                <span class="badge rounded-pill bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('admin.establecimientos.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $est->id }}')"
                                    wire:loading.attr="disabled"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm {{ $est->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    wire:click="toggleActivo('{{ $est->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:confirm="{{ $est->activo ? '¿Desactivar este establecimiento?' : '¿Activar este establecimiento?' }}"
                                    title="{{ $est->activo ? 'Desactivar' : 'Activar' }}">
                                <i class="bi bi-{{ $est->activo ? 'toggle-on' : 'toggle-off' }}"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-geo-alt display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron establecimientos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($establecimientos->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $establecimientos->links('livewire::pagination.bootstrap') }}
        </div>
        @endif
    </div>

    {{-- Modal crear/editar --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar establecimiento' : 'Nuevo establecimiento' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>

                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-est">

                        {{-- Datos básicos --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Datos del establecimiento
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre" placeholder="Ej: La Primavera">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Provincia</label>
                                <select class="form-select @error('provincia') is-invalid @enderror"
                                        wire:model="provincia">
                                    <option value="">Sin especificar</option>
                                    @foreach ($provinciasLista as $prov)
                                        <option value="{{ $prov }}">{{ $prov }}</option>
                                    @endforeach
                                </select>
                                @error('provincia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Partido / Departamento</label>
                                <input type="text"
                                       class="form-control @error('partido_departamento') is-invalid @enderror"
                                       wire:model="partido_departamento" placeholder="Ej: General Alvear">
                                @error('partido_departamento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Localidad</label>
                                <input type="text" class="form-control @error('localidad') is-invalid @enderror"
                                       wire:model="localidad" placeholder="Ej: Médanos">
                                @error('localidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Ubicación --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Ubicación geográfica
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Latitud</label>
                                <input type="number" step="any"
                                       class="form-control @error('latitud') is-invalid @enderror"
                                       wire:model="latitud" placeholder="-38.123456">
                                @error('latitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longitud</label>
                                <input type="number" step="any"
                                       class="form-control @error('longitud') is-invalid @enderror"
                                       wire:model="longitud" placeholder="-62.654321">
                                @error('longitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Superficies --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Superficies (hectáreas)
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('superficie_total_ha') is-invalid @enderror"
                                           wire:model="superficie_total_ha" placeholder="0.00">
                                    <span class="input-group-text">ha</span>
                                    @error('superficie_total_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-flower1 text-success me-1"></i>Agrícola
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('superficie_agricola_ha') is-invalid @enderror"
                                           wire:model="superficie_agricola_ha" placeholder="0.00">
                                    <span class="input-group-text">ha</span>
                                    @error('superficie_agricola_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-heart-pulse text-danger me-1"></i>Ganadera
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('superficie_ganadera_ha') is-invalid @enderror"
                                           wire:model="superficie_ganadera_ha" placeholder="0.00">
                                    <span class="input-group-text">ha</span>
                                    @error('superficie_ganadera_ha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Configuración legal/admin --}}
                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">
                            Información legal y administración
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo de tenencia <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_tenencia') is-invalid @enderror"
                                        wire:model="tipo_tenencia">
                                    @foreach ($tiposTenencia as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_tenencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Partida catastral</label>
                                <input type="text" class="form-control @error('partida_catastral') is-invalid @enderror"
                                       wire:model="partida_catastral" placeholder="Ej: 123-456-789">
                                @error('partida_catastral') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Responsable</label>
                                <select class="form-select @error('responsable_id') is-invalid @enderror"
                                        wire:model="responsable_id">
                                    <option value="">Sin responsable</option>
                                    @foreach ($usuariosOpciones as $usuario)
                                        <option value="{{ $usuario->id }}">{{ $usuario->nombre_completo }}</option>
                                    @endforeach
                                </select>
                                @error('responsable_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if ($modoEdicion)
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activo" id="switch-activo">
                                    <label class="form-check-label fw-semibold" for="switch-activo">
                                        Establecimiento activo
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>

                    </form>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">
                        Cancelar
                    </button>
                    <button type="submit" form="form-est" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="bi bi-check-lg me-1"></i>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear establecimiento' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
