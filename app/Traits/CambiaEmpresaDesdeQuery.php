<?php

namespace App\Traits;

use App\Models\Empresa;

/**
 * Permite que un link con ?empresa=<uuid> (por ej. desde un reporte consolidado que
 * cruza las 3 empresas) cambie la empresa activa de la sesión antes de que el
 * componente ejecute sus queries, reusando el mismo mecanismo del selector de
 * empresa (ver routes/web.php: POST /empresa/cambiar), disponible para los
 * roles con permiso "sistema.empresas.cambiar".
 */
trait CambiaEmpresaDesdeQuery
{
    protected function switchEmpresaDesdeQuery(): void
    {
        $empresaId = request()->query('empresa');

        if (! $empresaId || ! auth()->check() || ! auth()->user()->can('sistema.empresas.cambiar')) {
            return;
        }

        if (Empresa::where('id', $empresaId)->exists()) {
            session(['empresa_activa_id' => $empresaId]);
        }
    }
}
