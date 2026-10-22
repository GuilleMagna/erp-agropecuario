<div>
    <h1 class="h3 mb-3">Ubicaciones</h1>
    <p class="text-muted">Organizá provincias, ciudades, zonas y campos. Los gastos de una ubicación se incluyen también al consultar sus ubicaciones superiores. Las propiedades de alquiler se administran en Inmuebles.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card mb-4">
        <div class="card-header">{{ $editandoId ? 'Editar ubicación' : 'Nueva ubicación' }}</div>
        <form wire:submit="guardar" class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="ubicacion-nombre">Nombre</label>
                    <input id="ubicacion-nombre" class="form-control" wire:model="nombre" maxlength="150" required>
                    @error('nombre')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="ubicacion-tipo">Tipo</label>
                    <select id="ubicacion-tipo" class="form-select" wire:model.live="tipo">
                        @foreach($tipos as $codigo => $etiqueta)<option value="{{ $codigo }}">{{ $etiqueta }}</option>@endforeach
                    </select>
                    @error('tipo')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="ubicacion-padre">Ubicación superior</label>
                    <select id="ubicacion-padre" class="form-select" wire:model="id_padre">
                        <option value="">Sin ubicación superior</option>
                        @foreach($opciones as $codigo => $etiqueta)
                            @unless(in_array($codigo, $excluidas, true))<option value="{{ $codigo }}">{{ $etiqueta }}</option>@endunless
                        @endforeach
                    </select>
                    @error('id_padre')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
            @if($tipo === 'campo')
                <div class="form-check mt-3">
                    <input id="crear-ficha" type="checkbox" class="form-check-input" wire:model="crearFicha">
                    <label for="crear-ficha" class="form-check-label">Crear también la ficha en Establecimientos, compartida por las tres cuentas, si todavía no existe.</label>
                    <div class="form-text">Luego podés completar hectáreas, tenencia, responsable y lotes en Establecimientos.</div>
                </div>
            @endif
            <div class="mt-3">
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">Guardar ubicación</button>
                @if($editandoId)<button class="btn btn-outline-secondary" type="button" wire:click="cancelar">Cancelar</button>@endif
            </div>
        </form>
    </div>
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Ubicación</th><th>Tipo</th><th></th></tr></thead>
        <tbody>@foreach($opciones as $codigo => $etiqueta)
            <tr wire:key="ubicacion-{{ $codigo }}"><td>{{ $etiqueta }}</td><td>{{ $tipos[$ubicaciones[$codigo]->tipo] }}</td><td class="text-end"><button class="btn btn-sm btn-outline-primary" wire:click="editar('{{ $codigo }}')">Editar</button></td></tr>
        @endforeach</tbody>
    </table></div></div>
</div>
