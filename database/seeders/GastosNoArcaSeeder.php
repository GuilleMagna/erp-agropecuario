<?php

namespace Database\Seeders;

use App\Models\GastoNoArca;
use App\Models\PagoGastoNoArca;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GastosNoArcaSeeder extends Seeder
{
    public function run(): void
    {
        $datos = $this->datos();
        $orden = 0;

        foreach ($datos as $item) {
            $orden++;
            $gasto = GastoNoArca::create([
                'id'        => Str::uuid(),
                'nombre'    => $item['nombre'],
                'categoria' => $item['categoria'],
                'orden'     => $orden,
                'activo'    => true,
            ]);

            foreach ($item['pagos'] as $pago) {
                $fechaPago = $this->parsearFecha($pago['fecha_pago']);
                $notas     = is_string($pago['fecha_pago']) && !$fechaPago && !empty($pago['fecha_pago'])
                    ? $pago['fecha_pago']   // guardar texto libre como nota
                    : null;

                PagoGastoNoArca::create([
                    'id'         => Str::uuid(),
                    'id_gasto'   => $gasto->id,
                    'mes'        => $pago['mes'],
                    'importe'    => $pago['importe'],
                    'fecha_pago' => $fechaPago,
                    'notas'      => $notas,
                ]);
            }
        }
    }

    private function parsearFecha(mixed $valor): ?string
    {
        if (!$valor || !is_string($valor)) return null;
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $valor);
                if ($d && $d->year > 2000 && $d->year < 2100) return $d->format('Y-m-d');
            } catch (\Exception) {}
        }
        return null;
    }

    private function datos(): array
    {
        return [
            // ── Servicios públicos (Rosario) ─────────────────────────────────
            ['nombre' => 'Aguas santafesinas S.A (Dto Rosario)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 23282.54,  'fecha_pago' => '2025-10-09'],
                ['mes' => '2025-11-01', 'importe' => 27511.46,  'fecha_pago' => '2025-11-07'],
                ['mes' => '2025-12-01', 'importe' => 27511.47,  'fecha_pago' => '2025-12-03'],
                ['mes' => '2026-01-01', 'importe' => 27511.47,  'fecha_pago' => '2026-01-05'],
                ['mes' => '2026-02-01', 'importe' => 27511.47,  'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 31454.38,  'fecha_pago' => '2026-03-06'],
                ['mes' => '2026-04-01', 'importe' => 31454.38,  'fecha_pago' => '2026-04-06'],
                ['mes' => '2026-05-01', 'importe' => 34804.19,  'fecha_pago' => '2026-05-11'],
                ['mes' => '2026-06-01', 'importe' => 34804.19,  'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'EPE (Dto Rosario)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 14169.02,  'fecha_pago' => '2025-10-20'],
                ['mes' => '2025-11-01', 'importe' => 14169.02,  'fecha_pago' => '2025-11-10'],
                ['mes' => '2025-12-01', 'importe' => 25190.97,  'fecha_pago' => '2025-12-17'],
                ['mes' => '2026-01-01', 'importe' => 25190.97,  'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 63686.79,  'fecha_pago' => '2026-02-23'],
                ['mes' => '2026-03-01', 'importe' => 63686.78,  'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 42880.71,  'fecha_pago' => '2026-04-09'],
                ['mes' => '2026-05-01', 'importe' => 42880.71,  'fecha_pago' => '2026-05-18'],
                ['mes' => '2026-06-01', 'importe' => 36774.21,  'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'TGI Rosario (Dto Rosario)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-11-01', 'importe' => 30751.76,  'fecha_pago' => '2025-11-07'],
                ['mes' => '2025-12-01', 'importe' => 30751.76,  'fecha_pago' => '2025-12-03'],
                ['mes' => '2026-01-01', 'importe' => 35587.02,  'fecha_pago' => '2026-01-05'],
                ['mes' => '2026-02-01', 'importe' => 332387.60, 'fecha_pago' => '2026-02-10'],
            ]],
            ['nombre' => 'Litoral Gas (Dto Rosario)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 12619.73,  'fecha_pago' => '2025-10-09'],
                ['mes' => '2025-11-01', 'importe' => 12625.38,  'fecha_pago' => '2025-11-07'],
                ['mes' => '2025-12-01', 'importe' => 7256.43,   'fecha_pago' => '2025-12-03'],
                ['mes' => '2026-01-01', 'importe' => 7260.99,   'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 7728.12,   'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 7555.43,   'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 7868.14,   'fecha_pago' => '2026-04-09'],
                ['mes' => '2026-05-01', 'importe' => 7830.63,   'fecha_pago' => '2026-05-11'],
                ['mes' => '2026-06-01', 'importe' => 9450.81,   'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'EPE (VCP)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 8336.00,   'fecha_pago' => '2025-10-20'],
                ['mes' => '2025-11-01', 'importe' => 7944.80,   'fecha_pago' => '2025-11-13'],
                ['mes' => '2025-12-01', 'importe' => 8020.80,   'fecha_pago' => '2025-12-15'],
                ['mes' => '2026-01-01', 'importe' => 15443.90,  'fecha_pago' => '2026-01-19'],
                ['mes' => '2026-02-01', 'importe' => 15508.70,  'fecha_pago' => '2026-02-23'],
                ['mes' => '2026-03-01', 'importe' => 39572.60,  'fecha_pago' => '2026-03-12'],
                ['mes' => '2026-04-01', 'importe' => 42986.40,  'fecha_pago' => '2026-04-09'],
                ['mes' => '2026-05-01', 'importe' => 12684.60,  'fecha_pago' => '2026-05-18'],
                ['mes' => '2026-06-01', 'importe' => 12793.20,  'fecha_pago' => '2026-06-16'],
            ]],
            ['nombre' => 'EPEC (FABIANA NUEVO)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-12-01', 'importe' => 61352.10,  'fecha_pago' => '2025-12-15'],
                ['mes' => '2026-01-01', 'importe' => 11641.30,  'fecha_pago' => '2026-01-19'],
                ['mes' => '2026-02-01', 'importe' => 11775.20,  'fecha_pago' => '2026-02-25'],
                ['mes' => '2026-03-01', 'importe' => 7835.00,   'fecha_pago' => '2026-03-12'],
                ['mes' => '2026-04-01', 'importe' => 7538.20,   'fecha_pago' => '2026-04-09'],
                ['mes' => '2026-05-01', 'importe' => 9226.00,   'fecha_pago' => '2026-05-18'],
                ['mes' => '2026-06-01', 'importe' => 9407.90,   'fecha_pago' => '2026-06-16'],
            ]],
            // ── Expensas ─────────────────────────────────────────────────────
            ['nombre' => 'Veneto V Expensas', 'categoria' => 'expensas', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 182983.96, 'fecha_pago' => '2025-10-13'],
                ['mes' => '2025-11-01', 'importe' => 226665.52, 'fecha_pago' => '2025-11-10'],
                ['mes' => '2025-12-01', 'importe' => 205944.26, 'fecha_pago' => '2025-12-09'],
                ['mes' => '2026-01-01', 'importe' => 220653.37, 'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 221570.27, 'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 232987.29, 'fecha_pago' => '2026-03-01'],
                ['mes' => '2026-04-01', 'importe' => 210914.84, 'fecha_pago' => '2026-04-10'],
                ['mes' => '2026-06-01', 'importe' => 220543.63, 'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'Veneto V Cochera', 'categoria' => 'expensas', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 21909.49,  'fecha_pago' => '2025-10-13'],
                ['mes' => '2025-11-01', 'importe' => 31075.44,  'fecha_pago' => '2025-11-10'],
                ['mes' => '2025-12-01', 'importe' => 28435.10,  'fecha_pago' => '2025-12-09'],
                ['mes' => '2026-01-01', 'importe' => 30039.55,  'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 32028.14,  'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 32433.27,  'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 33014.53,  'fecha_pago' => '2026-04-10'],
                ['mes' => '2026-06-01', 'importe' => 34425.19,  'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'Veneto VII Expensas', 'categoria' => 'expensas', 'pagos' => [
                ['mes' => '2025-11-01', 'importe' => 162543.84, 'fecha_pago' => '2025-11-14'],
                ['mes' => '2025-12-01', 'importe' => 179599.27, 'fecha_pago' => '2025-12-15'],
                ['mes' => '2026-01-01', 'importe' => 180984.11, 'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 165265.62, 'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 174368.32, 'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 176969.18, 'fecha_pago' => '2026-04-09'],
                ['mes' => '2026-05-01', 'importe' => 218749.18, 'fecha_pago' => '2026-05-15'],
                ['mes' => '2026-06-01', 'importe' => 211753.18, 'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'Veneto VII Cochera', 'categoria' => 'expensas', 'pagos' => [
                ['mes' => '2025-11-01', 'importe' => 43853.74,  'fecha_pago' => '2025-11-14'],
                ['mes' => '2025-12-01', 'importe' => 50678.26,  'fecha_pago' => '2025-12-15'],
                ['mes' => '2026-01-01', 'importe' => 52017.93,  'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 46829.39,  'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 48861.06,  'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 49832.86,  'fecha_pago' => '2026-04-09'],
                ['mes' => '2026-05-01', 'importe' => 61162.59,  'fecha_pago' => '2026-05-15'],
                ['mes' => '2026-06-01', 'importe' => 53888.68,  'fecha_pago' => '2026-06-08'],
            ]],
            // ── Obra social ──────────────────────────────────────────────────
            ['nombre' => 'Federada Salud Elvio', 'categoria' => 'obra_social', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 620892.73,  'fecha_pago' => '2025-10-09'],
                ['mes' => '2025-11-01', 'importe' => 645996.84,  'fecha_pago' => '2025-11-10'],
                ['mes' => '2025-12-01', 'importe' => 648075.73,  'fecha_pago' => '2025-12-09'],
                ['mes' => '2026-01-01', 'importe' => 868508.21,  'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 679307.23,  'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 642233.01,  'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 647331.00,  'fecha_pago' => '2026-04-06'],
                ['mes' => '2026-05-01', 'importe' => 682750.62,  'fecha_pago' => '2026-05-11'],
                ['mes' => '2026-06-01', 'importe' => 700831.73,  'fecha_pago' => '2026-06-08'],
            ]],
            ['nombre' => 'Federada Salud Nelva', 'categoria' => 'obra_social', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 375963.91,  'fecha_pago' => '2025-10-09'],
                ['mes' => '2025-11-01', 'importe' => 379156.50,  'fecha_pago' => '2025-11-10'],
                ['mes' => '2025-12-01', 'importe' => 392145.12,  'fecha_pago' => '2025-12-09'],
                ['mes' => '2026-01-01', 'importe' => 399299.22,  'fecha_pago' => '2026-01-12'],
                ['mes' => '2026-02-01', 'importe' => 408263.50,  'fecha_pago' => '2026-02-10'],
                ['mes' => '2026-03-01', 'importe' => 420052.00,  'fecha_pago' => '2026-03-09'],
                ['mes' => '2026-04-01', 'importe' => 432168.00,  'fecha_pago' => '2026-04-06'],
                ['mes' => '2026-05-01', 'importe' => 466825.90,  'fecha_pago' => '2026-05-11'],
                ['mes' => '2026-06-01', 'importe' => 462535.23,  'fecha_pago' => '2026-06-08'],
            ]],
            // ── EPE (varias sedes) ───────────────────────────────────────────
            ['nombre' => 'EPE (532)', 'categoria' => 'servicios_publicos', 'pagos' => [
                ['mes' => '2025-10-01', 'importe' => 43730.52,  'fecha_pago' => '2025-10-09'],
                ['mes' => '2025-11-01', 'importe' => 43730.52,  'fecha_pago' => '2025-11-10'],
                ['mes' => '2025-12-01', 'importe' => 52184.34,  'fecha_pago' => '2025-11-07'],
                ['mes' => '2026-01-01', 'importe' => 52184.34,  'fecha_pago' => '2026-01-06'],
                ['mes' => '2026-02-01', 'importe' => 66087.01,  'fecha_pago' => '2026-02-02'],
                ['mes' => '2026-03-01', 'importe' => 66087.01,  'fecha_pago' => '2026-03-06'],
                ['mes' => '2026-04-01', 'importe' => 112236.72, 'fecha_pago' => '2026-04-01'],
                ['mes' => '2026-05-01', 'importe' => 112236.71, 'fecha_pago' => '2026-05-04'],
                ['mes' => '2026-06-01', 'importe' => 73703.69,  'fecha_pago' => '2026-06-01'],
            ]],
            ['nombre' => 'EPE (876)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'EPE (140)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'EPE (563)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'EPE (715)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'EPE (707)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'EPE (708)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            // ── Agua potable ─────────────────────────────────────────────────
            ['nombre' => 'Agua potable (952)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'Agua potable (113)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'Agua potable (131)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'Agua potable (190)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            // ── Litoral Gas / TGI ────────────────────────────────────────────
            ['nombre' => 'Litoral Gas (999)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'Litoral Gas (988)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'Litoral Gas (997)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'Litoral Gas (992)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'TGI (10701)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'TGI (10724)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'TGI (11706)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            ['nombre' => 'TGI (12418)', 'categoria' => 'servicios_publicos', 'pagos' => []],
            // ── Telecom ──────────────────────────────────────────────────────
            ['nombre' => 'VCPT Cable Carlos Paz', 'categoria' => 'telecom', 'pagos' => []],
            ['nombre' => 'Telecom empresa (904)', 'categoria' => 'telecom', 'pagos' => []],
            ['nombre' => 'Personal Flow Elvio',   'categoria' => 'telecom', 'pagos' => []],
            ['nombre' => 'Personal Flow Nelva',   'categoria' => 'telecom', 'pagos' => []],
            ['nombre' => 'Personal Flow Wilmar',  'categoria' => 'telecom', 'pagos' => []],
            // ── Comité de cuenca ─────────────────────────────────────────────
            ['nombre' => 'Corrientes comité de cuenca (450)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (451)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (452)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (453)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (495)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (107)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (188)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (210)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (348)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            ['nombre' => 'Corrientes comité de cuenca (352)', 'categoria' => 'comite_cuenca', 'pagos' => []],
            // ── Seguros ──────────────────────────────────────────────────────
            ['nombre' => 'La Segunda Corrientes', 'categoria' => 'seguros', 'pagos' => []],
            // ── Luz Corrientes ───────────────────────────────────────────────
            ['nombre' => 'Luz Corrientes Bella Vista', 'categoria' => 'servicios_publicos', 'pagos' => []],
            // ── Tarjetas ─────────────────────────────────────────────────────
            ['nombre' => 'Tarjeta Cabal',            'categoria' => 'tarjetas', 'pagos' => []],
            ['nombre' => 'Tarjeta Mastercard Nación', 'categoria' => 'tarjetas', 'pagos' => []],
            ['nombre' => 'Tarjeta Visa Credicoop',   'categoria' => 'tarjetas', 'pagos' => []],
            ['nombre' => 'Tarjeta Visa Nación',      'categoria' => 'tarjetas', 'pagos' => []],
            ['nombre' => 'Tarjeta Credicat',         'categoria' => 'tarjetas', 'pagos' => []],
            // ── RRHH ─────────────────────────────────────────────────────────
            ['nombre' => 'Sueldo Marco',              'categoria' => 'rrhh', 'pagos' => []],
            ['nombre' => 'Leyes Sociales Marco',      'categoria' => 'rrhh', 'pagos' => []],
            ['nombre' => 'Transferencia Fajardo Jonatan', 'categoria' => 'rrhh', 'pagos' => []],
            ['nombre' => 'Transferencia Godoy',       'categoria' => 'rrhh', 'pagos' => []],
            ['nombre' => 'Transferencia Ortellao',    'categoria' => 'rrhh', 'pagos' => []],
            // ── Combustible ──────────────────────────────────────────────────
            ['nombre' => 'Tickets Shell', 'categoria' => 'combustible', 'pagos' => []],
        ];
    }
}
