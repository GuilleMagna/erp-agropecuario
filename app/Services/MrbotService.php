<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MrbotService
{
    private string $baseUrl;

    private string $apiKey;

    private string $email;

    private int $timeout;

    // Mismos mapeos de tipo que ImportarComprasArca
    const MAPA_TIPOS = [
        '1' => 'factura_a', '01' => 'factura_a', '001' => 'factura_a',
        '2' => 'nota_debito',  '02' => 'nota_debito',  '002' => 'nota_debito',   // ND A
        '3' => 'nota_credito', '03' => 'nota_credito', '003' => 'nota_credito',  // NC A
        '6' => 'factura_b', '06' => 'factura_b',  '006' => 'factura_b',
        '7' => 'nota_debito',  '07' => 'nota_debito',  '007' => 'nota_debito',   // ND B
        '8' => 'nota_credito', '08' => 'nota_credito', '008' => 'nota_credito',  // NC B
        '11' => 'factura_c', '011' => 'factura_c',
        '12' => 'nota_debito',  '012' => 'nota_debito',   // ND C
        '13' => 'nota_credito', '013' => 'nota_credito',  // NC C
        '51' => 'factura_a', '051' => 'factura_a',
        '52' => 'nota_debito',  '052' => 'nota_debito',   // ND M
        '53' => 'nota_credito', '053' => 'nota_credito',  // NC M
        '81' => 'ticket',    '081' => 'ticket',
        '82' => 'ticket',    '082' => 'ticket',
        '83' => 'ticket',    '083' => 'ticket',
        '110' => 'nota_credito',                          // Tique nota de crédito
        '112' => 'nota_credito', '113' => 'nota_credito', '114' => 'nota_credito',
        '115' => 'nota_debito',  '116' => 'nota_debito',  '117' => 'nota_debito',
        '118' => 'nota_credito', '119' => 'nota_credito', '120' => 'nota_debito',
        '201' => 'factura_a', '202' => 'nota_debito', '203' => 'nota_credito',   // FCE MiPyME A
        '206' => 'factura_b', '207' => 'nota_debito', '208' => 'nota_credito',   // FCE MiPyME B
        '211' => 'factura_c', '212' => 'nota_debito', '213' => 'nota_credito',   // FCE MiPyME C
        'factura a' => 'factura_a',
        'factura b' => 'factura_b',
        'factura c' => 'factura_c',
        'factura m' => 'factura_a',
        'ticket' => 'ticket',
        'ticket factura a' => 'ticket',
        'ticket factura b' => 'ticket',
        'recibo' => 'recibo',
        // Nota: la búsqueda por texto es por str_contains, así que alcanza con
        // el prefijo para cubrir A, B, C y M.
        'nota de débito' => 'nota_debito',
        'nota de credito' => 'nota_credito',
        'nota de crédito' => 'nota_credito',
        'nota credito' => 'nota_credito',
        'nota debito' => 'nota_debito',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('mrbot.url', 'https://api-bots.mrbot.com.ar'), '/');
        $this->apiKey = config('mrbot.api_key', '');
        $this->email = config('mrbot.email', '');
        $this->timeout = config('mrbot.timeout', 120);
    }

    public function estaConfigurado(): bool
    {
        return ! empty($this->apiKey)
            && ! empty($this->email)
            && ! empty(config('mrbot.cuit_login'))
            && ! empty(config('mrbot.clave_fiscal'))
            && ! empty(config('mrbot.cuit_representado'));
    }

    /**
     * Importa un array de comprobantes (JSON de MrBot o CSV parseado por Node) a la BD.
     *
     * @param  string|null  $idEmpresa  UUID de la empresa destino. En web se toma del
     *                                  usuario autenticado vía PerteneceAEmpresa; en
     *                                  consola (artisan) debe pasarse explícitamente.
     * @return array{importadas: int, duplicadas: int, errores: int, error: string|null, compras: Collection}
     */
    public function importarComprobantesJson(array $comprobantes, ?string $idEmpresa = null): array
    {
        $resultado = ['importadas' => 0, 'duplicadas' => 0, 'errores' => 0, 'error' => null, 'compras' => collect()];

        foreach ($comprobantes as $item) {
            $fila = $this->parsearComprobante($item, $idEmpresa);

            if ($fila === null) {
                Log::warning('MrbotService: comprobante descartado (datos insuficientes)', ['item' => $item]);
                $resultado['errores']++;

                continue;
            }
            if ($fila['estado'] === 'duplicado') {
                $resultado['duplicadas']++;

                continue;
            }
            if ($fila['estado'] === 'error') {
                Log::warning('MrbotService: comprobante con error de validación', [
                    'motivo' => $fila['error_msg'],
                    'fila' => $fila,
                ]);
                $resultado['errores']++;

                continue;
            }

            try {
                $proveedor = null;
                if (! empty($fila['cuit'])) {
                    $proveedor = Proveedor::withoutGlobalScope('empresa')
                        ->where('cuit', $fila['cuit'])
                        ->when($idEmpresa, fn ($q) => $q->where('id_empresa', $idEmpresa))
                        ->first();

                    if (! $proveedor) {
                        $attrs = [
                            'nombre' => $fila['nombre'] ?: $fila['cuit'],
                            'razon_social' => $fila['nombre'] ?: null,
                            'cuit' => $fila['cuit'],
                            'rubro' => 'otro',
                            'activo' => true,
                        ];
                        if ($idEmpresa) {
                            $attrs['id_empresa'] = $idEmpresa;
                        }
                        $proveedor = Proveedor::create($attrs);
                    }
                }

                $compraAttrs = [
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
                    'observaciones' => 'Sincronizado desde ARCA',
                    // Se hereda del proveedor si ya tiene clasificación cargada
                    // (catálogo importado de "Facturas AÑO 2024.xlsx" o cargada
                    // a mano, siempre trae actividad); si el proveedor es nuevo
                    // (recién autocreado acá arriba), queda sin clasificar. La
                    // zona no se hereda acá: es del establecimiento, y esta
                    // sincronización no elige uno (queda para completar a mano).
                    'actividad' => $proveedor?->actividad !== null ? $proveedor->actividad : null,
                    'rubro' => $proveedor?->actividad !== null ? $proveedor->rubro : null,
                ];
                if ($idEmpresa) {
                    $compraAttrs['id_empresa'] = $idEmpresa;
                }
                $compra = Compra::create($compraAttrs);
                $compra->setRelation('proveedor', $proveedor);

                $resultado['importadas']++;
                $resultado['compras']->push($compra);
            } catch (\Exception $e) {
                Log::warning('MrbotService: error importando comprobante', [
                    'error' => $e->getMessage(),
                    'fila' => $fila,
                ]);
                $resultado['errores']++;
            }
        }

        return $resultado;
    }

    /**
     * Consulta Mis Comprobantes Recibidos vía MrBot API y los importa a la BD.
     *
     * @return array{importadas: int, duplicadas: int, errores: int, error: string|null}
     */
    public function sincronizarRecibidos(string $desde, string $hasta): array
    {
        $resultado = ['importadas' => 0, 'duplicadas' => 0, 'errores' => 0, 'error' => null];

        $response = $this->consultarApi($desde, $hasta);

        if (! $response['success']) {
            $resultado['error'] = $response['error'];

            return $resultado;
        }

        $comprobantes = $response['comprobantes'] ?? [];

        if (empty($comprobantes)) {
            return $resultado;
        }

        return $this->importarComprobantesJson($comprobantes);
    }

    /**
     * Llama a la API de MrBot y retorna los comprobantes recibidos como array.
     *
     * @return array{success: bool, comprobantes: array, error: string|null}
     */
    private function consultarApi(string $desde, string $hasta): array
    {
        $url = $this->baseUrl.'/api/v1/mis_comprobantes/consulta';

        $payload = [
            'desde' => Carbon::parse($desde)->format('d/m/Y'),
            'hasta' => Carbon::parse($hasta)->format('d/m/Y'),
            'cuit_inicio_sesion' => config('mrbot.cuit_login'),
            'representado_nombre' => config('mrbot.nombre_representado'),
            'representado_cuit' => config('mrbot.cuit_representado'),
            'contrasena' => config('mrbot.clave_fiscal'),
            'descarga_emitidos' => false,
            'descarga_recibidos' => true,
            'carga_minio' => false,
            'carga_json' => true,
            'b64' => false,
            'carga_s3' => false,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
                'email' => $this->email,
            ])->timeout($this->timeout)->post($url, $payload);

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                $error = $data['error'] ?? $data['detail'] ?? $data['message'] ?? 'Error desconocido en la API de MrBot';
                Log::error('MrbotService: API devolvió error', ['response' => $data]);

                return ['success' => false, 'comprobantes' => [], 'error' => $error];
            }

            $comprobantes = $data['mis_comprobantes_recibidos_json'] ?? [];

            return ['success' => true, 'comprobantes' => $comprobantes, 'error' => null];

        } catch (\Exception $e) {
            Log::error('MrbotService: excepción al llamar API', ['error' => $e->getMessage()]);

            return ['success' => false, 'comprobantes' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Parsea un elemento JSON de comprobante y lo mapea al formato interno.
     * Normaliza los nombres de campos con flexibilidad (ARCA puede usar distintas variantes).
     */
    private function parsearComprobante(array $item, ?string $idEmpresa = null): ?array
    {
        // Normalizar todas las claves: minúsculas, sin tildes, espacios→_
        $n = [];
        foreach ($item as $k => $v) {
            $n[$this->normalizarKey($k)] = $v;
        }

        // ── Fecha ──────────────────────────────────────────────────────────
        $fechaRaw = $n['fecha'] ?? $n['fecha_comprobante'] ?? $n['fecha_de_emision'] ?? $n['fecha_emision'] ?? null;
        $fecha = $this->parsearFecha($fechaRaw);

        // ── Tipo comprobante ───────────────────────────────────────────────
        $tipoRaw = (string) ($n['tipo'] ?? $n['tipo_comprobante'] ?? $n['tipo_de_comprobante'] ?? '');
        $tipoComprobante = $this->mapearTipo($tipoRaw);

        // ── Punto de venta y número ────────────────────────────────────────
        $pvRaw = (string) ($n['punto_de_venta'] ?? $n['pto_venta'] ?? $n['pto__venta'] ?? $n['pv'] ?? '0');
        $numRaw = (string) ($n['numero_desde'] ?? $n['numero'] ?? $n['nro'] ?? '0');
        $pv = str_pad(preg_replace('/\D/', '', $pvRaw), 4, '0', STR_PAD_LEFT);
        $num = str_pad(preg_replace('/\D/', '', $numRaw), 8, '0', STR_PAD_LEFT);
        $numeroComprobante = "{$pv}-{$num}";

        // ── CUIT y nombre del proveedor ───────────────────────────────────
        $cuitRaw = (string) ($n['cuit'] ?? $n['nro_doc_emisor'] ?? $n['nro__doc__emisor'] ?? '');
        $cuit = $this->normalizarCuit($cuitRaw);
        $nombre = (string) ($n['denominacion'] ?? $n['denominacion_emisor'] ?? $n['denominaci_n_emisor'] ?? $n['razon_social'] ?? $n['nombre'] ?? '');

        // ── Moneda y tipo de cambio ────────────────────────────────────────
        // ARCA informa el neto/IVA/total del comprobante en su moneda original
        // (por ej. USD). Si no se convierte a ARS acá, un comprobante en dólares
        // queda guardado con el valor numérico del dólar tratado como si fuera
        // pesos (undercount de ~1/tipo_cambio).
        $monedaRaw = strtoupper(trim((string) ($n['moneda'] ?? $n['tipo_moneda'] ?? $n['divisa'] ?? '')));
        $tipoCambio = $this->parsearImporte(
            $n['tipo_cambio'] ?? $n['tipo_de_cambio'] ?? $n['cotizacion'] ?? $n['tc'] ?? null
        ) ?? 1.0;
        $factorConversion = (! in_array($monedaRaw, ['', 'ARS', '$', 'PESOS', 'PES'], true) && $tipoCambio > 0)
            ? $tipoCambio
            : 1.0;

        // ── Importes (ya convertidos a ARS) ─────────────────────────────────
        $neto = ($this->parsearImporte($n['imp_neto_gravado'] ?? $n['imp_neto_gravado_total'] ?? $n['neto_gravado'] ?? $n['neto'] ?? null) ?? 0.0) * $factorConversion;
        $netoNg = ($this->parsearImporte($n['imp_neto_no_gravado'] ?? $n['neto_no_gravado'] ?? null) ?? 0.0) * $factorConversion;
        $exento = ($this->parsearImporte($n['imp_op_exentas'] ?? $n['exento'] ?? null) ?? 0.0) * $factorConversion;

        $iva21 = ($this->parsearImporte($n['iva_21'] ?? $n['iva_21_'] ?? null) ?? 0.0) * $factorConversion;
        $iva105 = ($this->parsearImporte($n['iva_105'] ?? $n['iva_10_5'] ?? $n['iva_10,5'] ?? null) ?? 0.0) * $factorConversion;
        $iva27 = ($this->parsearImporte($n['iva_27'] ?? null) ?? 0.0) * $factorConversion;
        $iva25 = ($this->parsearImporte($n['iva_25'] ?? $n['iva_2_5'] ?? null) ?? 0.0) * $factorConversion;
        $iva5 = ($this->parsearImporte($n['iva_5'] ?? null) ?? 0.0) * $factorConversion;

        $ivaTotal = round($iva21 + $iva105 + $iva27 + $iva25 + $iva5, 2);
        $subtotal = round($neto + $netoNg + $exento, 2);

        $totalRaw = $n['imp_total'] ?? $n['total'] ?? $n['importe_total'] ?? null;
        $total = $this->parsearImporte($totalRaw);
        if ($total !== null) {
            $total *= $factorConversion;
        }

        if ($subtotal === 0.0 && $total !== null && $ivaTotal > 0) {
            $subtotal = round($total - $ivaTotal, 2);
        } elseif ($subtotal === 0.0 && $total !== null) {
            $subtotal = $total;
        }

        $ivaPorc = ($subtotal > 0 && $ivaTotal > 0)
            ? round(($ivaTotal / $subtotal) * 100, 2)
            : 0.0;

        // ── Notas de crédito: se guardan en negativo ──────────────────────
        // ARCA las informa con importes positivos. Si se guardan así, suman
        // en vez de restar y inflan tanto el total de compras como el crédito
        // fiscal. Se toma el valor absoluto por si alguna fuente ya las trae
        // con signo.
        if (in_array($tipoComprobante, Compra::TIPOS_NEGATIVOS, true)) {
            $subtotal = -abs($subtotal);
            $ivaTotal = -abs($ivaTotal);
            if ($total !== null) {
                $total = -abs($total);
            }
        }

        // ── Validar datos mínimos ─────────────────────────────────────────
        if (empty($cuit) && ($total === null || abs($total) < 0.005)) {
            return null;
        }

        // ── Chequeo de duplicado ──────────────────────────────────────────
        // Se ignora el global scope de empresa (que depende de la empresa
        // activa en sesión) y se filtra explícitamente por $idEmpresa, la
        // empresa que realmente se está sincronizando. Si no se hace así,
        // al sincronizar desde el navegador con otra empresa seleccionada
        // en el selector de arriba, este chequeo mira los comprobantes de
        // la empresa equivocada, nunca encuentra el ya importado y termina
        // creando un duplicado.
        $estado = 'nuevo';
        $errorMsg = null;

        if (! empty($cuit) && $numeroComprobante !== '0000-00000000') {
            $yaExiste = Compra::withoutGlobalScope('empresa')
                ->where('numero_comprobante', $numeroComprobante)
                ->when($idEmpresa, fn ($q) => $q->where('id_empresa', $idEmpresa))
                ->whereHas('proveedor', function ($q) use ($cuit, $idEmpresa) {
                    $q->withoutGlobalScope('empresa')->where('cuit', $cuit);
                    if ($idEmpresa) {
                        $q->where('id_empresa', $idEmpresa);
                    }
                })
                ->exists();
            if ($yaExiste) {
                $estado = 'duplicado';
            }
        }

        if ($estado === 'nuevo') {
            if (! $fecha) {
                $estado = 'error';
                $errorMsg = "Fecha inválida ({$fechaRaw})";
            } elseif ($total === null || abs($total) < 0.005) {
                $estado = 'error';
                $errorMsg = 'Importe total inválido';
            }
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
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers de parseo (misma lógica que ImportarComprasArca)
    // ─────────────────────────────────────────────────────────────

    private function normalizarKey(string $key): string
    {
        $key = mb_strtolower(trim($key));
        $key = strtr($key, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim($key, '_');
    }

    private function parsearFecha(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
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

        if (is_numeric($str) && ! preg_match('/^\d+\.\d{3}$/', $str)) {
            return (float) $str;
        }

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

        return $raw;
    }

    private function mapearTipo(string $tipo): string
    {
        $tipo = trim($tipo);
        $lower = strtolower($tipo);
        $sinCero = ltrim($tipo, '0') ?: '0';

        if (isset(self::MAPA_TIPOS[$tipo])) {
            return self::MAPA_TIPOS[$tipo];
        }
        if (isset(self::MAPA_TIPOS[$sinCero])) {
            return self::MAPA_TIPOS[$sinCero];
        }
        if (isset(self::MAPA_TIPOS[$lower])) {
            return self::MAPA_TIPOS[$lower];
        }

        if (preg_match('/^(\d+)\s*[-–]/', $tipo, $m)) {
            $code = $m[1];
            $sinCero = ltrim($code, '0') ?: '0';
            if (isset(self::MAPA_TIPOS[$code])) {
                return self::MAPA_TIPOS[$code];
            }
            if (isset(self::MAPA_TIPOS[$sinCero])) {
                return self::MAPA_TIPOS[$sinCero];
            }
        }

        foreach (self::MAPA_TIPOS as $key => $val) {
            if (! is_numeric(str_replace(['-', '.', ' '], '', $key)) && str_contains($lower, $key)) {
                return $val;
            }
        }

        return 'otro';
    }
}
