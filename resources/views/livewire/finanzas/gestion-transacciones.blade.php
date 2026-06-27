<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">Transacciones</h5>
            <small class="text-muted">Ingresos y egresos de todas las cuentas</small>
        </div>
        @can('finanzas.transacciones.crear')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar
        </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">BÃºsqueda</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0"
                               placeholder="Concepto, comprobanteâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Cuenta</label>
                    <select class="form-select" wire:model.live="filtroCuenta">
                        <option value="">Todas</option>
                        @foreach ($cuentasOpciones as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo</label>
                    <select class="form-select" wire:model.live="filtroTipo">
                        <option value="">Todos</option>
                        <option value="ingreso">Ingreso</option>
                        <option value="egreso">Egreso</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">CategorÃ­a</label>
                    <select class="form-select" wire:model.live="filtroCategoria">
                        <option value="">Todas</option>
                        <optgroup label="Ingresos">
                            @foreach ($categoriasIngreso as $val => $etq)
                                <option value="{{ $val }}">{{ $etq }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Egresos">
                            @foreach ($categoriasEgreso as $val => $etq)
                                <option value="{{ $val }}">{{ $etq }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Desde</label>
                    <input type="date" class="form-control" wire:model.live="filtroFechaDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                    <input type="date" class="form-control" wire:model.live="filtroFechaHasta">
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
                        <th class="ps-4">Fecha</th>
                        <th>Concepto</th>
                        <th>CategorÃ­a</th>
                        <th>Cuenta</th>
                        <th>Establecimiento</th>
                        <th>Tipo</th>
                        <th class="text-end pe-4">Importe</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transacciones as $t)
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $t->fecha->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $t->concepto }}</div>
                            @if ($t->numero_comprobante)
                                <small class="text-muted font-monospace">{{ $t->numero_comprobante }}</small>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $t->categoria_label }}</small></td>
                        <td>
                            <small>{{ $t->cuenta->nombre ?? 'â€”' }}</small>
                            @if ($t->cuenta)
                                <br><small class="text-muted" style="font-size:.7rem;">{{ $t->cuenta->moneda }}</small>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $t->establecimiento->nombre ?? 'â€”' }}</small></td>
                        <td>
                            @if ($t->tipo === 'ingreso')
                                <span class="badge rounded-pill bg-success-subtle text-success">Ingreso</span>
                            @else
                                <span class="badge rounded-pill bg-danger-subtle text-danger">Egreso</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <span class="fw-semibold {{ $t->tipo === 'ingreso' ? 'text-success' : 'text-danger' }}">
                                {{ $t->tipo === 'ingreso' ? '+' : 'âˆ’' }}
                                ${{ number_format((float) $t->importe, 2, ',', '.') }}
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            @can('finanzas.transacciones.editar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $t->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-journal-text display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron transacciones.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transacciones->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $transacciones->links() }}
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
                        <i class="bi bi-journal-plus me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar transacciÃ³n' : 'Registrar transacciÃ³n' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-transaccion">

                        {{-- Tipo selector prominente --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block mb-2">Tipo de movimiento <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" wire:model.live="tipo"
                                       value="ingreso" id="tipo-ingreso" autocomplete="off">
                                <label class="btn btn-outline-success" for="tipo-ingreso">
                                    <i class="bi bi-arrow-down-circle me-1"></i> Ingreso
                                </label>
                                <input type="radio" class="btn-check" wire:model.live="tipo"
                                       value="egreso" id="tipo-egreso" autocomplete="off">
                                <label class="btn btn-outline-danger" for="tipo-egreso">
                                    <i class="bi bi-arrow-up-circle me-1"></i> Egreso
                                </label>
                            </div>
                            @error('tipo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Detalle</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cuenta <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_cuenta') is-invalid @enderror"
                                        wire:model="id_cuenta">
                                    <option value="">Seleccionar cuentaâ€¦</option>
                                    @foreach ($cuentasOpciones as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->moneda }})</option>
                                    @endforeach
                                </select>
                                @error('id_cuenta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CategorÃ­a <span class="text-danger">*</span></label>
                                <select class="form-select @error('categoria') is-invalid @enderror"
                                        wire:model="categoria">
                                    <option value="">Seleccionar categorÃ­aâ€¦</option>
                                    @foreach ($categoriasFormulario as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Concepto <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('concepto') is-invalid @enderror"
                                       wire:model="concepto"
                                       placeholder="DescripciÃ³n del movimiento">
                                @error('concepto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Importe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01"
                                           class="form-control @error('importe') is-invalid @enderror"
                                           wire:model="importe" placeholder="0.00">
                                    @error('importe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date"
                                       class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">NÂ° comprobante</label>
                                <input type="text"
                                       class="form-control font-monospace @error('numero_comprobante') is-invalid @enderror"
                                       wire:model="numero_comprobante"
                                       placeholder="Factura, reciboâ€¦">
                                @error('numero_comprobante') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Adicional (opcional)</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
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
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"
                                          placeholder="Notas adicionalesâ€¦"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-transaccion"
                            class="btn {{ $tipo === 'ingreso' ? 'btn-success' : 'btn-danger' }}"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar">
                            <i class="bi bi-{{ $tipo === 'ingreso' ? 'arrow-down-circle' : 'arrow-up-circle' }} me-1"></i>
                        </span>
                        {{ $modoEdicion ? 'Guardar cambios' : ($tipo === 'ingreso' ? 'Registrar ingreso' : 'Registrar egreso') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
