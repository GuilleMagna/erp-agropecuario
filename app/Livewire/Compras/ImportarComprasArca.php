<?php

namespace App\Livewire\Compras;

use App\Models\Compra;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Support\TipoComprobanteArca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarComprasArca extends Component
{
    use WithFileUploads;

    public $archivo = null;

    public string $paso = 'subir'; // subir | previsualizar | resultado

    /**
     * Las filas del archivo NO viajan en el estado del componente: se guardan
     * en caché del servidor y acá queda sólo la clave.
     *
     * Con un export de 165 comprobantes el snapshot de Livewire pesaba más de
     * 100 KB, y ese snapshot va y vuelve en cada request. En el hosting eso
     * terminaba en un 419 al confirmar la importación.
     */
    public string $lote = '';

    public array $resumen = ['importadas' => 0, 'reclasificadas' => 0, 'duplicadas' => 0, 'errores' => 0];

    // Indices de columnas detectados del encabezado
    protected array $cols = [];


    // ─────────────────────────────────────────────────────────────
    // Paso 1: procesar el archivo subido
    // ─────────────────────────────────────────────────────────────

    public function procesarArchivo(): void
    {
        Gate::authorize('compras.arca.gestionar');

        $this->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'archivo.required' => 'Seleccioná un archivo.',
            'archivo.mimes' => 'Solo se aceptan archivos Excel (.xlsx, .xls) o CSV.',
            'archivo.max' => 'El archivo no puede superar 10 MB.',
        ]);

        $extension = strtolower($this->archivo->getClientOriginalName());
        $path = $this->archivo->getRealPath();

        try {
            $rows = str_ends_with($extension, '.csv')
                ? $this->leerCsv($path)
                : $this->leerExcel($path);
        } catch (\Exception $e) {
            $this->addError('archivo', 'No se pudo leer el archivo: '.$e->getMessage());

            return;
        }

        if (empty($rows)) {
            $this->addError('archivo', 'El archivo está vacío o no tiene datos legibles.');

            return;
        }

        $headerIndex = $this->encontrarFilaEncabezado($rows);

        if ($headerIndex === null) {
            $this->addError('archivo', 'No se encontró la fila de encabezados. Verificá que sea el export de ARCA (Mis Comprobantes).');

            return;
        }

        $this->cols = $this->mapearColumnas($rows[$headerIndex]);

        if ($this->cols['fecha'] === null || $this->cols['cuit'] === null || $this->cols['total'] === null) {
            $this->addError('archivo', 'Faltan columnas requeridas (Fecha, CUIT, Importe Total). El archivo no coincide con el formato esperado de ARCA.');

            return;
        }

        if ($error = $this->empresaDelArchivoNoCoincide($rows, $headerIndex)) {
            $this->addError('archivo', $error);

            return;
        }

        $filas = [];
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $fila = $this->parsearFila($row);
            if ($fila !== null) {
                $filas[] = $fila;
            }
        }

        $this->guardarFilas($filas);

        if (empty($filas)) {
            $this->addError('archivo', 'No se encontraron filas con datos válidos en el archivo.');

            return;
        }

        $this->paso = 'previsualizar';
    }

    // ─────────────────────────────────────────────────────────────
    // Paso 2: confirmar e importar
    // ─────────────────────────────────────────────────────────────

    public function confirmarImport(): void
    {
        Gate::authorize('compras.arca.gestionar');

        $importadas = 0;
        $duplicadas = 0;
        $errores = 0;
        $reclasificadas = 0;

        $filas = $this->filas();
        foreach ($filas as &$fila) {
            if ($fila['estado'] === 'duplicado') {
                $duplicadas++;

                continue;
            }
            if ($fila['estado'] === 'error') {
                $errores++;

                continue;
            }

            // Comprobante ya cargado con un tipo distinto del que informa ARCA:
            // se corrige el tipo y los importes (una nota de crédito guardada
            // como "otro" estaba en positivo y tiene que pasar a negativo).
            if ($fila['estado'] === 'reclasificar') {
                $compra = Compra::find($fila['compra_id']);
                if (! $compra) {
                    $errores++;

                    continue;
                }

                // Lo que se corrige es el tipo y el signo. Los importes del
                // archivo sólo pisan a los ya cargados si vienen con valor: si
                // el export no trae las columnas de IVA desglosadas por
                // alícuota, reescribirlos a ciegas borraría el crédito fiscal
                // de un comprobante que lo tenía bien.
                $signo = in_array($fila['tipo_comprobante'], Compra::TIPOS_NEGATIVOS, true) ? -1 : 1;
                $tomar = fn (float $archivo, float $actual) => abs($archivo) > 0 ? abs($archivo) : abs($actual);

                $compra->update([
                    'tipo_comprobante' => $fila['tipo_comprobante'],
                    'subtotal' => $signo * $tomar((float) $fila['subtotal'], (float) $compra->subtotal),
                    'iva_importe' => $signo * $tomar((float) $fila['iva_importe'], (float) $compra->iva_importe),
                    'total' => $signo * $tomar((float) $fila['total'], (float) $compra->total),
                    'observaciones' => trim(($compra->observaciones ? $compra->observaciones.' | ' : '')
                        .'Reclasificado desde ARCA: era "'.$fila['tipo_actual'].'".'),
                ]);
                $reclasificadas++;

                continue;
            }

            try {
                // Buscar o crear proveedor por CUIT
                $proveedor = null;
                if (! empty($fila['cuit'])) {
                    $proveedor = Proveedor::where('cuit', $fila['cuit'])->first();
                    if (! $proveedor) {
                        $proveedor = Proveedor::create([
                            'nombre' => $fila['nombre'] ?: $fila['cuit'],
                            'razon_social' => $fila['nombre'] ?: null,
                            'cuit' => $fila['cuit'],
                            'rubro' => 'otro',
                            'activo' => true,
                        ]);
                        $fila['proveedor_creado'] = true;
                    }
                }

                Compra::create([
                    'id_proveedor' => $proveedor?->id,
                    'id_establecimiento' => null,
                    'tipo_comprobante' => $fila['tipo_comprobante'],
                    'numero_comprobante' => $fila['numero_comprobante'],
                    'fecha' => $fila['fecha'],
                    'fecha_vencimiento' => null,
                    'estado' => 'recibida',
                    'subtotal' => $fila['subtotal'],
                    'iva_porc' => $fila['iva_porc'],
                    'iva_importe' => $fila['iva_importe'],
                    'total' => $fila['total'],
                    'stock_registrado' => false,
                    'observaciones' => 'Importado desde ARCA',
                    // Heredado del proveedor si ya tiene clasificación cargada.
                    // La zona no se hereda acá: es del establecimiento, y esta
                    // importación no elige uno (queda para completar a mano).
                    'actividad' => $proveedor?->actividad !== null ? $proveedor->actividad : null,
                    'rubro' => $proveedor?->actividad !== null ? $proveedor->rubro : null,
                ]);

                $fila['estado'] = 'importado';
                $importadas++;
            } catch (\Exception $e) {
                $fila['estado'] = 'error';
                $fila['error_msg'] = $e->getMessage();
                $errores++;
            }
        }
        unset($fila);
        $this->guardarFilas($filas);

        $this->resumen = compact('importadas', 'reclasificadas', 'duplicadas', 'errores');
        $this->paso = 'resultado';
    }

    public function reiniciar(): void
    {
        $this->olvidarFilas();
        $this->reset(['archivo', 'resumen']);
        $this->cols = [];
        $this->paso = 'subir';
        $this->resetValidation();
    }

    // ─────────────────────────────────────────────────────────────
    // Las filas del archivo, fuera del estado del componente
    // ─────────────────────────────────────────────────────────────

    /**
     * Los comprobantes se cargan en la empresa que está seleccionada arriba, y
     * es fácil olvidarse de cambiarla: así fue a parar el export de WILMAR 2024
     * dentro de ELVIO. El export dice de quién es (el receptor), así que se
     * compara antes de dejar seguir.
     *
     * Devuelve el mensaje de error, o null si está todo bien o no se puede
     * determinar de quién es el archivo.
     */
    private function empresaDelArchivoNoCoincide(array $rows, int $headerIndex): ?string
    {
        // resolverEmpresaActiva() vive en el trait PerteneceAEmpresa, que usa
        // Compra; Empresa no lo tiene porque no pertenece a otra empresa.
        $activa = Empresa::find(Compra::resolverEmpresaActiva());
        if (! $activa) {
            return null;
        }

        // El CUIT del receptor puede venir en una columna o en el nombre del
        // archivo ("Mis Comprobantes Recibidos - CUIT 20135430138.xlsx").
        $delArchivo = null;
        if ($this->cols['cuit_receptor'] !== null) {
            for ($i = $headerIndex + 1; $i < count($rows); $i++) {
                $valor = preg_replace('/\D/', '', (string) ($rows[$i][$this->cols['cuit_receptor']] ?? ''));
                if (strlen($valor) === 11) {
                    $delArchivo = $valor;
                    break;
                }
            }
        }

        if (! $delArchivo && $this->archivo && preg_match('/(\d{11})/', $this->archivo->getClientOriginalName(), $m)) {
            $delArchivo = $m[1];
        }

        if (! $delArchivo || $delArchivo === preg_replace('/\D/', '', $activa->cuit)) {
            return null;
        }

        $duena = Empresa::whereRaw("REPLACE(cuit, '-', '') = ?", [$delArchivo])->first();

        return sprintf(
            'El archivo es de %s (CUIT %s) y la empresa seleccionada es %s. '.
            'Cambiá la empresa en el selector de arriba antes de importar, o los comprobantes van a quedar en la empresa equivocada.',
            $duena?->razon_social ?? 'otro CUIT',
            $delArchivo,
            $activa->razon_social
        );
    }

    /** Filas del archivo que se está importando. */
    private function filas(): array
    {
        return $this->lote ? Cache::get($this->claveLote(), []) : [];
    }

    private function guardarFilas(array $filas): void
    {
        $this->lote = $this->lote ?: (string) Str::uuid();
        Cache::put($this->claveLote(), $filas, now()->addHour());
    }

    private function olvidarFilas(): void
    {
        if ($this->lote) {
            Cache::forget($this->claveLote());
        }
        $this->lote = '';
    }

    private function claveLote(): string
    {
        return 'importar-arca:'.$this->lote;
    }

    // ─────────────────────────────────────────────────────────────
    // Lectura de archivos
    // ─────────────────────────────────────────────────────────────

    private function leerExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    private function leerCsv(string $path): array
    {
        $content = file_get_contents($path);
        $separator = substr_count($content, ';') >= substr_count($content, ',') ? ';' : ',';
        $rows = [];

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $separator)) !== false) {
                $rows[] = array_map(fn ($v) => trim($v, " \t\r\n\""), $row);
            }
            fclose($handle);
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────
    // Detección de encabezados
    // ─────────────────────────────────────────────────────────────

    private function encontrarFilaEncabezado(array $rows): ?int
    {
        $indicadores = ['cuit', 'fecha', 'total', 'tipo', 'importe', 'denominac'];

        foreach (array_slice($rows, 0, 10, true) as $i => $row) {
            $celdas = array_map(fn ($v) => strtolower(trim((string) ($v ?? ''))), $row);
            $hits = 0;
            foreach ($indicadores as $ind) {
                foreach ($celdas as $celda) {
                    if (str_contains($celda, $ind)) {
                        $hits++;
                        break;
                    }
                }
            }
            if ($hits >= 3) {
                return $i;
            }
        }

        return null;
    }

    private function mapearColumnas(array $headerRow): array
    {
        $cols = array_fill_keys([
            'fecha', 'tipo', 'pv', 'numero', 'moneda', 'tc',
            'cuit', 'nombre', 'neto', 'neto_ng', 'exento',
            'iva_21', 'iva_105', 'iva_27', 'iva_25', 'iva_5',
            'otros', 'total', 'cuit_receptor',
        ], null);

        foreach ($headerRow as $i => $header) {
            $h = strtolower(preg_replace('/\s+/', ' ', trim((string) ($header ?? ''))));

            if (str_contains($h, 'fecha')) {
                $cols['fecha'] = $i;
            } elseif (in_array($h, ['tipo', 'tipo comprobante', 'tipo de comprobante'])) {
                $cols['tipo'] = $i;
            } elseif (str_contains($h, 'punto de venta') || str_contains($h, 'pto. venta') || str_contains($h, 'pto venta')) {
                $cols['pv'] = $i;
            }
            // CUIT antes que numero: "Nro. Doc. Emisor" no tiene la palabra "cuit"
            // El receptor es la empresa dueña del archivo: sirve para avisar si
            // se está importando con la empresa equivocada seleccionada.
            elseif (str_contains($h, 'nro') && str_contains($h, 'doc') && str_contains($h, 'receptor')) {
                $cols['cuit_receptor'] = $i;
            } elseif (str_contains($h, 'cuit') ||
                    (str_contains($h, 'nro') && str_contains($h, 'doc') && str_contains($h, 'emisor'))) {
                $cols['cuit'] = $i;
            }
            // Excluir "Nro. Doc. Emisor/Receptor" del campo numero
            elseif ((str_contains($h, 'número desde') || str_contains($h, 'numero desde')
                  || str_contains($h, 'nro.') || in_array($h, ['número', 'numero']))
                  && ! str_contains($h, 'doc')) {
                $cols['numero'] = $i;
            } elseif (str_contains($h, 'moneda')) {
                $cols['moneda'] = $i;
            } elseif (str_contains($h, 'cambio')) {
                $cols['tc'] = $i;
            } elseif (str_contains($h, 'denominac') || str_contains($h, 'razón social')
                 || str_contains($h, 'razon social')) {
                $cols['nombre'] = $i;
            }
            // "Neto Gravado Total" o "Neto Gravado" clásico; excluir "Neto Grav. IVA X%"
            elseif (str_contains($h, 'neto gravado') && ! str_contains($h, 'no') && ! str_contains($h, 'iva')) {
                $cols['neto'] = $i;
            } elseif (str_contains($h, 'no gravado') || str_contains($h, 'neto no')) {
                $cols['neto_ng'] = $i;
            } elseif (str_contains($h, 'exent')) {
                $cols['exento'] = $i;
            }
            // IVA: exigir 'iva' en el header y excluir columnas "Neto Grav. IVA X%"
            elseif (str_contains($h, 'iva') && str_contains($h, '21') && ! str_contains($h, 'neto')) {
                $cols['iva_21'] = $i;
            } elseif (str_contains($h, 'iva') && str_contains($h, '10') && ! str_contains($h, 'neto')) {
                $cols['iva_105'] = $i;
            } elseif (str_contains($h, 'iva') && str_contains($h, '27') && ! str_contains($h, 'neto')) {
                $cols['iva_27'] = $i;
            } elseif ((str_contains($h, '2,5') || str_contains($h, '2.5'))
                 && str_contains($h, 'iva') && ! str_contains($h, 'neto')) {
                $cols['iva_25'] = $i;
            } elseif (preg_match('/\b5%?\b/', $h) && str_contains($h, 'iva') && ! str_contains($h, 'neto')) {
                $cols['iva_5'] = $i;
            } elseif (str_contains($h, 'otros tribut') || str_contains($h, 'percep')) {
                $cols['otros'] = $i;
            }
            // Excluir "Total IVA"; "Imp. Total" es el que nos interesa
            elseif (str_contains($h, 'total') && ! str_contains($h, 'neto') && ! str_contains($h, 'iva')) {
                $cols['total'] = $i;
            }
        }

        return $cols;
    }

    // ─────────────────────────────────────────────────────────────
    // Parseo de fila
    // ─────────────────────────────────────────────────────────────

    private function parsearFila(array $row): ?array
    {
        $c = $this->cols;

        $cuitRaw = $c['cuit'] !== null ? trim((string) ($row[$c['cuit']] ?? '')) : '';
        $cuit = $this->normalizarCuit($cuitRaw);

        $totalRaw = $c['total'] !== null ? ($row[$c['total']] ?? null) : null;
        $total = $this->parsearImporte($totalRaw);

        // Fila sin datos relevantes
        if (empty($cuit) && ($total === null || $total === 0.0)) {
            return null;
        }

        // Fecha
        $fechaRaw = $c['fecha'] !== null ? ($row[$c['fecha']] ?? null) : null;
        $fecha = $this->parsearFecha($fechaRaw);

        // Tipo de comprobante
        $tipoRaw = $c['tipo'] !== null ? trim((string) ($row[$c['tipo']] ?? '')) : '';
        $tipoComprobante = $this->mapearTipo($tipoRaw);

        // Número de comprobante: XXXX-XXXXXXXX
        $pvRaw = $c['pv'] !== null ? (string) ($row[$c['pv']] ?? '0') : '0';
        $numRaw = $c['numero'] !== null ? (string) ($row[$c['numero']] ?? '0') : '0';
        $pv = str_pad(preg_replace('/\D/', '', $pvRaw), 4, '0', STR_PAD_LEFT);
        $num = str_pad(preg_replace('/\D/', '', $numRaw), 8, '0', STR_PAD_LEFT);
        $numeroComprobante = "{$pv}-{$num}";

        // Nombre del proveedor
        $nombre = $c['nombre'] !== null ? trim((string) ($row[$c['nombre']] ?? '')) : '';

        // Moneda y tipo de cambio: ARCA informa el neto/IVA/total en la moneda
        // original del comprobante (por ej. USD). Si no se convierte a ARS acá,
        // un comprobante en dólares queda guardado con el valor numérico del
        // dólar tratado como si fuera pesos.
        $monedaRaw = $c['moneda'] !== null ? strtoupper(trim((string) ($row[$c['moneda']] ?? ''))) : '';
        $tipoCambio = $c['tc'] !== null ? ($this->parsearImporte($row[$c['tc']] ?? null) ?? 1.0) : 1.0;
        $factorConversion = (! in_array($monedaRaw, ['', 'ARS', '$', 'PESOS', 'PES'], true) && $tipoCambio > 0)
            ? $tipoCambio
            : 1.0;
        if ($total !== null) {
            $total *= $factorConversion;
        }

        // Importes (ya convertidos a ARS)
        $neto = (($c['neto'] !== null ? $this->parsearImporte($row[$c['neto']] ?? null) : null) ?? 0.0) * $factorConversion;
        $netoNg = (($c['neto_ng'] !== null ? $this->parsearImporte($row[$c['neto_ng']] ?? null) : null) ?? 0.0) * $factorConversion;
        $exento = (($c['exento'] !== null ? $this->parsearImporte($row[$c['exento']] ?? null) : null) ?? 0.0) * $factorConversion;

        $iva21 = (($c['iva_21'] !== null ? $this->parsearImporte($row[$c['iva_21']] ?? null) : null) ?? 0.0) * $factorConversion;
        $iva105 = (($c['iva_105'] !== null ? $this->parsearImporte($row[$c['iva_105']] ?? null) : null) ?? 0.0) * $factorConversion;
        $iva27 = (($c['iva_27'] !== null ? $this->parsearImporte($row[$c['iva_27']] ?? null) : null) ?? 0.0) * $factorConversion;
        $iva25 = (($c['iva_25'] !== null ? $this->parsearImporte($row[$c['iva_25']] ?? null) : null) ?? 0.0) * $factorConversion;
        $iva5 = (($c['iva_5'] !== null ? $this->parsearImporte($row[$c['iva_5']] ?? null) : null) ?? 0.0) * $factorConversion;

        $ivaTotal = round($iva21 + $iva105 + $iva27 + $iva25 + $iva5, 2);
        $subtotal = round($neto + $netoNg + $exento, 2);

        // Si no hay columnas de neto, estimar subtotal = total - IVA
        if ($subtotal === 0.0 && $total !== null && $ivaTotal > 0) {
            $subtotal = round($total - $ivaTotal, 2);
        } elseif ($subtotal === 0.0 && $total !== null) {
            $subtotal = $total;
        }

        // IVA % aproximado
        $ivaPorc = ($subtotal > 0 && $ivaTotal > 0)
            ? round(($ivaTotal / $subtotal) * 100, 2)
            : 0.0;

        // Notas de crédito: se guardan en negativo para que resten del total
        // de compras y del crédito fiscal. ARCA las informa en positivo.
        if (in_array($tipoComprobante, Compra::TIPOS_NEGATIVOS, true)) {
            $subtotal = -abs($subtotal);
            $ivaTotal = -abs($ivaTotal);
            if ($total !== null) {
                $total = -abs($total);
            }
        }

        // Verificar duplicado: mismo número + mismo CUIT
        $yaExiste = false;
        $existente = null;
        if (! empty($cuit) && $numeroComprobante !== '0000-00000000') {
            $existente = Compra::where('numero_comprobante', $numeroComprobante)
                ->whereHas('proveedor', fn ($q) => $q->where('cuit', $cuit))
                ->first();
            $yaExiste = $existente !== null;
        }

        // ¿El comprobante ya cargado quedó con un tipo distinto del que informa
        // ARCA? Pasa con todo lo que se importó antes de que el mapeo separara
        // notas de crédito y débito: quedaron como "otro" y en positivo, así que
        // las de crédito suman en vez de restar. En ese caso no se saltea como
        // duplicado, se ofrece reclasificarlo.
        $reclasificar = false;
        if ($existente && $existente->tipo_comprobante !== $tipoComprobante) {
            $reclasificar = true;
        }

        // Determinar estado
        $estado = 'nuevo';
        $errorMsg = null;

        if ($reclasificar) {
            $estado = 'reclasificar';
        } elseif ($yaExiste) {
            $estado = 'duplicado';
        } elseif (! $fecha) {
            $estado = 'error';
            $errorMsg = 'Fecha inválida ('.$fechaRaw.')';
        } elseif ($total === null || abs($total) < 0.005) {
            $estado = 'error';
            $errorMsg = 'Importe total inválido';
        }

        return [
            'fecha' => $fecha,
            'tipo_comprobante' => $tipoComprobante,
            'numero_comprobante' => $numeroComprobante,
            'cuit' => $cuit,
            'nombre' => $nombre,
            'subtotal' => $subtotal,
            'iva_porc' => $ivaPorc,
            'iva_importe' => $ivaTotal,
            'total' => round($total ?? 0, 2),
            'estado' => $estado,
            'error_msg' => $errorMsg,
            'proveedor_creado' => false,
            'compra_id' => $existente?->id,
            'tipo_actual' => $existente?->tipo_comprobante,
            'total_actual' => $existente ? (float) $existente->total : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers de parseo
    // ─────────────────────────────────────────────────────────────

    private function parsearFecha(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Número de serie de Excel
        if (is_numeric($value) && (float) $value > 10000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Exception) {
            }
        }

        $str = trim((string) $value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'd/m/y'] as $fmt) {
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

    private function parsearImporte(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-') {
            return 0.0;
        }

        // Float con punto decimal inglés (ej: "88548.6") — excluir "1.234" que
        // podría ser miles argentinos (exactamente 3 dígitos tras el punto)
        if (is_numeric($str) && ! preg_match('/^\d+\.\d{3}$/', $str)) {
            return (float) $str;
        }

        // Formato argentino: 1.234,56 → punto miles, coma decimal
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace(',', '.', str_replace('.', '', $str));
            if (! is_numeric($str)) {
                $str = str_replace(',', '.', (string) $value);
            }
        }

        return is_numeric($str) ? (float) $str : null;
    }

    private function normalizarCuit(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) === 11) {
            return substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10);
        }

        return $raw; // devolver tal cual si no tiene 11 dígitos
    }

    /**
     * Traduce el tipo que informa ARCA al tipo interno del ERP. La tabla vive
     * en TipoComprobanteArca, compartida con MrbotService.
     */
    private function mapearTipo(string $tipo): string
    {
        return TipoComprobanteArca::mapear($tipo);
    }

    public function render()
    {
        // Las filas se le pasan a la vista desde acá y no como propiedad del
        // componente, para que no viajen en el snapshot de Livewire.
        return view('livewire.compras.importar-compras-arca', [
            'filasParsadas' => $this->filas(),
        ]);
    }
}
