<?php

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Support\TipoComprobanteArca;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportarFacturas2024 extends Command
{
    protected $signature = 'importar:facturas-2024
        {archivo? : Ruta al xlsx (default docs/Facturas AÑO 2024.xlsx)}
        {--dry-run : No guarda nada, solo muestra el resultado}';

    protected $description = 'Importa las facturas 2024 y el catálogo de clasificación (Actividad/Zona/Rubro) por CUIT desde "Facturas AÑO 2024.xlsx"';

    /** Texto de la hoja RUBROS (o de la columna Actividad de Facturas) → clave interna. */
    private const ACTIVIDADES = [
        'GENERAL' => 'general',
        'AGRICULTURA' => 'agricultura',
        'GANADERIA' => 'ganaderia',
        'INVERCIONES' => 'inversiones',
        'INVERSIONES' => 'inversiones',
    ];

    private const ZONAS = [
        'GENERAL' => 'general',
        'EL TREBOL' => 'el_trebol',
        'CORRIENTES' => 'corrientes',
    ];

    private const RUBROS = [
        'OTRO' => 'otro',
        'INSUMOS' => 'insumos',
        'VARIOS' => 'varios',
        'COMERCIALIZACION' => 'comercializacion',
        'MANTENIMIENTO' => 'mantenimiento',
        'REPARACIONES' => 'reparaciones',
        'LABORES/SERVICIOS' => 'labores_servicios',
        'SANIDAD' => 'sanidad',
        'TRANSPORTE' => 'transporte',
        'EMPLEADOS' => 'empleados',
        'ALIMENTO' => 'alimento',
        'ADMINISTRACION' => 'administracion',
        'ESPORADICOS' => 'esporadicos',
        'ASESORAMIENTO' => 'asesoramiento',
        'ALQUILERES' => 'alquileres',
        'BIEN DE CAPITAL' => 'bien_capital',
        'COMBUSTIBLE' => 'combustible',
    ];

    public function handle(): int
    {
        $path = $this->argument('archivo') ?? base_path('docs/Facturas AÑO 2024.xlsx');

        if (! file_exists($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $spreadsheet = IOFactory::load($path);

        $rubrosSheet = $spreadsheet->getSheetByName('RUBROS');
        $facturasSheet = $spreadsheet->getSheetByName('Facturas');

        if (! $rubrosSheet || ! $facturasSheet) {
            $this->error('El archivo no tiene las hojas "RUBROS" y/o "Facturas" esperadas.');

            return self::FAILURE;
        }

        $catalogo = $this->cargarCatalogo($rubrosSheet);
        $this->info('Catálogo CUIT → Actividad/Zona/Rubro: '.count($catalogo).' proveedores.');

        $empresas = Empresa::all()->keyBy(fn ($e) => strtoupper($e->razon_social));

        $rows = $facturasSheet->toArray(null, false, false, false);

        $creadas = 0;
        $yaExistentes = 0;
        $omitidas = 0;
        $proveedoresCreados = 0;
        $proveedoresClasificados = 0;

        foreach (array_slice($rows, 1, null, true) as $i => $row) {
            $filaExcel = $i + 1;
            $nombreEmpresa = strtoupper(trim((string) ($row[0] ?? '')));

            if ($nombreEmpresa === '') {
                continue;
            }

            $empresa = $empresas->get($nombreEmpresa);
            if (! $empresa) {
                $this->warn("Fila {$filaExcel}: empresa \"{$nombreEmpresa}\" no encontrada, se omite.");
                $omitidas++;

                continue;
            }

            $fecha = $this->parsearFecha($row[1] ?? null);
            if (! $fecha) {
                $this->warn("Fila {$filaExcel}: fecha inválida (\"{$row[1]}\"), se omite.");
                $omitidas++;

                continue;
            }

            $tipoComprobante = $this->mapearTipo((string) ($row[2] ?? ''));
            $puntoVenta = str_pad(preg_replace('/\D/', '', (string) ($row[3] ?? '')), 4, '0', STR_PAD_LEFT);
            $numero = str_pad(preg_replace('/\D/', '', (string) ($row[4] ?? '')), 8, '0', STR_PAD_LEFT);
            $numeroComprobante = "{$puntoVenta}-{$numero}";

            $cuit = $this->normalizarCuit((string) ($row[6] ?? ''));
            $nombreEmisor = trim((string) ($row[7] ?? ''));

            if ($cuit === '') {
                $this->warn("Fila {$filaExcel}: sin CUIT de emisor, se omite.");
                $omitidas++;

                continue;
            }

            // Columnas Q..V (índices 16..21) son fórmulas con los importes ya
            // convertidos a ARS (=+col_original*Tipo_Cambio); se lee el valor
            // cacheado porque PhpSpreadsheet no resuelve esas fórmulas.
            $ivaArs = $this->valorCelda($facturasSheet, 'U', $filaExcel);
            $totalArs = $this->valorCelda($facturasSheet, 'V', $filaExcel);
            $subtotalArs = round($totalArs - $ivaArs, 2);

            if ($totalArs <= 0) {
                $this->warn("Fila {$filaExcel}: importe total inválido, se omite.");
                $omitidas++;

                continue;
            }

            // ── Deduplicación: mismo criterio que MrbotService::parsearComprobante ──
            $yaExiste = Compra::withoutGlobalScope('empresa')
                ->where('id_empresa', $empresa->id)
                ->where('numero_comprobante', $numeroComprobante)
                ->whereHas('proveedor', function ($q) use ($cuit) {
                    $q->withoutGlobalScope('empresa')->where('cuit', $cuit);
                })
                ->exists();

            if ($yaExiste) {
                $yaExistentes++;

                continue;
            }

            // ── Proveedor: buscar por CUIT dentro de la empresa, o crear ──
            $proveedor = Proveedor::withoutGlobalScope('empresa')
                ->where('id_empresa', $empresa->id)
                ->where('cuit', $cuit)
                ->first();

            $clasificacion = $catalogo[$cuit] ?? null;

            if (! $proveedor) {
                if (! $dryRun) {
                    $proveedor = new Proveedor([
                        'nombre' => $nombreEmisor ?: $cuit,
                        'razon_social' => $nombreEmisor ?: null,
                        'cuit' => $cuit,
                        'rubro' => $clasificacion['rubro'] ?? 'otro',
                        'actividad' => $clasificacion['actividad'] ?? null,
                        'activo' => true,
                    ]);
                    $proveedor->id_empresa = $empresa->id;
                    $proveedor->save();
                }
                $proveedoresCreados++;
            } elseif ($proveedor->actividad === null && $clasificacion) {
                // Proveedor ya existente (por ej. de una sincronización ARCA previa)
                // pero sin clasificar todavía: se completa con el catálogo.
                if (! $dryRun) {
                    $proveedor->update([
                        'actividad' => $clasificacion['actividad'],
                        'rubro' => $clasificacion['rubro'] ?? $proveedor->rubro,
                    ]);
                }
                $proveedoresClasificados++;
            }

            if (! $dryRun) {
                $compra = new Compra([
                    'id_proveedor' => $proveedor?->id,
                    'id_establecimiento' => null,
                    'tipo_comprobante' => $tipoComprobante,
                    'numero_comprobante' => $numeroComprobante,
                    'fecha' => $fecha,
                    'fecha_vencimiento' => null,
                    'estado' => 'recibida',
                    'subtotal' => $subtotalArs,
                    'iva_porc' => $subtotalArs > 0 ? round(($ivaArs / $subtotalArs) * 100, 2) : 0,
                    'iva_importe' => $ivaArs,
                    'total' => $totalArs,
                    'stock_registrado' => false,
                    'observaciones' => "Importado desde Facturas AÑO 2024.xlsx (fila {$filaExcel})",
                    'actividad' => $proveedor?->actividad,
                    // La zona es un dato histórico de esta factura puntual (viene
                    // del catálogo CUIT→Zona de la planilla 2024), no del
                    // proveedor: la zona "actual" del proveedor ya no existe como
                    // concepto, ahora vive en Establecimiento.
                    'zona' => $clasificacion['zona'] ?? null,
                    'rubro' => $proveedor?->rubro !== 'otro' ? $proveedor?->rubro : null,
                ]);
                $compra->id_empresa = $empresa->id;
                $compra->save();
            }

            $creadas++;
        }

        $this->newLine();
        $this->line('─────────────────────────────────────');
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Compras creadas: {$creadas}");
        $this->line("Ya existentes (omitidas por duplicado): {$yaExistentes}");
        $this->line(($dryRun ? '[DRY RUN] ' : '')."Proveedores nuevos creados: {$proveedoresCreados}");
        $this->line(($dryRun ? '[DRY RUN] ' : '')."Proveedores existentes clasificados: {$proveedoresClasificados}");
        $this->info("Filas omitidas: {$omitidas}");

        return self::SUCCESS;
    }

    /** @return array<string, array{actividad: ?string, zona: ?string, rubro: ?string}> */
    private function cargarCatalogo(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, false, false, false);
        $catalogo = [];

        foreach (array_slice($rows, 1) as $row) {
            $cuit = $this->normalizarCuit((string) ($row[0] ?? ''));
            if ($cuit === '') {
                continue;
            }

            $actividadRaw = strtoupper(trim((string) ($row[2] ?? '')));
            $zonaRaw = strtoupper(trim((string) ($row[3] ?? '')));
            $rubroRaw = strtoupper(trim((string) ($row[4] ?? '')));

            $catalogo[$cuit] = [
                'actividad' => self::ACTIVIDADES[$actividadRaw] ?? null,
                'zona' => self::ZONAS[$zonaRaw] ?? null,
                'rubro' => self::RUBROS[$rubroRaw] ?? 'otro',
            ];
        }

        return $catalogo;
    }

    private function valorCelda(Worksheet $sheet, string $col, int $fila): float
    {
        $celda = $sheet->getCell("{$col}{$fila}");

        return (float) ($celda->isFormula() ? $celda->getOldCalculatedValue() : $celda->getValue());
    }

    /**
     * Traduce el tipo que informa ARCA al tipo interno del ERP. La tabla vive
     * en TipoComprobanteArca, compartida con los dos importadores.
     */
    private function mapearTipo(string $tipo): string
    {
        return TipoComprobanteArca::mapear($tipo);
    }

    private function normalizarCuit(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) === 11) {
            return substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10);
        }

        return $digits === '' ? '' : $raw;
    }

    private function parsearFecha(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Parte de la planilla tiene la fecha como texto "d/m/Y" y parte como
        // serial de Excel (número de días desde 1900), según cómo se haya
        // tipeado o pegado cada fila.
        if (is_numeric($value) && (float) $value > 10000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Exception) {
            }
        }

        $str = trim((string) $value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
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
