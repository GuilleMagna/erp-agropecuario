<div>

    {{-- Flash --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4 py-2 small" role="alert">
        <i class="bi bi-x-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0 small">Gestioná los roles del sistema y sus permisos. Los roles marcados con <i class="bi bi-shield-fill text-primary"></i> son del sistema y no se pueden eliminar.</p>
        <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
            <i class="bi bi-plus-lg me-1"></i> Nuevo rol
        </button>
    </div>

    {{-- Tabla de roles --}}
    <div class="row g-3">
        @foreach ($roles as $role)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 {{ $role->es_sistema ? 'border-start border-primary border-2' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if ($role->es_sistema)
                                    <i class="bi bi-shield-fill text-primary" title="Rol del sistema"></i>
                                @else
                                    <i class="bi bi-person-gear text-secondary"></i>
                                @endif
                                <span class="fw-bold font-monospace">{{ $role->name }}</span>
                            </div>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="bi bi-key me-1"></i>{{ $role->permissions_count ?? $role->permissions->count() }} permisos</span>
                                <span><i class="bi bi-people me-1"></i>{{ $role->users_count }} usuario{{ $role->users_count !== 1 ? 's' : '' }}</span>
                            </div>

                            {{-- Preview de módulos con acceso --}}
                            @php
                                $modulos = $role->permissions->map(fn($p) => explode('.', $p->name)[0])->unique()->values();
                            @endphp
                            @if ($modulos->count() > 0)
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                @foreach ($modulos->take(6) as $mod)
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary" style="font-size:.7rem;">
                                        {{ $modulosLabel[$mod] ?? $mod }}
                                    </span>
                                @endforeach
                                @if ($modulos->count() > 6)
                                    <span class="badge rounded-pill bg-light text-muted" style="font-size:.7rem;">+{{ $modulos->count() - 6 }} más</span>
                                @endif
                            </div>
                            @else
                            <div class="mt-2">
                                <span class="text-muted" style="font-size:.78rem;">Sin permisos asignados</span>
                            </div>
                            @endif
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button class="btn btn-outline-primary btn-sm" wire:click="editarPermisos('{{ $role->id }}')"
                                    title="Editar permisos">
                                <i class="bi bi-key"></i>
                            </button>
                            @if (!$role->es_sistema)
                            <button class="btn btn-outline-danger btn-sm"
                                    wire:click="eliminarRol('{{ $role->id }}')"
                                    wire:confirm="¿Eliminar el rol '{{ $role->name }}'? Esta acción no se puede deshacer."
                                    title="Eliminar rol">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ===== MODAL: CREAR ROL ===== --}}
    @if ($modalCrearAbierto)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-plus-lg me-2 text-primary"></i>Nuevo rol personalizado</h6>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Nombre del rol <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace @error('nuevoRolNombre') is-invalid @enderror"
                               wire:model="nuevoRolNombre"
                               placeholder="ej: supervisor_campo"
                               wire:keydown.enter="crearRol">
                        @error('nuevoRolNombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text text-muted" style="font-size:.75rem;">Solo letras, números, guiones y guiones bajos.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 gap-2">
                    <button type="button" class="btn btn-light btn-sm" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="crearRol" wire:loading.attr="disabled">
                        <span wire:loading wire:target="crearRol" class="spinner-border spinner-border-sm me-1"></span>
                        Crear rol
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== MODAL: EDITAR PERMISOS ===== --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <div>
                        <h6 class="modal-title fw-bold mb-0">
                            <i class="bi bi-key me-2 text-primary"></i>Permisos del rol:
                            <span class="font-monospace text-primary">{{ $rolEditandoNombre }}</span>
                        </h6>
                        <small class="text-muted">{{ count($permisosSeleccionados) }} permisos seleccionados</small>
                    </div>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        @foreach ($permisosPorModulo as $modulo => $permisos)
                        <div class="col-md-4 border-bottom border-end p-3">
                            <div class="fw-semibold small mb-2 text-uppercase text-muted"
                                 style="font-size:.72rem; letter-spacing:.5px;">
                                {{ $modulosLabel[$modulo] ?? $modulo }}
                            </div>
                            @foreach ($permisos as $permiso)
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox"
                                       wire:model="permisosSeleccionados"
                                       value="{{ $permiso->name }}"
                                       id="perm_{{ $permiso->id }}">
                                <label class="form-check-label small" for="perm_{{ $permiso->id }}">
                                    @php
                                        // Extraer la acción del permiso (última parte) y mostrarla legible
                                        $partes = explode('.', $permiso->name);
                                        $recurso = count($partes) >= 2 ? $partes[1] : '';
                                        $accion  = count($partes) >= 3 ? $partes[2] : ($partes[1] ?? $permiso->name);
                                        $accionLabel = [
                                            'ver'         => 'Ver',
                                            'crear'       => 'Crear',
                                            'editar'      => 'Editar',
                                            'gestionar'   => 'Gestionar',
                                            'registrar'   => 'Registrar',
                                            'aprobar'     => 'Aprobar',
                                            'inactivar'   => 'Inactivar',
                                            'exportar'    => 'Exportar',
                                        ][$accion] ?? ucfirst($accion);
                                        $recursoLabel = str_replace(['_'], [' '], $recurso);
                                    @endphp
                                    <span class="text-dark">{{ $accionLabel }}</span>
                                    <span class="text-muted ms-1" style="font-size:.78rem;">{{ $recursoLabel }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-top py-2 gap-2">
                    <span class="text-muted small me-auto">
                        <i class="bi bi-key me-1"></i>{{ count($permisosSeleccionados) }} seleccionados
                    </span>
                    <button type="button" class="btn btn-light btn-sm" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="guardarPermisos" wire:loading.attr="disabled">
                        <span wire:loading wire:target="guardarPermisos" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="guardarPermisos" class="bi bi-check-lg me-1"></i>
                        Guardar permisos
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
