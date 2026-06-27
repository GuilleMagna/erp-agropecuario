<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Cuentas</h5>
            <small class="text-muted">Bancos, cajas y tarjetas de la empresa</small>
        </div>
        @can('finanzas.cuentas.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nueva cuenta
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Nombre, banco, nÃºmeroâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        @foreach ($tipos as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroActiva">
                        <option value="">Todas</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <span class="text-muted small">{{ $cuentas->total() }} cuentas</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards de cuentas --}}
    <div class="row g-3 mb-4">
        @forelse ($cuentas as $cuenta)
        @php
            $saldoActual = (float) $cuenta->saldo_inicial
                         + (float) ($cuenta->total_ingresos ?? 0)
                         - (float) ($cuenta->total_egresos ?? 0);
            $badgeTipo = [
                'banco'   => 'bg-primary-subtle text-primary',
                'caja'    => 'bg-success-subtle text-success',
                'tarjeta' => 'bg-info-subtle text-info-emphasis',
                'credito' => 'bg-warning-subtle text-warning-emphasis',
                'otro'    => 'bg-secondary-subtle text-secondary',
            ][$cuenta->tipo] ?? 'bg-secondary-subtle text-secondary';
            $iconoTipo = [
                'banco'   => 'bank',
                'caja'    => 'cash-stack',
                'tarjeta' => 'credit-card',
                'credito' => 'credit-card-2-front',
                'otro'    => 'wallet2',
            ][$cuenta->tipo] ?? 'wallet2';
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 {{ !$cuenta->activa ? 'opacity-50' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                 style="width:40px;height:40px;background:rgba(22,50,79,.08);">
                                <i class="bi bi-{{ $iconoTipo }} text-primary fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ $cuenta->nombre }}</div>
                                <span class="badge rounded-pill {{ $badgeTipo }} small">{{ $cuenta->tipo_label }}</span>
                            </div>
                        </div>
                        @can('finanzas.cuentas.gestionar')
                        <button class="btn btn-sm btn-outline-secondary"
                                wire:click="abrirModalEditar('{{ $cuenta->id }}')"
                                wire:loading.attr="disabled" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcan
                    </div>

                    @if ($cuenta->banco || $cuenta->numero_cuenta)
                    <div class="text-muted small mb-2">
                        @if ($cuenta->banco) {{ $cuenta->banco }} @endif
                        @if ($cuenta->banco && $cuenta->numero_cuenta) Â· @endif
                        @if ($cuenta->numero_cuenta) <span class="font-monospace">{{ $cuenta->numero_cuenta }}</span> @endif
                    </div>
                    @endif

                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Saldo inicial</small>
                            <small class="font-monospace">
                                {{ $cuenta->moneda === 'USD' ? 'U$S' : '$' }}
                                {{ number_format((float) $cuenta->saldo_inicial, 2, ',', '.') }}
                            </small>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-success">+ Ingresos</small>
                            <small class="text-success font-monospace">
                                {{ number_format((float)($cuenta->total_ingresos ?? 0), 2, ',', '.') }}
                            </small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-danger">âˆ’ Egresos</small>
                            <small class="text-danger font-monospace">
                                {{ number_format((float)($cuenta->total_egresos ?? 0), 2, ',', '.') }}
                            </small>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Saldo actual</span>
                            <span class="fs-5 {{ $saldoActual >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $cuenta->moneda === 'USD' ? 'U$S' : '$' }}
                                {{ number_format($saldoActual, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
                @if (!$cuenta->activa)
                <div class="card-footer bg-light border-0 py-1 text-center">
                    <small class="text-muted">Cuenta inactiva</small>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-bank display-6 d-block mb-2 opacity-25"></i>
                    No se encontraron cuentas.
                </div>
            </div>
        </div>
        @endforelse
    </div>

    @if ($cuentas->hasPages())
    <div class="d-flex justify-content-center">
        {{ $cuentas->links() }}
    </div>
    @endif

    {{-- Modal --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-bank me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar cuenta' : 'Nueva cuenta' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-cuenta">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nombre de la cuenta <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre"
                                       placeholder="Ej: Cuenta Corriente BNA, Caja Chica">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo') is-invalid @enderror" wire:model="tipo">
                                    @foreach ($tipos as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Moneda <span class="text-danger">*</span></label>
                                <select class="form-select @error('moneda') is-invalid @enderror" wire:model="moneda">
                                    @foreach ($monedas as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('moneda') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Banco / Entidad</label>
                                <input type="text" class="form-control @error('banco') is-invalid @enderror"
                                       wire:model="banco" placeholder="Ej: Banco NaciÃ³n Argentina">
                                @error('banco') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">NÂ° de cuenta / CBU</label>
                                <input type="text" class="form-control font-monospace @error('numero_cuenta') is-invalid @enderror"
                                       wire:model="numero_cuenta" placeholder="0000000000">
                                @error('numero_cuenta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Saldo inicial</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01"
                                           class="form-control @error('saldo_inicial') is-invalid @enderror"
                                           wire:model="saldo_inicial" placeholder="0.00">
                                    @error('saldo_inicial') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-text">Saldo al momento de crear la cuenta en el sistema.</div>
                            </div>
                            @if ($modoEdicion)
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           wire:model="activa" id="switch-cuenta-activa">
                                    <label class="form-check-label fw-semibold" for="switch-cuenta-activa">
                                        Cuenta activa
                                    </label>
                                </div>
                            </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"
                                          placeholder="Notas internas sobre esta cuentaâ€¦"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-cuenta" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear cuenta' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
