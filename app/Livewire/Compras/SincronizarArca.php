<?php

namespace App\Livewire\Compras;

use App\Models\Empresa;
use Livewire\Component;

class SincronizarArca extends Component
{
    public function render()
    {
        $empresasArca = Empresa::where('arca_activo', true)->orderBy('razon_social')->get();

        return view('livewire.compras.sincronizar-arca', compact('empresasArca'));
    }
}
