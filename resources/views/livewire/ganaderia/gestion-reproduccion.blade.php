<div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-bold text-dark">ReproducciÃ³n</h5>
            <small class="text-muted">Servicios, diagnÃ³sticos de preÃ±ez, partos y destetes</small>
        </div>
        @can('ganaderia.reproduccion.registrar')
        <button class="btn btn-primary" wire:click="abrirModalCrear" wire:loading.attr="disabled">
            <i class="bi bi-plus-lg me-1"></i> Registrar evento
        </button>
        @endcan
    </div>

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
                               placeholder="Caravana hembra / toroâ€¦"
                               wire:model.live.debounce.300ms="busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                    <select class="form-select" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Tipo de evento</label>
                    <select class="form-select" wire:model.live="filtroTipoEvento">
                        <option value="">Todos</option>
                        @foreach ($tiposEvento as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Resultado</label>
                    <select class="form-select" wire:model.live="filtroResultado">
                        <option value="">Todos</option>
                        @foreach ($resultados as $val => $etq)
                            <option value="{{ $val }}">{{ $etq }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <span class="text-muted small">{{ $eventos->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Evento</th>
                        <th>Establecimiento</th>
                        <th>Hembra</th>
                        <th>Toro</th>
                        <th>Fecha</th>
                        <th>Resultado</th>
                        <th class="pe-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                    @php
                        $badgeResultado = [
                            'prenada'       => 'bg-success-subtle text-success',
                            'vacia'         => 'bg-danger-subtle text-danger',
                            'parto_simple'  => 'bg-primary-subtle text-primary',
                            'parto_gemelar' => 'bg-info-subtle text-info-emphasis',
                            'aborto'        => 'bg-warning-subtle text-warning-emphasis',
                        ][$evento->resultado] ?? null;
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <span class="fw-semibold">{{ $evento->tipo_label }}</span>
                        </td>
                        <td>{{ $evento->establecimiento->nombre ?? 'â€”' }}</td>
                        <td>
                            @if ($evento->animal)
                                <span class="font-monospace">{{ $evento->animal->caravana ?? 'â€”' }}</span>
                                <small class="text-muted d-block">{{ $evento->animal->categoria_label }}</small>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td>{{ $evento->toro_caravana ?? 'â€”' }}</td>
                        <td>{{ $evento->fecha->format('d/m/Y') }}</td>
                        <td>
                            @if ($evento->resultado && $badgeResultado)
                                <span class="badge rounded-pill {{ $badgeResultado }}">{{ $evento->resultado_label }}</span>
                            @else
                                <span class="text-muted">â€”</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            @can('ganaderia.reproduccion.registrar')
                            <button class="btn btn-sm btn-outline-secondary"
                                    wire:click="abrirModalEditar('{{ $evento->id }}')"
                                    wire:loading.attr="disabled" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-gender-ambiguous display-6 d-block mb-2 opacity-25"></i>
                            No se encontraron eventos reproductivos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($eventos->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $eventos->links() }}
        </div>
        @endif
    </div>

    @if ($modalAbierto)
    <div class="modal fade show d-block" wire:ignore.self tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-gender-ambiguous me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar evento reproductivo' : 'Registrar evento reproductivo' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form wire:submit="guardar" id="form-reproduccion">

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Contexto</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Establecimiento <span class="text-danger">*</span></label>
                                <select class="form-select @error('id_establecimiento') is-invalid @enderror"
                                        wire:model.live="id_establecimiento">
                                    <option value="">Seleccionarâ€¦</option>
                                    @foreach ($establecimientos as $est)
                                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_establecimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Hembra <small class="text-muted fw-normal">(opcional)</small></label>
                                <select class="form-select @error('id_animal') is-invalid @enderror" wire:model="id_animal">
                                    <option value="">Sin identificar individualmente</option>
                                    @foreach ($hembrasOpciones as $h)
                                        <option value="{{ $h->id }}">
                                            {{ $h->caravana ?? '(sin caravana)' }}
                                            â€” {{ \App\Models\Animal::CATEGORIAS[$h->categoria] ?? $h->categoria }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_animal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase fw-bold small mb-3 border-bottom pb-2">Detalle del evento</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo de evento <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_evento') is-invalid @enderror" wire:model="tipo_evento">
                                    @foreach ($tiposEvento as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_evento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror" wire:model="fecha">
                                @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Resultado</label>
                                <select class="form-select @error('resultado') is-invalid @enderror" wire:model="resultado">
                                    <option value="">Sin resultado</option>
                                    @foreach ($resultados as $val => $etq)
                                        <option value="{{ $val }}">{{ $etq }}</option>
                                    @endforeach
                                </select>
                                @error('resultado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Toro / Caravana</label>
                                <input type="text" class="form-control font-monospace @error('toro_caravana') is-invalid @enderror"
                                       wire:model="toro_caravana" placeholder="NÂ° caravana">
                                @error('toro_caravana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                          wire:model="observaciones" rows="2"></textarea>
                                @error('observaciones') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" wire:click="cerrarModal">Cancelar</button>
                    <button type="submit" form="form-reproduccion" class="btn btn-primary"
                            wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading wire:target="guardar"><span class="spinner-border spinner-border-sm me-1"></span></span>
                        <span wire:loading.remove wire:target="guardar"><i class="bi bi-check-lg me-1"></i></span>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Registrar evento' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

</div>
