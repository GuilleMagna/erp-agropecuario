<?php

use App\Models\Compra;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carga los comprobantes de compra de 2024 y 2025 que ARCA informa en Mis
 * Comprobantes Recibidos, y corrige el tipo de los que ya estaban cargados.
 *
 * Hace lo mismo que la pantalla de importación, pero desde el deploy: en
 * producción no se pueden subir los archivos.
 *
 * Los datos salen de storage/app/arca/comprobantes_arca_2024_2025.csv, que se
 * generó con los mismos métodos que usa la pantalla (lectura del export,
 * detección de columnas, mapeo de tipos y cálculo de importes), así que lo que
 * entra por acá es idéntico a lo que entraría por ahí.
 *
 * El archivo va en storage y no en el repositorio a propósito: son datos
 * fiscales de las tres empresas y el repositorio es público. Hay que subirlo
 * al servidor por FTP o por el administrador de archivos antes del deploy. Si
 * no está, la migración no hace nada y se puede volver a correr después.
 *
 * Es idempotente: los que ya están con el mismo tipo se saltean, y los que
 * están con otro tipo se corrigen una sola vez.
 *
 * Al corregir el tipo sólo se toca el tipo y el signo. Los importes del
 * archivo no pisan a los ya cargados salvo que vengan con valor, para no
 * borrar el crédito fiscal de un comprobante que lo tenía bien.
 */
return new class extends Migration
{
    private const ARCHIVO = 'arca/comprobantes_arca_2024_2025.csv';

    public function up(): void
    {
        $ruta = storage_path('app/'.self::ARCHIVO);
        if (! is_readable($ruta)) {
            info('Comprobantes ARCA: no se encontró '.self::ARCHIVO.', no hay nada que importar.');

            return;
        }

        $empresas = DB::table('empresas')->pluck('id', 'cuit');
        $proveedores = [];   // "idEmpresa|cuit" => idProveedor
        $altas = $correcciones = $sinCambio = 0;

        $fh = fopen($ruta, 'r');
        fgetcsv($fh, 0, ';');   // encabezado

        while (($fila = fgetcsv($fh, 0, ';')) !== false) {
            if (count($fila) < 10) {
                continue;
            }
            [$cuitEmpresa, $fecha, $tipo, $numero, $cuitEmisor, $denominacion, $subtotal, $ivaPorc, $iva, $total] = $fila;

            $idEmpresa = $empresas[$cuitEmpresa] ?? null;
            if (! $idEmpresa) {
                continue;
            }

            $subtotal = (float) $subtotal;
            $iva = (float) $iva;
            $total = (float) $total;

            $existente = DB::table('compras')
                ->where('id_empresa', $idEmpresa)
                ->where('numero_comprobante', $numero)
                ->whereDate('fecha', $fecha)
                ->first(['id', 'tipo_comprobante', 'subtotal', 'iva_importe', 'total']);

            if ($existente) {
                if ($existente->tipo_comprobante === $tipo) {
                    $sinCambio++;

                    continue;
                }

                // Sólo el tipo y el signo. Los importes del archivo pisan a los
                // guardados únicamente si vienen con valor.
                $signo = in_array($tipo, Compra::TIPOS_NEGATIVOS, true) ? -1 : 1;
                $tomar = fn (float $delArchivo, float $actual) => abs($delArchivo) > 0 ? abs($delArchivo) : abs($actual);

                DB::table('compras')->where('id', $existente->id)->update([
                    'tipo_comprobante' => $tipo,
                    'subtotal' => $signo * $tomar($subtotal, (float) $existente->subtotal),
                    'iva_importe' => $signo * $tomar($iva, (float) $existente->iva_importe),
                    'total' => $signo * $tomar($total, (float) $existente->total),
                    'updated_at' => now(),
                ]);
                $correcciones++;

                continue;
            }

            // ── Alta ──────────────────────────────────────────────────────
            $clave = $idEmpresa.'|'.$cuitEmisor;
            if (! isset($proveedores[$clave])) {
                $idProveedor = DB::table('proveedores')
                    ->where('id_empresa', $idEmpresa)->where('cuit', $cuitEmisor)->value('id');

                if (! $idProveedor && $cuitEmisor !== '') {
                    $idProveedor = (string) Str::uuid();
                    DB::table('proveedores')->insert([
                        'id' => $idProveedor,
                        'id_empresa' => $idEmpresa,
                        'nombre' => $denominacion ?: $cuitEmisor,
                        'razon_social' => $denominacion ?: null,
                        'cuit' => $cuitEmisor,
                        'rubro' => 'otro',
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $proveedores[$clave] = $idProveedor;
            }

            DB::table('compras')->insert([
                'id' => (string) Str::uuid(),
                'id_empresa' => $idEmpresa,
                'id_proveedor' => $proveedores[$clave],
                'id_establecimiento' => null,
                'tipo_comprobante' => $tipo,
                'numero_comprobante' => $numero,
                'fecha' => $fecha,
                'estado' => 'recibida',
                'subtotal' => $subtotal,
                'iva_porc' => (float) $ivaPorc,
                'iva_importe' => $iva,
                'total' => $total,
                'stock_registrado' => false,
                'presentado_arca' => true,
                'actividad' => 'general',
                'observaciones' => 'Importado desde ARCA (Mis Comprobantes Recibidos)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $altas++;
        }
        fclose($fh);

        info(sprintf('Comprobantes ARCA 2024-2025: %d altas, %d tipos corregidos, %d sin cambios.',
            $altas, $correcciones, $sinCambio));
    }

    public function down(): void
    {
        // Las altas se identifican por la observación con la que se cargaron.
        DB::table('compras')
            ->where('observaciones', 'Importado desde ARCA (Mis Comprobantes Recibidos)')
            ->whereBetween('fecha', ['2024-01-01', '2025-12-31'])
            ->delete();
    }
};
