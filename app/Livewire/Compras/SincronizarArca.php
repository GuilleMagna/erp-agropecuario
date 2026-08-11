<?php

namespace App\Livewire\Compras;

use App\Models\ArcaSyncRun;
use App\Models\Empresa;
use Livewire\Component;

class SincronizarArca extends Component
{
    public function render()
    {
        $empresasArca = Empresa::where('arca_activo', true)->orderBy('razon_social')->get();

        $ejecuciones = ArcaSyncRun::query()->latest('iniciado_at')->limit(10)->get();
        $ultimaEjecucion = $ejecuciones->first();

        return view('livewire.compras.sincronizar-arca', compact('empresasArca', 'ejecuciones', 'ultimaEjecucion'));
    }
}
