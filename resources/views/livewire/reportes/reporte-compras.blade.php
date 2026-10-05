<div>
    <div class="d-flex justify-content-between mb-3"><h1 class="h3">Informe de compras</h1><button class="btn btn-outline-success" wire:click="exportar">Exportar CSV</button></div>
    <p class="text-muted">Importes de los comprobantes de la empresa activa. Las notas de crédito restan y los cancelados se excluyen. La imputación es la Actividad elegida, con Lote y Campaña cuando corresponden. Los pendientes se muestran sin clasificar.</p>
    <div class="card card-body mb-3"><div class="row g-2">
        <div class="col-md-2"><label>Desde</label><input type="date" class="form-control" wire:model.live="desde">@error('desde')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="col-md-2"><label>Hasta</label><input type="date" class="form-control" wire:model.live="hasta">@error('hasta')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="col-md-2"><label>Actividad / Imputación</label><select class="form-select" wire:model.live="actividad"><option value="">Todas</option><option value="sin_clasificar">Sin clasificar</option>@foreach($actividades as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Rubro</label><select class="form-select" wire:model.live="rubro"><option value="">Todos</option><option value="sin_clasificar">Sin clasificar</option>@foreach($rubros as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Proveedor</label><select class="form-select" wire:model.live="proveedor"><option value="">Todos</option>@foreach($proveedores as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach</select></div>
    </div></div>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Rubro</th><th>Actividad / Imputación</th><th>Lote</th><th>Campaña</th><th>Comprobantes</th><th>Neto</th><th>IVA</th><th>Total</th></tr></thead><tbody>
    @forelse($filas as $fila)
        <tr><td>{{ $rubros[$fila->rubro] ?? $fila->rubro }}</td><td>{{ $actividades[$fila->actividad] ?? 'Sin clasificar' }}</td><td>{{ $fila->lote?->nombre ?? '—' }}</td><td>{{ $fila->campana?->nombre ?? '—' }}</td><td>{{ $fila->cantidad }}</td><td>${{ number_format($fila->neto,2,',','.') }}</td><td>${{ number_format($fila->iva,2,',','.') }}</td><td>${{ number_format($fila->importe,2,',','.') }}</td></tr>
    @empty<tr><td colspan="8">No hay comprobantes para estos filtros.</td></tr>@endforelse
    </tbody><tfoot><tr class="fw-bold"><td colspan="4">Total</td><td>{{ $filas->sum('cantidad') }}</td><td>${{ number_format($filas->sum('neto'),2,',','.') }}</td><td>${{ number_format($filas->sum('iva'),2,',','.') }}</td><td>${{ number_format($filas->sum('importe'),2,',','.') }}</td></tr></tfoot></table></div>
</div>
