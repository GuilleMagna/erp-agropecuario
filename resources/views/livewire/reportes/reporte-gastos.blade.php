<div>
    <div class="d-flex justify-content-between mb-3"><div><h1 class="h3 mb-1">Gastos por rubro</h1><p class="text-muted mb-0">Comprobantes de compras y gastos no ARCA</p></div><button class="btn btn-outline-success" wire:click="exportar">Exportar CSV</button></div>
    <p class="text-muted">Empresa activa: comprobantes de Compras (total con IVA, notas de crédito restan, cancelados excluidos) y registros mensuales de Gastos no ARCA. Los gastos generales sin empresa se identifican por separado.</p>
    <div class="alert alert-info">El total reúne ambos registros: no elimina duplicados automáticamente. Si una misma factura también se cargó en Gastos no ARCA, revisá sus orígenes antes de interpretar la suma como gasto consolidado. El período usa fecha del comprobante en Compras y mes registrado en Gastos no ARCA.</div>
    <div class="card card-body mb-3"><div class="row g-3">
        <div class="col-md-2"><label>Desde</label><input type="month" class="form-control" wire:model.live="desde">@error('desde')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="col-md-2"><label>Hasta</label><input type="month" class="form-control" wire:model.live="hasta">@error('hasta')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="col-md-3"><label>Rubro</label><select class="form-select" wire:model.live="rubro"><option value="">Todos</option><option value="sin_clasificar">Sin rubro</option>@foreach($rubros as $codigo=>$nombre)<option value="{{ $codigo }}">{{ $nombre }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Origen</label><select class="form-select" wire:model.live="origen"><option value="">Compras y Gastos no ARCA</option><option value="compras">Sólo Compras</option><option value="no_arca">Sólo Gastos no ARCA</option></select></div>
        <div class="col-md-3"><label>Ubicación</label><select class="form-select" wire:model.live="zona"><option value="">Todas</option><option value="sin_clasificar">Sin ubicación</option>@foreach($zonas as $codigo=>$nombre)<option value="{{ $codigo }}">{{ $nombre }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end"><label><input type="checkbox" class="form-check-input" wire:model.live="incluirGenerales"> Incluir gastos sin empresa</label></div>
    </div></div>
    <div class="row g-3 mb-4">
        @foreach(['compras'=>'Compras','no_arca'=>'Gastos no ARCA de la empresa','generales'=>'Gastos generales sin empresa'] as $codigo=>$nombre)
            <div class="col-md-4"><div class="card card-body"><span>{{ $nombre }}</span><strong>${{ number_format($filas->where('origen',$codigo)->sum('importe'),2,',','.') }}</strong></div></div>
        @endforeach
    </div>
    <div class="row g-3 mb-4"><div class="col-lg-6"><x-grafico-clasificacion titulo="Gastos por rubro" :serie="$serie" /></div><div class="col-lg-6"><x-grafico-clasificacion titulo="Gastos por ubicación" :serie="$porZona" /></div></div>
    <div><div>
        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Rubro</th><th>Ubicación</th><th>Origen</th><th>Registros</th><th>Total</th><th>Detalle</th></tr></thead><tbody>
        @forelse($filas as $fila)
            <tr><td>{{ $rubros[$fila['rubro']] ?? 'Sin rubro' }}</td><td>{{ $zonas[$fila['zona']] ?? 'Sin ubicación' }}</td><td>{{ ['compras'=>'Compras','no_arca'=>'Gastos no ARCA','generales'=>'Generales sin empresa'][$fila['origen']] }}</td><td>{{ $fila['cantidad'] }}</td><td>${{ number_format($fila['importe'],2,',','.') }}</td><td><a href="{{ $this->enlace($fila['rubro'],$fila['origen'],$fila['zona']) }}" aria-label="Ver registros del grupo" title="{{ $fila['origen'] === 'compras' ? 'Ver comprobantes del período' : 'Ver servicios del rubro y su historial mensual' }}"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a></td></tr>
        @empty<tr><td colspan="6">No hay registros para estos filtros.</td></tr>@endforelse
        </tbody><tfoot><tr class="fw-bold"><td colspan="3">Total registrado (sin conciliación de duplicados)</td><td>{{ $filas->sum('cantidad') }}</td><td>${{ number_format($filas->sum('importe'),2,',','.') }}</td><td></td></tr></tfoot></table></div>
    </div></div>
</div>
