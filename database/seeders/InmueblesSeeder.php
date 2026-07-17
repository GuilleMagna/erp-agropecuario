<?php

namespace Database\Seeders;

use App\Models\GastoNoArca;
use App\Models\Inmueble;
use Illuminate\Database\Seeder;

/**
 * Alta de las 3 propiedades de renta y tageo de los ítems del catálogo de
 * Gastos NO ARCA que ya correspondían a ellas (expensas, EPE, gas, agua).
 * Idempotente: usa firstOrCreate / actualiza por nombre exacto del catálogo.
 */
class InmueblesSeeder extends Seeder
{
    public function run(): void
    {
        $inmuebles = [
            'Veneto V'    => ['localidad' => 'Carlos Paz', 'gastos' => [
                'Veneto V Expensas', 'Veneto V Cochera', 'EPE (VCP)', 'VCPT Cable Carlos Paz',
            ]],
            'Veneto VII'  => ['localidad' => 'La Falda', 'gastos' => [
                'Veneto VII Expensas', 'Veneto VII Cochera',
            ]],
            'Dto Rosario' => ['localidad' => 'Rosario', 'gastos' => [
                'EPE (Dto Rosario)', 'Aguas santafesinas S.A (Dto Rosario)',
                'Litoral Gas (Dto Rosario)', 'TGI Rosario (Dto Rosario)',
            ]],
        ];

        foreach ($inmuebles as $nombre => $datos) {
            $inmueble = Inmueble::firstOrCreate(
                ['nombre' => $nombre],
                ['localidad' => $datos['localidad'], 'activo' => true]
            );

            $actualizados = GastoNoArca::whereIn('nombre', $datos['gastos'])
                ->update(['id_inmueble' => $inmueble->id]);

            $this->command?->info("{$nombre}: {$actualizados} gasto(s) NO ARCA vinculado(s).");
        }
    }
}
