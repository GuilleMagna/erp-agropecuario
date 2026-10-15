<div>
    <div class="d-flex justify-content-between mb-3"><h1 class="h3">Informe de compras</h1><button class="btn btn-outline-success" wire:click="exportar">Exportar CSV</button></div>
    <p class="text-muted">Importes de los comprobantes de la empresa activa. Las notas de crédito restan y los cancelados se excluyen. La imputación es la Actividad elegida, con Lote y Campaña cuando corresponden. Los pendientes se muestran sin clasificar.</p>
    <div class="card card-body mb-3"><div class="row g-2">
        <div class="col-md-2"><label>Desde</label><input type="date" class="form-control" wire:model.live="desde">@error('desde')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="col-md-2"><label>Hasta</label><input type="date" class="form-control" wire:model.live="hasta">@error('hasta')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="col-md-2"><label>Actividad / Imputación</label><select class="form-select" wire:model.live="actividad"><option value="">Todas</option><option value="sin_clasificar">Sin clasificar</option>@foreach($actividades as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Rubro</label><select class="form-select" wire:model.live="rubro"><option value="">Todos</option><option value="sin_clasificar">Sin rubro</option>@foreach($rubros as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Proveedor</label><select class="form-select" wire:model.live="proveedor"><option value="">Todos</option>@foreach($proveedores as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach</select></div>
    </div></div>
    <div class="alert alert-warning d-flex justify-content-between align-items-center gap-2">
        <span><strong>Sin rubro:</strong> {{ $sinRubro['cantidad'] ?? 0 }} comprobantes · ${{ number_format($sinRubro['importe'] ?? 0,2,',','.') }}</span>
        @can('compras.ver')<a class="btn btn-sm btn-outline-dark" href="{{ $this->enlaceCompras('sin_clasificar') }}"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Ver y completar</a>@endcan
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-6"><x-grafico-clasificacion titulo="Por rubro" :serie="$porRubro" /></div>
        <div class="col-lg-6"><x-grafico-clasificacion titulo="Por actividad / imputación" :serie="$porActividad" /></div>
    </div>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Rubro</th><th>Actividad / Imputación</th><th>Lote</th><th>Campaña</th><th>Comprobantes</th><th>Neto</th><th>IVA</th><th>Total</th><th>Detalle</th></tr></thead><tbody>
    @forelse($filas as $fila)
        <tr><td>{{ $fila->rubro === 'otro' ? 'Sin rubro' : ($rubros[$fila->rubro] ?? $fila->rubro) }}</td><td>{{ $actividades[$fila->actividad] ?? 'Sin clasificar' }}</td><td>{{ $fila->lote?->nombre ?? '—' }}</td><td>{{ $fila->campana?->nombre ?? '—' }}</td><td>{{ $fila->cantidad }}</td><td>${{ number_format($fila->neto,2,',','.') }}</td><td>${{ number_format($fila->iva,2,',','.') }}</td><td>${{ number_format($fila->importe,2,',','.') }}</td><td>@can('compras.ver')<a href="{{ $this->enlaceCompras($fila->rubro === 'otro' ? 'sin_clasificar' : $fila->rubro, $fila->actividad ?: 'sin_clasificar', $fila->id_lote ?: 'sin_asignar', $fila->id_campana ?: 'sin_asignar') }}" aria-label="Ver comprobantes del grupo" title="Ver comprobantes y proveedores"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>@endcan</td></tr>
    @empty<tr><td colspan="9">No hay comprobantes para estos filtros.</td></tr>@endforelse
    </tbody><tfoot><tr class="fw-bold"><td colspan="4">Total</td><td>{{ $filas->sum('cantidad') }}</td><td>${{ number_format($filas->sum('neto'),2,',','.') }}</td><td>${{ number_format($filas->sum('iva'),2,',','.') }}</td><td>${{ number_format($filas->sum('importe'),2,',','.') }}</td><td></td></tr></tfoot></table></div>
</div>
