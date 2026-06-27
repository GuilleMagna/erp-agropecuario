<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0 small">Registro de actividad del sistema. Últimos 30 días por defecto.</p>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill">
            <i class="bi bi-journal-text me-1"></i>{{ $actividades->total() }} registros
        </span>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Usuario</label>
                    <input type="text" class="form-control form-control-sm"
                           wire:model.live.debounce.400ms="filtroUsuario"
                           placeholder="Buscar por nombre...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Modelo</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroModelo">
                        <option value="">Todos</option>
                        @foreach ($tiposModelo as $tipo)
                        <option value="{{ $tipo }}">{{ $modelosLabel[$tipo] ?? class_basename($tipo) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Acción</label>
                    <select class="form-select form-select-sm" wire:model.live="filtroAccion">
                        <option value="">Todas</option>
                        @foreach ($tiposAccion as $accion)
                        <option value="{{ $accion }}">{{ ucfirst($accion) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Desde</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroDesde">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="filtroHasta">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary btn-sm w-100"
                            wire:click="$set('filtroUsuario',''); $set('filtroModelo',''); $set('filtroAccion','');"
                            title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                        <th class="ps-3" style="width:140px;">Fecha / Hora</th>
                        <th style="width:160px;">Usuario</th>
                        <th style="width:100px;">Acción</th>
                        <th>Entidad</th>
                        <th class="pe-3">Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividades as $act)
                    @php
                        $modeloNombre = $modelosLabel[$act->subject_type] ?? class_basename((string)$act->subject_type);
                        $eventoBadge  = [
                            'created' => 'bg-success-subtle text-success',
                            'updated' => 'bg-primary-subtle text-primary',
                            'deleted' => 'bg-danger-subtle text-danger',
                        ][$act->event ?? ''] ?? 'bg-secondary-subtle text-secondary';
                        $eventoLabel  = [
                            'created' => 'Creó',
                            'updated' => 'Modificó',
                            'deleted' => 'Eliminó',
                        ][$act->event ?? ''] ?? ucfirst($act->event ?? $act->description ?? '—');

                        // Extraer cambios del JSON properties
                        $old  = $act->properties['old'] ?? [];
                        $new  = $act->properties['attributes'] ?? [];
                        $diff = collect($new)->filter(fn($val, $key) => ($old[$key] ?? null) !== $val);
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="small font-monospace">{{ $act->created_at->format('d/m/Y') }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $act->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            @if ($act->causer)
                            <div class="small fw-semibold">{{ $act->causer->nombre_completo }}</div>
                            <div class="text-muted" style="font-size:.75rem;">{{ $act->causer->getRoleNames()->first() ?? '—' }}</div>
                            @else
                            <span class="text-muted small">Sistema</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill {{ $eventoBadge }}">{{ $eventoLabel }}</span>
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $modeloNombre }}</div>
                            @if ($act->subject_id)
                            <div class="text-muted font-monospace" style="font-size:.7rem;">{{ substr($act->subject_id, 0, 8) }}…</div>
                            @endif
                        </td>
                        <td class="pe-3">
                            @if ($diff->count() > 0)
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($diff->take(4) as $campo => $valor)
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:.72rem; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                      title="{{ $campo }}: {{ is_array($old[$campo] ?? null) ? json_encode($old[$campo]) : ($old[$campo] ?? '—') }} → {{ is_array($valor) ? json_encode($valor) : $valor }}">
                                    <span class="text-muted">{{ str_replace('_', ' ', $campo) }}:</span>
                                    @if (isset($old[$campo]) && $old[$campo] !== $valor)
                                        <del class="text-danger">{{ is_array($old[$campo]) ? '(objeto)' : substr((string)$old[$campo], 0, 15) }}</del>→
                                    @endif
                                    {{ is_array($valor) ? '(objeto)' : substr((string)$valor, 0, 15) }}
                                </span>
                                @endforeach
                                @if ($diff->count() > 4)
                                <span class="badge rounded-pill bg-light text-muted border" style="font-size:.72rem;">+{{ $diff->count() - 4 }}</span>
                                @endif
                            </div>
                            @elseif ($act->event === 'created')
                            <span class="text-muted small">Registro creado</span>
                            @elseif ($act->event === 'deleted')
                            <span class="text-muted small">Registro eliminado</span>
                            @else
                            <span class="text-muted small">{{ $act->description ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-journal-x d-block fs-2 mb-2 text-muted opacity-50"></i>
                            No hay registros para el período y filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($actividades->hasPages())
        <div class="card-footer bg-white border-top py-2 px-3">
            {{ $actividades->links() }}
        </div>
        @endif
    </div>

</div>
