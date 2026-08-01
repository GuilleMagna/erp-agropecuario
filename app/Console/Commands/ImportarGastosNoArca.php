<?php

namespace App\Console\Commands;

use App\Models\GastoNoArca;
use App\Models\PagoGastoNoArca;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarGastosNoArca extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importar:gastos-no-arca
        {mes? : Mes a importar en formato Y-m (default: mes actual)}
        {--archivo= : Ruta al xlsx (default docs/CONTROL MENSUAL 2026.xlsx)}
        {--dry-run : No guarda nada, solo muestra el resultado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa los pagos de un mes desde la hoja NO ARCA de la planilla de control mensual';

    // Las primeras 6 columnas de mes de la hoja tienen el nombre del mes tipeado a
    // mano (texto), sin año: corresponden a Enero-Junio 2025. Todas las columnas
    // posteriores son celdas de fecha reales (se leen como serial de Excel).
    private const MESES_TEXTO_2025 = [
        'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4, 'MAYO' => 5, 'JUNIO' => 6,
    ];

    public function handle(): int
    {
        $mesInput = $this->argument('mes') ?? now()->format('Y-m');
        try {
            $mes = Carbon::createFromFormat('Y-m', $mesInput)->startOfMonth();
        } catch (\Exception) {
            $this->error("Formato de mes inválido: \"{$mesInput}\" (se espera Y-m, ej. 2026-07).");
            return self::FAILURE;
        }

        $path = $this->option('archivo') ?? base_path('docs/CONTROL MENSUAL 2026.xlsx');
        if (!file_exists($path)) {
            $this->error("No se encontró el archivo: {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('NO ARCA');
        if (!$sheet) {
            $this->error('La planilla no tiene una hoja "NO ARCA".');
            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, false, false, false);
        $header = $rows[0] ?? [];

        // Columnas en pares (IMPORTE, FECHA DE PAGO) empezando en el índice 1.
        $colImporte = null;
        $totalCols = count($header);
        for ($c = 1; $c < $totalCols; $c += 2) {
            $mesColumna = $this->parsearMesHeader($header[$c] ?? null);
            if ($mesColumna && $mesColumna->isSameMonth($mes) && $mesColumna->isSameYear($mes)) {
                $colImporte = $c;
                break;
            }
        }

        if ($colImporte === null) {
            $this->error("No se encontró una columna para el mes {$mes->format('Y-m')} en la hoja NO ARCA.");
            return self::FAILURE;
        }
        $colFecha = $colImporte + 1;

        $catalogo = GastoNoArca::all()->keyBy(fn ($g) => $this->normalizar($g->nombre));

        $creados = 0;
        $actualizados = 0;
        $omitidos = 0;

        foreach (array_slice($rows, 2, null, true) as $i => $row) {
            $nombre = trim((string) ($row[0] ?? ''));
            if ($nombre === '') {
                continue;
            }

            $importeCrudo = $row[$colImporte] ?? null;
            if ($importeCrudo === null || $importeCrudo === '') {
                continue;
            }

            $gasto = $catalogo->get($this->normalizar($nombre));
            if (!$gasto) {
                $this->warn("Fila " . ($i + 1) . ": gasto \"{$nombre}\" no encontrado en el catálogo, se omite.");
                $omitidos++;
                continue;
            }

            $notas = null;
            $importe = $this->parsearImporte($importeCrudo, $notas);
            if ($importe === null) {
                $this->warn("Fila " . ($i + 1) . " (\"{$nombre}\"): importe \"{$importeCrudo}\" no es numérico, se omite (revisar manualmente).");
                $omitidos++;
                continue;
            }

            $fechaPago = $this->parsearFecha($row[$colFecha] ?? null);

            $yaExistia = PagoGastoNoArca::where('id_gasto', $gasto->id)->where('mes', $mes->format('Y-m-d'))->exists();

            if (!$dryRun) {
                PagoGastoNoArca::updateOrCreate(
                    ['id_gasto' => $gasto->id, 'mes' => $mes->format('Y-m-d')],
                    ['importe' => $importe, 'fecha_pago' => $fechaPago, 'notas' => $notas]
                );
            }

            $yaExistia ? $actualizados++ : $creados++;
            if ($notas) {
                $this->warn("Fila " . ($i + 1) . " (\"{$nombre}\"): {$notas} (importe interpretado: {$importe}).");
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Mes importado: {$mes->format('Y-m')}");
        $this->info("{$prefix}Pagos creados: {$creados}");
        $this->info("{$prefix}Pagos actualizados: {$actualizados}");
        $this->info("Filas omitidas: {$omitidos}");

        return self::SUCCESS;
    }

    private function parsearMesHeader(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 10000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Exception) {
                return null;
            }
        }

        $mesNum = self::MESES_TEXTO_2025[strtoupper(trim((string) $value))] ?? null;
        if ($mesNum) {
            return Carbon::create(2025, $mesNum, 1);
        }

        return null;
    }

    private function normalizar(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', ' ', trim(Str::ascii($value))));
    }

    /**
     * Devuelve el importe numérico, o null si no se puede interpretar. Si el valor
     * crudo no era un número simple (ej. "$1755000 + $4800000"), completa $notas
     * con una aclaración para que quede registrado y se pueda revisar a mano.
     */
    private function parsearImporte(mixed $crudo, ?string &$notas): ?float
    {
        if (is_numeric($crudo)) {
            return (float) $crudo;
        }

        $str = trim((string) $crudo);

        preg_match_all('/[\d.]+(?:,\d+)?/', $str, $matches);
        $numeros = array_map(fn ($n) => (float) str_replace(',', '.', $n), $matches[0] ?? []);

        if (count($numeros) > 0 && preg_match('/^[\d.,\s$+]+$/', $str)) {
            $notas = "Valor original de la planilla: \"{$str}\", se sumaron los importes detectados";
            return array_sum($numeros);
        }

        return null;
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

        return null;
    }
}
