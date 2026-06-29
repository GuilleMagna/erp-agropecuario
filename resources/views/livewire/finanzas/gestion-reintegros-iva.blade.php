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
            <h5 class="mb-0 fw-bold text-dark">Reintegros IVA</h5>
            <small class="text-muted">Solicitudes de reintegro de crédito fiscal</small>
        </div>
        @can('finanzas.reintegros.gestionar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Nuevo reintegro
        </button>
        @endcan
    </div>

    {{-- Cards resumen --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Pendiente</div>
                            <div class="fs-4 fw-bold text-warning">
                                ${{ number_format($totalPendiente, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-hourglass-split text-warning fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">En trámite</div>
                            <div class="fs-4 fw-bold text-info">
                                ${{ number_format($totalPresentado, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="bg-info-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-send text-info fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Acreditado</div>
                            <div class="fs-4 fw-bold text-success">
                                ${{ number_format($totalAcreditado, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="bg-success-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-check-circle text-success fs-5"></i>
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
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Estado</label>
                    <select class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos</option>
                        @foreach ($estados as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Período</label>
                    <select class="form-select" wire:model.live="filtroPeriodo">
                        <option value="">Todos los períodos</option>
                        @foreach ($periodosFiscales as $pf)
                            <option value="{{ $pf->periodo }}">{{ $pf->periodo_formateado }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <span class="text-muted small">{{ $reintegros->total() }} reintegro(s)</span>
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
                        <th class="text-end">Importe</th>
                        <th>Fecha presentación</th>
                        <th>Fecha acreditación</th>
                        <th>Estado</th>
                        <th>Expediente</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reintegros as $r)
                    @php
                        $badgeEstado = [
                            'pendiente'   => 'bg-warning-subtle text-warning-emphasis',
                            'presentado'  => 'bg-info-subtle text-info-emphasis',
                            'acreditado'  => 'bg-success-subtle text-success',
                            'rechazado'   => 'bg-danger-subtle text-danger',
                        ][$r->estado] ?? 'bg-secondary-subtle text-secondary';
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold font-monospace">{{ $r->periodo }}</div>
                            @if ($r->periodoFiscal)
                                <small class="text-muted">{{ $r->periodoFiscal->periodo_formateado }}</small>
                            @endif
                        </td>
                        <td class="text-end fw-bold">
                            ${{ number_format((float) $r->importe, 2, ',', '.') }}
                        </td>
                        <td>
                            {{ $r->fecha_presentacion?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td>
                            {{ $r->fecha_acreditacion?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $badgeEstado }}">{{ $estados[$r->estado] ?? $r->estado }}</span>
                        </td>
                        <td>
                            <small class="font-monospace text-muted">{{ $r->numero_expediente ?? '—' }}</small>
                        </td>
                        <td class="pe-4 text-end text-nowrap">
                            @can('finanzas.reintegros.gestionar')
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                    wire:click="abrirModalEditar('{{ $r->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                    wire:click="eliminar('{{ $r->id }}')"
                                    wire:confirm="¿Eliminar este reintegro? Esta acción no se puede deshacer."
                                    wire:loading.attr="disabled" title="Eliminar">
                                <i class="bi bi-trash3"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-arrow-return-left display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron reintegros de IVA.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reintegros->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $reintegros->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear/editar reintegro --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-return-left me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar reintegro IVA' : 'Registrar reintegro IVA' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-reintegro">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Período Fiscal asociado</label>
                                <select class="form-select @error('id_periodo_fiscal') is-invalid @enderror"
                                        wire:model="id_periodo_fiscal">
                                    <option value="">Sin período fiscal asociado</option>
                                    @foreach ($periodosFiscales as $pf)
                                        <option value="{{ $pf->id }}">{{ $pf->periodo }} — {{ $pf->periodo_formateado }}</option>
                                    @endforeach
                                </select>
                                @error('id_periodo_fiscal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Si selecciona un período, el campo "Período" se puede dejar como referencia.</div>
                            </div>
                            @if ($ivaCredito !== null)
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0 small">
                                    <div class="row g-2 text-center">
                                        <div class="col-4">
                                            <div class="text-muted">IVA crédito (compras)</div>
                                            <div class="fw-bold">${{ number_format($ivaCredito, 2, ',', '.') }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">IVA débito (ventas est.)</div>
                                            <div class="fw-bold">${{ number_format($ivaDebito, 2, ',', '.') }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">Saldo a favor</div>
                                            <div class="fw-bold {{ $saldoFavor > 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($saldoFavor, 2, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                    @if ($saldoFavor <= 0)
                                        <div class="text-center text-warning mt-1">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            No hay saldo a favor en este período.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3">
                                <label class="form-label fw-semibold">Período <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control font-monospace @error('periodo') is-invalid @enderror"
                                       wire:model="periodo"
                                       placeholder="YYYY-MM"
                                       maxlength="7">
                                @error('periodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Importe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01"
                                           class="form-control @error('importe') is-invalid @enderror"
                                           wire:model="importe"
                                           placeholder="0.00">
                                    @error('importe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
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
                                <label class="form-label fw-semibold">Fecha presentación</label>
                                <input type="date"
                                       class="form-control @error('fecha_presentacion') is-invalid @enderror"
                                       wire:model="fecha_presentacion">
                                @error('fecha_presentacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha acreditación</label>
                                <input type="date"
                                       class="form-control @error('fecha_acreditacion') is-invalid @enderror"
                                       wire:model="fecha_acreditacion">
                                @error('fecha_acreditacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">N° Expediente</label>
                                <input type="text"
                                       class="form-control @error('numero_expediente') is-invalid @enderror"
                                       wire:model="numero_expediente"
                                       placeholder="EXP-2026-000001">
                                @error('numero_expediente') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <button type="submit" form="form-reintegro" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar reintegro' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
