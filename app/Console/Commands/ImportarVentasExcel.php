<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\VentaGrano;
use App\Models\VentaHacienda;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarVentasExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importar:ventas-excel {archivo? : Ruta al xlsx (default docs/CONTROL MENSUAL 2026.xlsx)} {--dry-run : No guarda nada, solo muestra el resultado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa el historial de ventas de granos y hacienda desde la hoja VENTAS de la planilla de control mensual';

    private const GRANOS = [
        'SOJA' => 'soja',
        'TRIGO' => 'trigo',
        'MAIZ' => 'maiz',
    ];

    private const HACIENDA = [
        'NOVILLO' => 'novillo',
        'NOVILLOS' => 'novillo',
        'NOVILLITO' => 'novillito',
        'VAQUILLONAS' => 'vaquillona',
        'VAQUILLONA' => 'vaquillona',
        'TORO' => 'toro',
        'VACA' => 'vaca',
        'TERNERO' => 'ternero',
        'TERNERA' => 'ternera',
        'CAPON' => 'capon',
    ];

    public function handle(): int
    {
        $path = $this->argument('archivo') ?? base_path('docs/CONTROL MENSUAL 2026.xlsx');

        if (! file_exists($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('VENTAS');

        if (! $sheet) {
            $this->error('La planilla no tiene una hoja "VENTAS".');

            return self::FAILURE;
        }

        // calculateFormulas=false: la planilla usa referencias estructuradas de tabla
        // (VENTAS2026[[#This Row],[...]]) que el motor de fórmulas de PhpSpreadsheet no
        // sabe resolver: se usa el último valor calculado y guardado en el archivo.
        // formatData=false: se piden los valores numéricos crudos, no el string formateado
        // (con separador de miles o símbolo de moneda), que rompería el cast a float.
        $rows = $sheet->toArray(null, false, false, false);

        // Fila de encabezado real (fila 4 del Excel, índice 3): Empresa | Fecha | Comprador | Producto | ...
        $header = array_map(fn ($v) => strtolower(trim((string) ($v ?? ''))), $rows[3] ?? []);
        if (! in_array('empresa', $header) || ! in_array('producto', $header)) {
            $this->error('No se reconoce el formato de encabezados de la hoja VENTAS (se esperaba "Empresa"/"Producto" en la fila 4).');

            return self::FAILURE;
        }

        $empresas = Empresa::all()->keyBy(fn ($e) => strtoupper($e->razon_social));
        $aliasEmpresa = [];

        $creadasGranos = 0;
        $actualizadasGranos = 0;
        $creadasHacienda = 0;
        $omitidas = 0;
        $yaExistentes = 0;

        foreach (array_slice($rows, 4, null, true) as $i => $row) {
            $nombreEmpresa = strtoupper(trim((string) ($row[0] ?? '')));
            $producto = strtoupper(trim((string) ($row[3] ?? '')));

            if ($nombreEmpresa === '' || $producto === '') {
                continue;
            }

            $claveEmpresa = $aliasEmpresa[$nombreEmpresa] ?? $nombreEmpresa;
            $empresa = $empresas->get($claveEmpresa);

            if (! $empresa) {
                $this->warn('Fila '.($i + 1).": empresa \"{$nombreEmpresa}\" no encontrada en la tabla empresas, se omite.");
                $omitidas++;

                continue;
            }

            $fecha = $this->parsearFecha($row[1] ?? null);
            if (! $fecha) {
                $this->warn('Fila '.($i + 1).': fecha inválida, se omite.');
                $omitidas++;

                continue;
            }

            $comprador = trim((string) ($row[2] ?? '')) ?: null;
            $cantidadKg = (float) ($row[4] ?? 0);
            $precioKg = (float) ($row[6] ?? 0);
            $datosLiquidacion = [
                'cantidad_kg' => $cantidadKg,
                'factor' => (float) ($row[5] ?? 100),
                'precio_kg' => $precioKg,
                'flete_kg' => (float) ($row[7] ?? 0),
                'deducciones' => (float) ($row[8] ?? 0),
                'iva_deducciones' => (float) ($row[9] ?? 0),
                'bonificacion' => (float) ($row[10] ?? 0),
                'ret_ganancias' => (float) ($row[11] ?? 0),
                'ret_iva' => (float) ($row[12] ?? 0),
                'iva_rg4310' => (float) ($row[13] ?? 0),
            ];

            // Columna P (Subtotal, neto sin IVA/retenciones) suele ser una fórmula con
            // referencias estructuradas de tabla que PhpSpreadsheet no puede recalcular: se
            // lee el valor cacheado que Excel guardó la última vez que se abrió/guardó el
            // archivo. Pero algunas filas tienen el subtotal tipeado a mano (sin fórmula),
            // ahí getOldCalculatedValue() da null porque no hay nada cacheado.
            $celdaSubtotal = $sheet->getCell('P'.($i + 1));
            $subtotal = (float) ($celdaSubtotal->isFormula()
                ? $celdaSubtotal->getOldCalculatedValue()
                : $celdaSubtotal->getValue());

            if ($cantidadKg <= 0 && $subtotal <= 0) {
                $this->warn('Fila '.($i + 1).': sin cantidad ni subtotal, se omite.');
                $omitidas++;

                continue;
            }

            if (isset(self::GRANOS[$producto])) {
                // El comando no es idempotente (siempre inserta), y la planilla se vuelve a
                // exportar completa cada vez que se pide una actualización: sin este chequeo,
                // reimportar duplicaría todo lo ya cargado. Se toma como clave natural la
                // combinación empresa+fecha+cereal+cantidad, y el importe se compara con
                // tolerancia porque el valor cacheado de la fórmula de Excel puede variar unos
                // pesos entre una exportación y otra para la misma operación real.
                $ventaExistente = VentaGrano::where('id_empresa', $empresa->id)
                    ->where('fecha', $fecha)
                    ->where('cereal', self::GRANOS[$producto])
                    ->where('cantidad_tn', round($cantidadKg / 1000, 3))
                    ->get()
                    ->first(fn ($v) => abs($v->importe_total - round($subtotal, 2)) < 100);

                if ($ventaExistente) {
                    $ventaExistente->fill($datosLiquidacion);
                    if ($ventaExistente->isDirty()) {
                        if (! $dryRun) {
                            $ventaExistente->save();
                        }
                        $actualizadasGranos++;
                    }
                    $yaExistentes++;

                    continue;
                }

                if (! $dryRun) {
                    // id_empresa no es mass-assignable (lo completa el trait solo cuando hay
                    // un usuario autenticado, algo que no aplica en un comando de consola),
                    // así que se asigna directo sobre la instancia antes de guardar.
                    $venta = new VentaGrano(array_merge([
                        'comprador' => $comprador,
                        'cereal' => self::GRANOS[$producto],
                        'tipo_venta' => 'disponible',
                        'fecha' => $fecha,
                        'cantidad_tn' => round($cantidadKg / 1000, 3),
                        'precio_tn' => round($precioKg * 1000, 2),
                        'moneda' => 'ARS',
                        'importe_total' => round($subtotal, 2),
                        'estado' => 'confirmada',
                        'observaciones' => 'Importado desde CONTROL MENSUAL 2026.xlsx (hoja VENTAS, fila '.($i + 1).')',
                    ], $datosLiquidacion));
                    $venta->id_empresa = $empresa->id;
                    $venta->save();
                }
                $creadasGranos++;
            } elseif (isset(self::HACIENDA[$producto])) {
                $yaExiste = VentaHacienda::where('id_empresa', $empresa->id)
                    ->where('fecha', $fecha)
                    ->where('categoria', self::HACIENDA[$producto])
                    ->get()
                    ->contains(fn ($v) => abs($v->importe_total - round($subtotal, 2)) < 100);

                if ($yaExiste) {
                    $yaExistentes++;

                    continue;
                }

                if (! $dryRun) {
                    // La planilla mezcla ventas cotizadas por kg y ventas cotizadas por
                    // cabeza en las mismas columnas "Cantidad-KG"/"Precio/KG" (ver fila con
                    // NOVILLO a $1.300.000 "por kg", que en realidad es precio por cabeza).
                    // Se distingue por magnitud: un precio por kg de hacienda nunca llega a
                    // $100.000 (ni en escenarios de alta inflación), mientras que un precio
                    // por cabeza siempre lo supera ampliamente. Verificado contra los 12
                    // registros históricos de la planilla: el corte funciona en todos los casos.
                    $esPrecioPorCabeza = $precioKg > 100000;

                    $venta = new VentaHacienda([
                        'comprador' => $comprador,
                        'fecha' => $fecha,
                        'tipo_operacion' => 'terminado',
                        'categoria' => self::HACIENDA[$producto],
                        'cantidad_cabezas' => $esPrecioPorCabeza ? (int) round($cantidadKg) : null,
                        'precio_cabeza' => $esPrecioPorCabeza ? $precioKg : null,
                        'peso_total_kg' => $esPrecioPorCabeza ? null : $cantidadKg,
                        'precio_kg' => $esPrecioPorCabeza ? null : ($precioKg ?: null),
                        'importe_total' => round($subtotal, 2),
                        'moneda' => 'ARS',
                        'estado' => 'confirmada',
                        'observaciones' => 'Importado desde CONTROL MENSUAL 2026.xlsx (hoja VENTAS, fila '.($i + 1).').',
                    ]);
                    $venta->id_empresa = $empresa->id;
                    $venta->save();
                }
                $creadasHacienda++;
            } else {
                $this->warn('Fila '.($i + 1).": producto \"{$producto}\" no reconocido, se omite.");
                $omitidas++;
            }
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Ventas de granos creadas: {$creadasGranos}");
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Ventas de granos actualizadas: {$actualizadasGranos}");
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Ventas de hacienda creadas: {$creadasHacienda}");
        $this->info("Filas ya existentes (omitidas por duplicado): {$yaExistentes}");
        $this->info("Filas omitidas: {$omitidas}");

        return self::SUCCESS;
    }

    private function parsearFecha(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 10000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Exception) {
            }
        }

        $str = trim((string) $value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $str);
                if ($d && $d->year > 2000) {
                    return $d->format('Y-m-d');
                }
            } catch (\Exception) {
            }
        }

        return null;
    }
}
