<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0 small">
            Gestioná las empresas/personas jurídicas del sistema y sus credenciales ARCA para sincronización automática de comprobantes.
        </p>
        <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
            <i class="bi bi-plus-circle me-1"></i> Nueva empresa
        </button>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Razón social</th>
                        <th>CUIT</th>
                        <th>Condición fiscal</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">ARCA</th>
                        <th class="text-center">Login ARCA</th>
                        <th class="text-center">Clave</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empresas as $emp)
                        <tr>
                            <td class="fw-semibold">{{ $emp->razon_social }}</td>
                            <td class="font-monospace small">{{ $emp->cuit }}</td>
                            <td class="small text-muted">
                                {{ \App\Livewire\Admin\GestionEmpresas::CONDICIONES_FISCALES[$emp->condicion_fiscal] ?? $emp->condicion_fiscal }}
                            </td>
                            <td class="text-center">
                                <button wire:click="toggleActiva('{{ $emp->id }}')"
                                        class="btn btn-sm btn-link p-0 border-0"
                                        title="{{ $emp->activa ? 'Desactivar' : 'Activar' }}">
                                    <span class="badge rounded-pill {{ $emp->activa ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $emp->activa ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </button>
                            </td>
                            <td class="text-center">
                                <button wire:click="toggleArca('{{ $emp->id }}')"
                                        class="btn btn-sm btn-link p-0 border-0"
                                        title="{{ $emp->arca_activo ? 'Desactivar sync ARCA' : 'Activar sync ARCA' }}">
                                    @if ($emp->arca_activo)
                                        <span class="badge rounded-pill bg-primary">
                                            <i class="bi bi-cloud-check me-1"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-secondary border">
                                            <i class="bi bi-cloud-slash me-1"></i>Inactivo
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="text-center font-monospace small">
                                {{ $emp->arca_cuit_login ?? '—' }}
                            </td>
                            <td class="text-center">
                                @if ($emp->arca_clave_fiscal)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-lock-fill me-1"></i>Configurada
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary"
                                        wire:click="abrirModalEditar('{{ $emp->id }}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-building fs-2 d-block mb-2 opacity-25"></i>
                                No hay empresas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal crear / editar --}}
    @if ($modalAbierto)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-building me-2 text-primary"></i>
                            {{ $modoEdicion ? 'Editar empresa' : 'Nueva empresa' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                    </div>
                    <div class="modal-body">

                        {{-- Datos fiscales --}}
                        <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                            <i class="bi bi-file-text me-1"></i>Datos fiscales
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-9">
                                <label class="form-label small fw-semibold">Razón social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('razon_social') is-invalid @enderror"
                                       wire:model="razon_social" placeholder="Nombre o razón social completa">
                                @error('razon_social')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Orden</label>
                                <input type="number" min="0" class="form-control @error('orden') is-invalid @enderror"
                                       wire:model="orden">
                                @error('orden')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Posición en los reportes</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">CUIT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace @error('cuit') is-invalid @enderror"
                                       wire:model="cuit" placeholder="20-12345678-9">
                                @error('cuit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Condición fiscal <span class="text-danger">*</span></label>
                                <select class="form-select @error('condicion_fiscal') is-invalid @enderror"
                                        wire:model="condicion_fiscal">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($condicionesFiscales as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('condicion_fiscal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Moneda principal</label>
                                <select class="form-select" wire:model="moneda_default">
                                    <option value="ARS">ARS</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Domicilio fiscal</label>
                                <input type="text" class="form-control" wire:model="domicilio_fiscal"
                                       placeholder="Calle 1234, Ciudad, Provincia">
                            </div>
                        </div>

                        <hr>

                        {{-- Credenciales ARCA --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-semibold text-muted text-uppercase small mb-0">
                                <i class="bi bi-cloud-arrow-down me-1"></i>Credenciales ARCA / AFIP
                            </h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" wire:model="arca_activo" id="arcaActivo">
                                <label class="form-check-label small" for="arcaActivo">
                                    Sincronización activa
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-light border small py-2 mb-3">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            <strong>CUIT login</strong>: quien inicia sesión en AFIP (puede ser un representante).
                            <strong>CUIT representado</strong>: el CUIT de esta empresa a seleccionar en la pantalla "Elegí una persona".
                            Si son el mismo (acceso propio), ingresá el mismo valor en ambos campos.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">CUIT login AFIP</label>
                                <input type="text" class="form-control font-monospace @error('arca_cuit_login') is-invalid @enderror"
                                       wire:model="arca_cuit_login" placeholder="20-12345678-9">
                                @error('arca_cuit_login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">
                                    Clave fiscal
                                    @if ($modoEdicion)
                                        <span class="text-muted fw-normal">(vacío = no cambiar)</span>
                                    @endif
                                </label>
                                <input type="password" class="form-control @error('arca_clave_fiscal') is-invalid @enderror"
                                       wire:model="arca_clave_fiscal" autocomplete="new-password"
                                       placeholder="{{ $modoEdicion ? '••••••••' : 'Clave fiscal' }}">
                                @error('arca_clave_fiscal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">CUIT representado</label>
                                <input type="text" class="form-control font-monospace @error('arca_cuit_representado') is-invalid @enderror"
                                       wire:model="arca_cuit_representado" placeholder="20-12345678-9">
                                @error('arca_cuit_representado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">El CUIT de esta empresa en ARCA</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nombre representado</label>
                                <input type="text" class="form-control"
                                       wire:model="arca_nombre_representado" placeholder="Razón social en ARCA">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModal">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="guardar" wire:loading.attr="disabled">
                            <span wire:loading wire:target="guardar" class="spinner-border spinner-border-sm me-1"></span>
                            <i wire:loading.remove wire:target="guardar" class="bi bi-floppy me-1"></i>
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
