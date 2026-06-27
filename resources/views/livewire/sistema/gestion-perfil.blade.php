<div>

    <div class="d-flex align-items-center mb-4">
        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3 flex-shrink-0"
             style="width:56px;height:56px;">
            <span class="text-white fw-bold fs-5">{{ strtoupper(substr(auth()->user()->nombre,0,1) . substr(auth()->user()->apellido,0,1)) }}</span>
        </div>
        <div>
            <h5 class="fw-bold text-dark mb-0">{{ auth()->user()->nombre_completo }}</h5>
            <small class="text-muted">
                <span class="badge rounded-pill bg-primary-subtle text-primary">{{ auth()->user()->getRoleNames()->first() ?? 'Sin rol' }}</span>
                &nbsp;{{ auth()->user()->email }}
            </small>
        </div>
    </div>

    <div class="row g-4">

        {{-- Datos personales --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold py-3">
                    <i class="bi bi-person me-2 text-primary"></i>Datos personales
                </div>
                <div class="card-body">

                    @if (session('success_datos'))
                    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success_datos') }}
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Nombre</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                               wire:model="nombre" placeholder="Nombre">
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Apellido</label>
                        <input type="text" class="form-control @error('apellido') is-invalid @enderror"
                               wire:model="apellido" placeholder="Apellido">
                        @error('apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               wire:model="email" placeholder="correo@empresa.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Teléfono <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                               wire:model="telefono" placeholder="+54 9 11 0000-0000">
                        @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button class="btn btn-primary" wire:click="guardarDatos" wire:loading.attr="disabled">
                        <span wire:loading wire:target="guardarDatos" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="guardarDatos" class="bi bi-check-lg me-1"></i>
                        Guardar datos
                    </button>

                </div>
            </div>
        </div>

        {{-- Cambio de contraseña --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold py-3">
                    <i class="bi bi-shield-lock me-2 text-warning"></i>Cambiar contraseña
                </div>
                <div class="card-body">

                    @if (session('success_password'))
                    <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                        <i class="bi bi-check-circle me-1"></i>{{ session('success_password') }}
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Contraseña actual</label>
                        <input type="password" class="form-control @error('password_actual') is-invalid @enderror"
                               wire:model="password_actual" autocomplete="current-password">
                        @error('password_actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Nueva contraseña</label>
                        <input type="password" class="form-control @error('password_nuevo') is-invalid @enderror"
                               wire:model="password_nuevo" autocomplete="new-password">
                        @error('password_nuevo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text text-muted" style="font-size:.78rem;">Mínimo 8 caracteres.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted mb-1">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control @error('password_confirmacion') is-invalid @enderror"
                               wire:model="password_confirmacion" autocomplete="new-password">
                        @error('password_confirmacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button class="btn btn-warning" wire:click="cambiarPassword" wire:loading.attr="disabled">
                        <span wire:loading wire:target="cambiarPassword" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="cambiarPassword" class="bi bi-shield-check me-1"></i>
                        Actualizar contraseña
                    </button>

                </div>
            </div>
        </div>

        {{-- Información de la sesión (solo lectura) --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom fw-semibold py-3">
                    <i class="bi bi-info-circle me-2 text-secondary"></i>Información de cuenta
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Rol asignado</div>
                            <div class="fw-semibold">{{ auth()->user()->getRoleNames()->first() ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Empresa</div>
                            <div class="fw-semibold">{{ auth()->user()->empresa?->razon_social ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Último acceso</div>
                            <div class="fw-semibold font-monospace" style="font-size:.875rem;">
                                {{ auth()->user()->ultimo_acceso?->format('d/m/Y H:i') ?? 'Sin registro' }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Estado</div>
                            <span class="badge rounded-pill {{ auth()->user()->activo ? 'bg-success' : 'bg-danger' }}">
                                {{ auth()->user()->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
