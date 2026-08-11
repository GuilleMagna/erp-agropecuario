<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $periodoId = DB::table('periodos_fiscales')
            ->where('periodo', '2026-07')
            ->value('id');

        if (! $periodoId) {
            $periodoId = (string) Str::uuid();
            DB::table('periodos_fiscales')->insert([
                'id' => $periodoId,
                'periodo' => '2026-07',
                'estado' => 'abierto',
                'observaciones' => 'Período incorporado desde CONTROL MENSUAL 2026.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $reintegros = [
            'ELVIO' => 2717071.38,
            'WILMAR' => 2882263.08,
        ];

        foreach ($reintegros as $empresa => $importe) {
            $empresaId = DB::table('empresas')->where('razon_social', $empresa)->value('id');
            if (! $empresaId) {
                continue;
            }

            $existenteId = DB::table('reintegros_iva')
                ->where('id_empresa', $empresaId)
                ->where('periodo', '2026-07')
                ->value('id');

            $datos = [
                'id_periodo_fiscal' => $periodoId,
                'importe' => $importe,
                'fecha_acreditacion' => '2026-07-24',
                'estado' => 'acreditado',
                'observaciones' => 'Importado desde CONTROL MENSUAL 2026 (SUMA POR MES, julio).',
                'updated_at' => now(),
            ];

            if ($existenteId) {
                DB::table('reintegros_iva')->where('id', $existenteId)->update($datos);
            } else {
                DB::table('reintegros_iva')->insert($datos + [
                    'id' => (string) Str::uuid(),
                    'id_empresa' => $empresaId,
                    'periodo' => '2026-07',
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No se revierten datos fiscales que podrían haber sido editados después.
    }
};