<?php

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\Empresa;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CorregirComprasUsd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'corregir:compras-usd {archivo? : Ruta al xlsx (default docs/CONTROL MENSUAL 2026.xlsx)} {--dry-run : No guarda nada, solo muestra el resultado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige compras importadas desde ARCA en moneda extranjera que se guardaron sin convertir a ARS (bug de MrbotService/ImportarComprasArca)';

    public function handle(): int
    {
        $path = $this->argument('archivo') ?? base_path('docs/CONTROL MENSUAL 2026.xlsx');

        if (!file_exists($path)) {
            $this->error("No se encontró el archivo: {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('ARCA comprobantes');

        if (!$sheet) {
            $this->error('La planilla no tiene una hoja "ARCA comprobantes".');
            return self::FAILURE;
        }

        // calculateFormulas=false + formatData=false: solo se leen columnas de datos
        // crudos (A-AE), no las convertidas a ARS (AF-AK, que sí son fórmulas) —
        // la conversión se recalcula acá mismo con moneda + tipo de cambio, igual
        // que el fix aplicado a MrbotService/ImportarComprasArca.
        $rows = $sheet->toArray(null, false, false, false);

        $empresas = Empresa::all()->keyBy(fn ($e) => strtoupper($e->razon_social));
        $aliasEmpresa = ['SOCIEDAD' => 'SUCESION'];

        $corregidas = 0;
        $sinMatch = 0;
        $sinCambios = 0;

        foreach (array_slice($rows, 1, null, true) as $row) {
            $monedaRaw = strtoupper(trim((string) ($row[13] ?? '')));
            if (in_array($monedaRaw, ['', 'ARS', '$', 'PESOS', 'PES'], true)) {
                continue;
            }

            $tipoCambio = (float) ($row[12] ?? 1);
            if ($tipoCambio <= 0) {
                continue;
            }

            $nombreEmpresa = strtoupper(trim((string) ($row[0] ?? '')));
            $claveEmpresa = $aliasEmpresa[$nombreEmpresa] ?? $nombreEmpresa;
            $empresa = $empresas->get($claveEmpresa);
            if (!$empresa) {
                $this->warn("Empresa \"{$nombreEmpresa}\" no encontrada, se omite fila (PV {$row[3]} N° {$row[4]}).");
                $sinMatch++;
                continue;
            }

            $pv = str_pad(preg_replace('/\D/', '', (string) ($row[3] ?? '')), 4, '0', STR_PAD_LEFT);
            $num = str_pad(preg_replace('/\D/', '', (string) ($row[4] ?? '')), 8, '0', STR_PAD_LEFT);
            $numeroComprobante = "{$pv}-{$num}";

            $cuitDigits = preg_replace('/\D/', '', (string) ($row[8] ?? ''));
            $cuit = strlen($cuitDigits) === 11
                ? substr($cuitDigits, 0, 2) . '-' . substr($cuitDigits, 2, 8) . '-' . substr($cuitDigits, 10)
                : $cuitDigits;

            // Neto/IVA/Total crudos (en la moneda original) × tipo de cambio = ARS correcto.
            $netoArs = ((float) ($row[25] ?? 0) + (float) ($row[26] ?? 0) + (float) ($row[27] ?? 0)) * $tipoCambio;
            $ivaArs = (float) ($row[29] ?? 0) * $tipoCambio;
            $totalArs = (float) ($row[30] ?? 0) * $tipoCambio;

            $compra = Compra::sinFiltroDeEmpresa()
                ->where('id_empresa', $empresa->id)
                ->where('numero_comprobante', $numeroComprobante)
                ->when($cuit, fn ($q) => $q->whereHas('proveedor', fn ($qq) => $qq->where('cuit', $cuit)))
                ->first();

            if (!$compra) {
                $this->warn("Sin match en compras: {$claveEmpresa} {$numeroComprobante} CUIT {$cuit}.");
                $sinMatch++;
                continue;
            }

            // Si ya está en el orden de magnitud correcto (ej. corrida repetida de este
            // comando), no tocar de nuevo.
            if (abs((float) $compra->total - $totalArs) < 1.0) {
                $sinCambios++;
                continue;
            }

            $ivaPorc = ($netoArs > 0 && $ivaArs > 0) ? round(($ivaArs / $netoArs) * 100, 2) : 0.0;

            $this->line(sprintf(
                '%s %s: total %s -> %s (tc %s)',
                $claveEmpresa,
                $numeroComprobante,
                number_format((float) $compra->total, 2, ',', '.'),
                number_format($totalArs, 2, ',', '.'),
                $tipoCambio
            ));

            if (!$dryRun) {
                $compra->subtotal = round($netoArs, 2);
                $compra->iva_importe = round($ivaArs, 2);
                $compra->iva_porc = $ivaPorc;
                $compra->total = round($totalArs, 2);
                $compra->save();
            }

            $corregidas++;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Compras corregidas: {$corregidas}");
        $this->info("Sin match en la base: {$sinMatch}");
        $this->info("Ya estaban correctas: {$sinCambios}");

        return self::SUCCESS;
    }
}
