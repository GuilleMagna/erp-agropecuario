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
            <h5 class="mb-0 fw-bold text-dark">Períodos Fiscales</h5>
            <small class="text-muted">Gestión de períodos IVA mensual y DDJJ</small>
        </div>
        @can('finanzas.periodos.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo período
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 offset-md-4 text-end">
                    <span class="text-muted small">{{ $periodos->total() }} período(s)</span>
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
                        <th class="ps-4">Período</th>
                        <th>Estado</th>
                        <th class="text-end">IVA Crédito</th>
                        <th class="text-end">IVA Débito</th>
                        <th class="text-end">Saldo IVA</th>
                        <th class="text-center">Reintegros</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periodos as $pf)
                    @php
                        $badgeEstado = [
                            'abierto'    => 'bg-success-subtle text-success',
                            'cerrado'    => 'bg-secondary-subtle text-secondary',
                            'presentado' => 'bg-info-subtle text-info-emphasis',
                        ][$pf->estado] ?? 'bg-secondary-subtle text-secondary';

                        $iva     = $ivaData[$pf->id] ?? ['credito' => 0, 'debito' => 0, 'saldo' => 0];
                        $saldo   = $iva['saldo'];
                        $saldoClass = $saldo > 0 ? 'text-danger fw-bold' : ($saldo < 0 ? 'text-success fw-bold' : 'text-muted');
                        $saldoLabel = $saldo > 0 ? 'A pagar' : ($saldo < 0 ? 'A favor' : 'Neutro');
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $pf->periodo_formateado }}</div>
                            <small class="text-muted font-monospace">{{ $pf->periodo }}</small>
                            @if ($pf->numero_formulario)
                                <br><small class="text-muted">DDJJ: {{ $pf->numero_formulario }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">{{ $estados[$pf->estado] ?? $pf->estado }}</span>
                            @if ($pf->fecha_cierre)
                                <br><small class="text-muted">Cerrado: {{ $pf->fecha_cierre->format('d/m/Y') }}</small>
                            @endif
                            @if ($pf->fecha_presentacion)
                                <br><small class="text-muted">Presentado: {{ $pf->fecha_presentacion->format('d/m/Y') }}</small>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="text-muted">$</span> {{ number_format($iva['credito'], 2, ',', '.') }}
                        </td>
                        <td class="text-end">
                            <span class="text-muted">$</span> {{ number_format($iva['debito'], 2, ',', '.') }}
                        </td>
                        <td class="text-end">
                            <span class="{{ $saldoClass }}">
                                ${{ number_format(abs($saldo), 2, ',', '.') }}
                            </span>
                            <br><small class="{{ $saldoClass }}" style="font-size:.7rem;">{{ $saldoLabel }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $pf->reintegros_count }}</span>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('finanzas.periodos.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $pf->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>

                            @if ($pf->estado === 'abierto')
                            <button class="btn btn-sm btn-outline-warning me-1"
                                    wire:click="cerrarPeriodo('{{ $pf->id }}')"
                                    wire:confirm="¿Cerrar el período {{ $pf->periodo }}? Se registrará la fecha de hoy como fecha de cierre."
                                    wire:loading.attr="disabled" title="Cerrar período">
                                <i class="bi bi-lock"></i>
                            </button>
                            @endif

                            @if ($pf->estado === 'cerrado')
                            <button class="btn btn-sm btn-outline-success me-1"
                                    wire:click="reabrirPeriodo('{{ $pf->id }}')"
                                    wire:confirm="¿Reabrir el período {{ $pf->periodo }}?"
                                    wire:loading.attr="disabled" title="Reabrir período">
                                <i class="bi bi-unlock"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info me-1"
                                    wire:click="marcarPresentado('{{ $pf->id }}')"
                                    wire:confirm="¿Marcar como presentado el período {{ $pf->periodo }}?"
                                    wire:loading.attr="disabled" title="Marcar como presentado">
                                <i class="bi bi-send-check"></i>
                            </button>
                            @endif

                            @if ($pf->estado === 'abierto')
                            <button class="btn btn-sm btn-outline-danger"
                                    wire:click="eliminar('{{ $pf->id }}')"
                                    wire:confirm="¿Eliminar el período {{ $pf->periodo }}? Esta acción no se puede deshacer."
                                    wire:loading.attr="disabled" title="Eliminar">
                                <i class="bi bi-trash3"></i>
                            </button>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-calendar-check display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron períodos fiscales.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($periodos->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $periodos->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear/editar período --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar-check me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar período fiscal' : 'Nuevo período fiscal' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-periodo">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Período <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control font-monospace @error('periodo') is-invalid @enderror"
                                       wire:model="periodo"
                                       placeholder="YYYY-MM (ej: 2026-06)"
                                       maxlength="7">
                                @error('periodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Formato: YYYY-MM</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select @error('estado') is-invalid @enderror"
                                        wire:model="estado">
                                    @foreach ($estados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">N° Formulario DDJJ</label>
                                <input type="text"
                                       class="form-control @error('numero_formulario') is-invalid @enderror"
                                       wire:model="numero_formulario"
                                       placeholder="Nro. de declaración">
                                @error('numero_formulario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de cierre</label>
                                <input type="date"
                                       class="form-control @error('fecha_cierre') is-invalid @enderror"
                                       wire:model="fecha_cierre">
                                @error('fecha_cierre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha de presentación</label>
                                <input type="date"
                                       class="form-control @error('fecha_presentacion') is-invalid @enderror"
                                       wire:model="fecha_presentacion">
                                @error('fecha_presentacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted text-uppercase">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-periodo" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear período' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
