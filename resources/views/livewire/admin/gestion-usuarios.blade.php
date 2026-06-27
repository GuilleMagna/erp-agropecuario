<div>
    {{-- Mensajes flash --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h5 class="mb-0 fw-bold" style="color:#16324F">Usuarios del sistema</h5>
            <small class="text-muted">AdministrÃ¡ los usuarios y sus roles de acceso.</small>
        </div>
        @can('admin.usuarios.crear')
        <button class="btn btn-primary btn-sm px-3" wire:click="abrirModalCrear">
            <i class="bi bi-person-plus me-1"></i> Nuevo usuario
        </button>
        @endcan
    </div>

    {{-- Barra de filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               class="form-control form-control-sm border-start-0 ps-0"
                               placeholder="Buscar por nombre, apellido o emailâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filtroRol">
                        <option value="">Todos los roles</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}">
                                {{ ucwords(str_replace('_', ' ', $r->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" wire:model.live="filtroActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <span class="text-muted small">{{ $usuarios->total() }} usuario(s)</span>
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
                        <th class="ps-3 fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">Nombre</th>
                        <th class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">Email</th>
                        <th class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">TelÃ©fono</th>
                        <th class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">Rol</th>
                        <th class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">Estado</th>
                        <th class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">Ãšltimo acceso</th>
                        <th class="pe-3 text-end fw-semibold small text-uppercase text-muted" style="letter-spacing:.5px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    @php
                        $rolNombre = $usuario->roles->first()?->name ?? '';
                        $colores   = [
                            'administrador_sistema' => 'danger',
                            'gerente'               => 'primary',
                            'capataz'               => 'success',
                            'administrativo'        => 'info',
                            'asesor_tecnico'        => 'warning',
                            'auditor'               => 'secondary',
                        ];
                        $colorRol  = $colores[$rolNombre] ?? 'light';
                        $etiquetaRol = ucwords(str_replace('_', ' ', $rolNombre));
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $usuario->nombre_completo }}</div>
                        </td>
                        <td class="text-muted small">{{ $usuario->email }}</td>
                        <td class="text-muted small">{{ $usuario->telefono ?? 'â€”' }}</td>
                        <td>
                            @if($rolNombre)
                                <span class="badge bg-{{ $colorRol }} bg-opacity-90" style="font-size:.75rem">
                                    {{ $etiquetaRol }}
                                </span>
                            @else
                                <span class="text-muted small fst-italic">Sin rol</span>
                            @endif
                        </td>
                        <td>
                            @if($usuario->activo)
                                <span class="badge rounded-pill text-success border border-success" style="background:rgba(25,135,84,.1);font-size:.75rem">
                                    <i class="bi bi-check-circle-fill me-1"></i>Activo
                                </span>
                            @else
                                <span class="badge rounded-pill text-danger border border-danger" style="background:rgba(220,53,69,.1);font-size:.75rem">
                                    <i class="bi bi-x-circle-fill me-1"></i>Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $usuario->ultimo_acceso?->diffForHumans() ?? 'Nunca' }}
                        </td>
                        <td class="pe-3 text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @can('admin.usuarios.editar')
                                <button class="btn btn-sm btn-outline-secondary"
                                        wire:click="abrirModalEditar('{{ $usuario->id }}')"
                                        title="Editar usuario"
                                        wire:loading.attr="disabled">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endcan

                                @can('admin.usuarios.inactivar')
                                @if($usuario->id !== auth()->id())
                                <button class="btn btn-sm {{ $usuario->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        wire:click="toggleActivo('{{ $usuario->id }}')"
                                        wire:confirm="{{ $usuario->activo ? 'Â¿Inactivar este usuario? Ya no podrÃ¡ ingresar al sistema.' : 'Â¿Activar este usuario?' }}"
                                        title="{{ $usuario->activo ? 'Inactivar' : 'Activar' }}"
                                        wire:loading.attr="disabled">
                                    <i class="bi bi-person-{{ $usuario->activo ? 'slash' : 'check' }}"></i>
                                </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                            No hay usuarios que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
        <div class="card-footer bg-white border-top-0 py-2 px-3">
            {{ $usuarios->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear / editar --}}
    @if($modalAbierto)
    <div class="modal fade show" style="display:block;" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header border-0 pb-1">
                    <h5 class="modal-title fw-bold" style="color:#16324F">
                        <i class="bi bi-person-{{ $modoEdicion ? 'gear' : 'plus-fill' }} me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar usuario' : 'Nuevo usuario' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>

                <div class="modal-body pt-2">
                    <form wire:submit="guardar" id="form-usuario">
                        <div class="row g-3">

                            {{-- Nombre --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-sm @error('nombre') is-invalid @enderror"
                                       wire:model="nombre"
                                       placeholder="Juan">
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Apellido --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">
                                    Apellido <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-sm @error('apellido') is-invalid @enderror"
                                       wire:model="apellido"
                                       placeholder="GarcÃ­a">
                                @error('apellido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold small mb-1">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                       class="form-control form-control-sm @error('email') is-invalid @enderror"
                                       wire:model="email"
                                       placeholder="juan@empresa.com"
                                       autocomplete="off">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- TelÃ©fono --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">TelÃ©fono</label>
                                <input type="text"
                                       class="form-control form-control-sm @error('telefono') is-invalid @enderror"
                                       wire:model="telefono"
                                       placeholder="+54 9 11 1234-5678">
                                @error('telefono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Rol --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">
                                    Rol <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm @error('rol') is-invalid @enderror"
                                        wire:model="rol">
                                    <option value="">SeleccionÃ¡ un rolâ€¦</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->name }}">
                                            {{ ucwords(str_replace('_', ' ', $r->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('rol')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- ContraseÃ±a --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">
                                    ContraseÃ±a {{ $modoEdicion ? '' : '*' }}
                                </label>
                                @if($modoEdicion)
                                    <p class="text-muted mb-1" style="font-size:.72rem">
                                        DejÃ¡ vacÃ­o para no cambiar.
                                    </p>
                                @endif
                                <input type="password"
                                       class="form-control form-control-sm @error('password') is-invalid @enderror"
                                       wire:model="password"
                                       autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Confirmar contraseÃ±a --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">
                                    Confirmar contraseÃ±a {{ $modoEdicion ? '' : '*' }}
                                </label>
                                @if($modoEdicion)<p class="mb-1" style="font-size:.72rem">&nbsp;</p>@endif
                                <input type="password"
                                       class="form-control form-control-sm"
                                       wire:model="password_confirmation"
                                       autocomplete="new-password">
                            </div>

                            {{-- Activo (solo en ediciÃ³n) --}}
                            @if($modoEdicion)
                            <div class="col-12 pt-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="check-activo" wire:model="activo">
                                    <label class="form-check-label fw-semibold small" for="check-activo">
                                        Usuario activo
                                    </label>
                                </div>
                            </div>
                            @endif

                        </div>
                    </form>
                </div>

                <div class="modal-footer border-0 pt-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3"
                            wire:click="cerrarModal">
                        Cancelar
                    </button>
                    <button type="submit" form="form-usuario"
                            class="btn btn-primary btn-sm px-3"
                            wire:loading.attr="disabled"
                            wire:target="guardar">
                        <span wire:loading wire:target="guardar">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear usuario' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
