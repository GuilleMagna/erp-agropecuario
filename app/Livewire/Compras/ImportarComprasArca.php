<?php

namespace App\Livewire\Compras;

use App\Models\Compra;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarComprasArca extends Component
{
    use WithFileUploads;

    public $archivo = null;
    public string $paso = 'subir'; // subir | previsualizar | resultado

    public array $filasParsadas = [];
    public array $resumen = ['importadas' => 0, 'duplicadas' => 0, 'errores' => 0];

    // Indices de columnas detectados del encabezado
    protected array $cols = [];

    // Mapa de códigos ARCA → tipo_comprobante interno
    const MAPA_TIPOS = [
        // Por código numérico (con y sin ceros a la izquierda)
        '1' => 'factura_a', '01' => 'factura_a', '001' => 'factura_a',
        '2' => 'otro',      '02' => 'otro',       '002' => 'otro',   // ND A
        '3' => 'otro',      '03' => 'otro',       '003' => 'otro',   // NC A
        '6' => 'factura_b', '06' => 'factura_b',  '006' => 'factura_b',
        '7' => 'otro',      '07' => 'otro',       '007' => 'otro',   // ND B
        '8' => 'otro',      '08' => 'otro',       '008' => 'otro',   // NC B
        '11' => 'factura_c', '011' => 'factura_c',
        '12' => 'otro',      '012' => 'otro',     // ND C
        '13' => 'otro',      '013' => 'otro',     // NC C
        '51' => 'factura_a', '051' => 'factura_a', // M
        '81' => 'ticket',    '081' => 'ticket',
        '82' => 'ticket',    '082' => 'ticket',
        '83' => 'ticket',    '083' => 'ticket',
        // Por texto
        'factura a'          => 'factura_a',
        'factura b'          => 'factura_b',
        'factura c'          => 'factura_c',
        'factura m'          => 'factura_a',
        'ticket'             => 'ticket',
        'ticket factura a'   => 'ticket',
        'ticket factura b'   => 'ticket',
        'recibo'             => 'recibo',
        'nota de débito a'   => 'otro',
        'nota de débito b'   => 'otro',
        'nota de crédito a'  => 'otro',
        'nota de crédito b'  => 'otro',
    ];

    // ─────────────────────────────────────────────────────────────
    // Paso 1: procesar el archivo subido
    // ─────────────────────────────────────────────────────────────

    public function procesarArchivo(): void
    {
        Gate::authorize('compras.crear');

        $this->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'archivo.required' => 'Seleccioná un archivo.',
            'archivo.mimes'    => 'Solo se aceptan archivos Excel (.xlsx, .xls) o CSV.',
            'archivo.max'      => 'El archivo no puede superar 10 MB.',
        ]);

        $extension = strtolower($this->archivo->getClientOriginalName());
        $path      = $this->archivo->getRealPath();

        try {
            $rows = str_ends_with($extension, '.csv')
                ? $this->leerCsv($path)
                : $this->leerExcel($path);
        } catch (\Exception $e) {
            $this->addError('archivo', 'No se pudo leer el archivo: ' . $e->getMessage());
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

        $this->filasParsadas = [];
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!array_filter($row, fn($v) => $v !== null && $v !== '')) continue;
            $fila = $this->parsearFila($row);
            if ($fila !== null) {
                $this->filasParsadas[] = $fila;
            }
        }

        if (empty($this->filasParsadas)) {
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
        Gate::authorize('compras.crear');

        $importadas = 0;
        $duplicadas = 0;
        $errores    = 0;

        foreach ($this->filasParsadas as &$fila) {
            if ($fila['estado'] === 'duplicado') { $duplicadas++; continue; }
            if ($fila['estado'] === 'error')     { $errores++;    continue; }

            try {
                // Buscar o crear proveedor por CUIT
                $proveedor = null;
                if (!empty($fila['cuit'])) {
                    $proveedor = Proveedor::where('cuit', $fila['cuit'])->first();
                    if (!$proveedor) {
                        $proveedor = Proveedor::create([
                            'nombre'       => $fila['nombre'] ?: $fila['cuit'],
                            'razon_social' => $fila['nombre'] ?: null,
                            'cuit'         => $fila['cuit'],
                            'rubro'        => 'otro',
                            'activo'       => true,
                        ]);
                        $fila['proveedor_creado'] = true;
                    }
                }

                Compra::create([
                    'id_proveedor'       => $proveedor?->id,
                    'id_establecimiento' => null,
                    'tipo_comprobante'   => $fila['tipo_comprobante'],
                    'numero_comprobante' => $fila['numero_comprobante'],
                    'fecha'              => $fila['fecha'],
                    'fecha_vencimiento'  => null,
                    'estado'             => 'recibida',
                    'subtotal'           => $fila['subtotal'],
                    'iva_porc'           => $fila['iva_porc'],
                    'iva_importe'        => $fila['iva_importe'],
                    'total'              => $fila['total'],
                    'stock_registrado'   => false,
                    'observaciones'      => 'Importado desde ARCA',
                ]);

                $fila['estado'] = 'importado';
                $importadas++;
            } catch (\Exception $e) {
                $fila['estado']    = 'error';
                $fila['error_msg'] = $e->getMessage();
                $errores++;
            }
        }
        unset($fila);

        $this->resumen = compact('importadas', 'duplicadas', 'errores');
        $this->paso    = 'resultado';
    }

    public function reiniciar(): void
    {
        $this->reset(['archivo', 'filasParsadas', 'resumen']);
        $this->cols = [];
        $this->paso = 'subir';
        $this->resetValidation();
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
        $content   = file_get_contents($path);
        $separator = substr_count($content, ';') >= substr_count($content, ',') ? ';' : ',';
        $rows      = [];

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $separator)) !== false) {
                $rows[] = array_map(fn($v) => trim($v, " \t\r\n\""), $row);
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
            $celdas = array_map(fn($v) => strtolower(trim((string)($v ?? ''))), $row);
            $hits   = 0;
            foreach ($indicadores as $ind) {
                foreach ($celdas as $celda) {
                    if (str_contains($celda, $ind)) { $hits++; break; }
                }
            }
            if ($hits >= 3) return $i;
        }
        return null;
    }

    private function mapearColumnas(array $headerRow): array
    {
        $cols = array_fill_keys([
            'fecha', 'tipo', 'pv', 'numero', 'moneda', 'tc',
            'cuit', 'nombre', 'neto', 'neto_ng', 'exento',
            'iva_21', 'iva_105', 'iva_27', 'iva_25', 'iva_5',
            'otros', 'total',
        ], null);

        foreach ($headerRow as $i => $header) {
            $h = strtolower(preg_replace('/\s+/', ' ', trim((string)($header ?? ''))));

            if (str_contains($h, 'fecha'))
                $cols['fecha'] = $i;
            elseif (in_array($h, ['tipo', 'tipo comprobante', 'tipo de comprobante']))
                $cols['tipo'] = $i;
            elseif (str_contains($h, 'punto de venta') || str_contains($h, 'pto. venta') || str_contains($h, 'pto venta'))
                $cols['pv'] = $i;
            // CUIT antes que numero: "Nro. Doc. Emisor" no tiene la palabra "cuit"
            elseif (str_contains($h, 'cuit') ||
                    (str_contains($h, 'nro') && str_contains($h, 'doc') && str_contains($h, 'emisor')))
                $cols['cuit'] = $i;
            // Excluir "Nro. Doc. Emisor/Receptor" del campo numero
            elseif ((str_contains($h, 'número desde') || str_contains($h, 'numero desde')
                  || str_contains($h, 'nro.') || in_array($h, ['número', 'numero']))
                  && !str_contains($h, 'doc'))
                $cols['numero'] = $i;
            elseif (str_contains($h, 'moneda'))
                $cols['moneda'] = $i;
            elseif (str_contains($h, 'cambio'))
                $cols['tc'] = $i;
            elseif (str_contains($h, 'denominac') || str_contains($h, 'razón social')
                 || str_contains($h, 'razon social'))
                $cols['nombre'] = $i;
            // "Neto Gravado Total" o "Neto Gravado" clásico; excluir "Neto Grav. IVA X%"
            elseif (str_contains($h, 'neto gravado') && !str_contains($h, 'no') && !str_contains($h, 'iva'))
                $cols['neto'] = $i;
            elseif (str_contains($h, 'no gravado') || str_contains($h, 'neto no'))
                $cols['neto_ng'] = $i;
            elseif (str_contains($h, 'exent'))
                $cols['exento'] = $i;
            // IVA: exigir 'iva' en el header y excluir columnas "Neto Grav. IVA X%"
            elseif (str_contains($h, 'iva') && str_contains($h, '21') && !str_contains($h, 'neto'))
                $cols['iva_21'] = $i;
            elseif (str_contains($h, 'iva') && str_contains($h, '10') && !str_contains($h, 'neto'))
                $cols['iva_105'] = $i;
            elseif (str_contains($h, 'iva') && str_contains($h, '27') && !str_contains($h, 'neto'))
                $cols['iva_27'] = $i;
            elseif ((str_contains($h, '2,5') || str_contains($h, '2.5'))
                 && str_contains($h, 'iva') && !str_contains($h, 'neto'))
                $cols['iva_25'] = $i;
            elseif (preg_match('/\b5%?\b/', $h) && str_contains($h, 'iva') && !str_contains($h, 'neto'))
                $cols['iva_5'] = $i;
            elseif (str_contains($h, 'otros tribut') || str_contains($h, 'percep'))
                $cols['otros'] = $i;
            // Excluir "Total IVA"; "Imp. Total" es el que nos interesa
            elseif (str_contains($h, 'total') && !str_contains($h, 'neto') && !str_contains($h, 'iva'))
                $cols['total'] = $i;
        }

        return $cols;
    }

    // ─────────────────────────────────────────────────────────────
    // Parseo de fila
    // ─────────────────────────────────────────────────────────────

    private function parsearFila(array $row): ?array
    {
        $c = $this->cols;

        $cuitRaw = $c['cuit'] !== null ? trim((string)($row[$c['cuit']] ?? '')) : '';
        $cuit    = $this->normalizarCuit($cuitRaw);

        $totalRaw = $c['total'] !== null ? ($row[$c['total']] ?? null) : null;
        $total    = $this->parsearImporte($totalRaw);

        // Fila sin datos relevantes
        if (empty($cuit) && ($total === null || $total === 0.0)) return null;

        // Fecha
        $fechaRaw = $c['fecha'] !== null ? ($row[$c['fecha']] ?? null) : null;
        $fecha    = $this->parsearFecha($fechaRaw);

        // Tipo de comprobante
        $tipoRaw         = $c['tipo']   !== null ? trim((string)($row[$c['tipo']]   ?? '')) : '';
        $tipoComprobante = $this->mapearTipo($tipoRaw);

        // Número de comprobante: XXXX-XXXXXXXX
        $pvRaw  = $c['pv']     !== null ? (string)($row[$c['pv']]     ?? '0') : '0';
        $numRaw = $c['numero'] !== null ? (string)($row[$c['numero']] ?? '0') : '0';
        $pv     = str_pad(preg_replace('/\D/', '', $pvRaw),  4, '0', STR_PAD_LEFT);
        $num    = str_pad(preg_replace('/\D/', '', $numRaw), 8, '0', STR_PAD_LEFT);
        $numeroComprobante = "{$pv}-{$num}";

        // Nombre del proveedor
        $nombre = $c['nombre'] !== null ? trim((string)($row[$c['nombre']] ?? '')) : '';

        // Importes
        $neto   = ($c['neto']    !== null ? $this->parsearImporte($row[$c['neto']]    ?? null) : null) ?? 0.0;
        $netoNg = ($c['neto_ng'] !== null ? $this->parsearImporte($row[$c['neto_ng']] ?? null) : null) ?? 0.0;
        $exento = ($c['exento']  !== null ? $this->parsearImporte($row[$c['exento']]  ?? null) : null) ?? 0.0;

        $iva21  = ($c['iva_21']  !== null ? $this->parsearImporte($row[$c['iva_21']]  ?? null) : null) ?? 0.0;
        $iva105 = ($c['iva_105'] !== null ? $this->parsearImporte($row[$c['iva_105']] ?? null) : null) ?? 0.0;
        $iva27  = ($c['iva_27']  !== null ? $this->parsearImporte($row[$c['iva_27']]  ?? null) : null) ?? 0.0;
        $iva25  = ($c['iva_25']  !== null ? $this->parsearImporte($row[$c['iva_25']]  ?? null) : null) ?? 0.0;
        $iva5   = ($c['iva_5']   !== null ? $this->parsearImporte($row[$c['iva_5']]   ?? null) : null) ?? 0.0;

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

        // Verificar duplicado: mismo número + mismo CUIT
        $yaExiste = false;
        if (!empty($cuit) && $numeroComprobante !== '0000-00000000') {
            $yaExiste = Compra::where('numero_comprobante', $numeroComprobante)
                ->whereHas('proveedor', fn($q) => $q->where('cuit', $cuit))
                ->exists();
        }

        // Determinar estado
        $estado   = 'nuevo';
        $errorMsg = null;

        if ($yaExiste) {
            $estado = 'duplicado';
        } elseif (!$fecha) {
            $estado   = 'error';
            $errorMsg = 'Fecha inválida (' . $fechaRaw . ')';
        } elseif ($total === null || $total <= 0) {
            $estado   = 'error';
            $errorMsg = 'Importe total inválido';
        }

        return [
            'fecha'              => $fecha,
            'tipo_comprobante'   => $tipoComprobante,
            'numero_comprobante' => $numeroComprobante,
            'cuit'               => $cuit,
            'nombre'             => $nombre,
            'subtotal'           => $subtotal,
            'iva_porc'           => $ivaPorc,
            'iva_importe'        => $ivaTotal,
            'total'              => round($total ?? 0, 2),
            'estado'             => $estado,
            'error_msg'          => $errorMsg,
            'proveedor_creado'   => false,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers de parseo
    // ─────────────────────────────────────────────────────────────

    private function parsearFecha(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        // Número de serie de Excel
        if (is_numeric($value) && (float)$value > 10000) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (\Exception) {}
        }

        $str = trim((string)$value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'd/m/y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $str);
                if ($d && $d->year > 2000) return $d->format('Y-m-d');
            } catch (\Exception) {}
        }
        return null;
    }

    private function parsearImporte(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_float($value) || is_int($value)) return (float)$value;

        $str = trim((string)$value);
        if ($str === '' || $str === '-') return 0.0;

        // Float con punto decimal inglés (ej: "88548.6") — excluir "1.234" que
        // podría ser miles argentinos (exactamente 3 dígitos tras el punto)
        if (is_numeric($str) && !preg_match('/^\d+\.\d{3}$/', $str)) {
            return (float)$str;
        }

        // Formato argentino: 1.234,56 → punto miles, coma decimal
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace(',', '.', str_replace('.', '', $str));
            if (!is_numeric($str)) {
                $str = str_replace(',', '.', (string)$value);
            }
        }

        return is_numeric($str) ? (float)$str : null;
    }

    private function normalizarCuit(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) === 11) {
            return substr($digits, 0, 2) . '-' . substr($digits, 2, 8) . '-' . substr($digits, 10);
        }
        return $raw; // devolver tal cual si no tiene 11 dígitos
    }

    private function mapearTipo(string $tipo): string
    {
        $tipo    = trim($tipo);
        $lower   = strtolower($tipo);
        $sinCero = ltrim($tipo, '0') ?: '0';

        // Búsqueda directa
        if (isset(self::MAPA_TIPOS[$tipo]))    return self::MAPA_TIPOS[$tipo];
        if (isset(self::MAPA_TIPOS[$sinCero])) return self::MAPA_TIPOS[$sinCero];
        if (isset(self::MAPA_TIPOS[$lower]))   return self::MAPA_TIPOS[$lower];

        // Nuevo formato ARCA: "1 - Factura A", "3 - Nota de Crédito A", etc.
        if (preg_match('/^(\d+)\s*[-–]/', $tipo, $m)) {
            $code    = $m[1];
            $sinCero = ltrim($code, '0') ?: '0';
            if (isset(self::MAPA_TIPOS[$code]))    return self::MAPA_TIPOS[$code];
            if (isset(self::MAPA_TIPOS[$sinCero])) return self::MAPA_TIPOS[$sinCero];
        }

        // Coincidencia parcial por texto (ej: "factura a" dentro del string)
        foreach (self::MAPA_TIPOS as $key => $val) {
            if (!is_numeric(str_replace(['-', '.', ' '], '', $key)) && str_contains($lower, $key)) {
                return $val;
            }
        }

        return 'otro';
    }

    public function render()
    {
        return view('livewire.compras.importar-compras-arca');
    }
}
