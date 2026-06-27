<div>

    {{-- Flash --}}
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 py-2 small" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-0">
                    <div class="fs-2 fw-bold text-primary">{{ number_format($totalActivas, 0, ',', '.') }}</div>
                    <div class="small text-muted">Tropas activas</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-0">
                    <div class="fs-2 fw-bold text-success">{{ number_format($totalCabezasFeedlot, 0, ',', '.') }}</div>
                    <div class="small text-muted">Cabezas en feedlot</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="card-body py-0">
                    <div class="fs-2 fw-bold text-info">{{ number_format((float)$promediosDias, 0, ',', '.') }}</div>
                    <div class="small text-muted">Días prom. en feedlot</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filtroCorral">
                        <option value="">Todos los corrales</option>
                        @foreach ($corrales as $corral)
                            <option value="{{ $corral->id }}">{{ $corral->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filtroEstablecimiento">
                        <option value="">Todos los establecimientos</option>
                        @foreach ($establecimientos as $est)
                            <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" wire:model.live="filtroEstado">
                        <option value="">Todos los estados</option>
                        <option value="activa">Activa</option>
                        <option value="finalizada">Finalizada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    @can('feedlot.tropas.gestionar')
                    <button class="btn btn-primary btn-sm" wire:click="abrirModalCrear">
                        <i class="bi bi-plus-lg me-1"></i> Nueva tropa
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tropa</th>
                        <th>Corral</th>
                        <th class="text-center">Categoría</th>
                        <th class="text-center">Cabezas</th>
                        <th class="text-center">Días</th>
                        <th class="text-end">Peso entrada</th>
                        <th class="text-end">Gan./día</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tropas as $tropa)
                    @php
                        $estadoClase = match($tropa->estado) {
                            'activa'     => 'bg-success-subtle text-success',
                            'finalizada' => 'bg-primary-subtle text-primary',
                            'cancelada'  => 'bg-danger-subtle text-danger',
                            default      => 'bg-secondary-subtle text-secondary',
                        };
                        $catLabel = \App\Models\Tropa::CATEGORIAS[$tropa->categoria] ?? $tropa->categoria;
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $tropa->nombre }}</div>
                            @if ($tropa->establecimiento)
                                <small class="text-muted">{{ $tropa->establecimiento->nombre }}</small>
                            @endif
                        </td>
                        <td class="small">{{ $tropa->corral?->nombre ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill small">
                                {{ $catLabel }}
                            </span>
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($tropa->cantidad_cabezas, 0, ',', '.') }}</td>
                        <td class="text-center font-monospace small">{{ $tropa->dias_en_feedlot }}</td>
                        <td class="text-end small font-monospace">
                            @if ($tropa->peso_promedio_entrada_kg !== null)
                                {{ number_format((float)$tropa->peso_promedio_entrada_kg, 1, ',', '.') }} kg
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end small font-monospace">
                            @if ($tropa->estado === 'finalizada' && $tropa->ganancia_diaria_real_kg !== null)
                                <span class="text-success">{{ number_format($tropa->ganancia_diaria_real_kg, 3, ',', '.') }} kg</span>
                                <div style="font-size:.7rem;" class="text-muted">real</div>
                            @elseif ($tropa->objetivo_ganancia_diaria_kg !== null)
                                <span class="text-info">{{ number_format((float)$tropa->objetivo_ganancia_diaria_kg, 3, ',', '.') }} kg</span>
                                <div style="font-size:.7rem;" class="text-muted">objetivo</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill {{ $estadoClase }}">
                                {{ \App\Models\Tropa::ESTADOS[$tropa->estado] ?? $tropa->estado }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            @can('feedlot.tropas.gestionar')
                            <button class="btn btn-outline-primary btn-sm"
                                    wire:click="abrirModalEditar('{{ $tropa->id }}')"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if ($tropa->estado === 'activa')
                            <button class="btn btn-outline-danger btn-sm ms-1"
                                    wire:click="cancelarTropa('{{ $tropa->id }}')"
                                    wire:confirm="¿Cancelar la tropa '{{ $tropa->nombre }}'?"
                                    title="Cancelar tropa">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-collection d-block fs-2 mb-2 opacity-50"></i>
                            No hay tropas registradas.
                            @can('feedlot.tropas.gestionar')
                            <a href="#" wire:click.prevent="abrirModalCrear" class="d-block small mt-1">Crear la primera</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tropas->hasPages())
        <div class="card-footer bg-white border-top py-2 px-3">
            {{ $tropas->links() }}
        </div>
        @endif
    </div>

    {{-- ===== MODAL CREAR / EDITAR ===== --}}
    @if ($modalAbierto)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5);">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-collection me-2 text-primary"></i>
                        {{ $modoEdicion ? 'Editar tropa' : 'Nueva tropa' }}
                    </h6>
                    <button type="button" class="btn-close" wire:click="cerrarModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        {{-- Nombre --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   wire:model="nombre" placeholder="Ej: Tropa Novillos Lote A">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Categoría --}}
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select @error('categoria') is-invalid @enderror" wire:model="categoria">
                                <option value="">— Seleccionar —</option>
                                @foreach (\App\Models\Tropa::CATEGORIAS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Estado --}}
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Estado <span class="text-danger">*</span></label>
                            <select class="form-select @error('estado') is-invalid @enderror" wire:model.live="estado">
                                @foreach (\App\Models\Tropa::ESTADOS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Corral --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Corral</label>
                            <select class="form-select @error('idCorral') is-invalid @enderror" wire:model="idCorral">
                                <option value="">— Sin asignar —</option>
                                @foreach ($corrales as $corral)
                                    <option value="{{ $corral->id }}">{{ $corral->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idCorral')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Establecimiento --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Establecimiento</label>
                            <select class="form-select @error('idEstablecimiento') is-invalid @enderror" wire:model="idEstablecimiento">
                                <option value="">— Sin asignar —</option>
                                @foreach ($establecimientos as $est)
                                    <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idEstablecimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- Cantidad --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Cabezas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('cantidadCabezas') is-invalid @enderror"
                                   wire:model="cantidadCabezas" min="1">
                            @error('cantidadCabezas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Fechas --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Fecha de entrada <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('fechaEntrada') is-invalid @enderror"
                                   wire:model="fechaEntrada">
                            @error('fechaEntrada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Salida estimada</label>
                            <input type="date" class="form-control @error('fechaSalidaEstimada') is-invalid @enderror"
                                   wire:model="fechaSalidaEstimada">
                            @error('fechaSalidaEstimada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Salida real
                                @if ($estado === 'finalizada') <span class="text-danger">*</span> @endif
                            </label>
                            <input type="date" class="form-control @error('fechaSalidaReal') is-invalid @enderror"
                                   wire:model="fechaSalidaReal">
                            @error('fechaSalidaReal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        {{-- Pesos --}}
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Peso prom. entrada (kg)</label>
                            <input type="number" class="form-control @error('pesoPesoPromedioEntradaKg') is-invalid @enderror"
                                   wire:model="pesoPesoPromedioEntradaKg" step="0.01" min="0"
                                   placeholder="ej: 280.50">
                            @error('pesoPesoPromedioEntradaKg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Peso prom. salida (kg)
                                @if ($estado === 'finalizada') <span class="text-danger">*</span> @endif
                            </label>
                            <input type="number" class="form-control @error('pesoPesoPromedioSalidaKg') is-invalid @enderror"
                                   wire:model="pesoPesoPromedioSalidaKg" step="0.01" min="0"
                                   placeholder="ej: 420.00">
                            @error('pesoPesoPromedioSalidaKg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Objetivo gan. diaria (kg/día)</label>
                            <input type="number" class="form-control @error('objetivoGananciaDiariaKg') is-invalid @enderror"
                                   wire:model="objetivoGananciaDiariaKg" step="0.001" min="0"
                                   placeholder="ej: 1.200">
                            @error('objetivoGananciaDiariaKg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Alerta finalización --}}
                        @if ($estado === 'finalizada')
                        <div class="col-12">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Al finalizar la tropa se requieren: <strong>fecha de salida real</strong> y <strong>peso promedio de salida</strong>.
                            </div>
                        </div>
                        @endif

                        {{-- Observaciones --}}
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1">Observaciones</label>
                            <textarea class="form-control" wire:model="observaciones" rows="2"
                                      placeholder="Condición sanitaria, procedencia, raza..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 gap-2">
                    <button type="button" class="btn btn-light btn-sm" wire:click="cerrarModal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="guardar" wire:loading.attr="disabled">
                        <span wire:loading wire:target="guardar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="guardar" class="bi bi-check-lg me-1"></i>
                        {{ $modoEdicion ? 'Guardar cambios' : 'Crear tropa' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
