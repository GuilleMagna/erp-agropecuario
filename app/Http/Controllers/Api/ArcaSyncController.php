<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArcaSyncRun;
use App\Models\Empresa;
use App\Services\MrbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArcaSyncController extends Controller
{
    public function __invoke(Request $request, MrbotService $importador): JsonResponse
    {
        $tokenConfigurado = (string) config('services.arca_sync.token');
        $tokenRecibido = (string) $request->bearerToken();

        if ($tokenConfigurado === '' || $tokenRecibido === '' || ! hash_equals($tokenConfigurado, $tokenRecibido)) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        $datos = $request->validate([
            'empresa_cuit' => ['required', 'string', 'max:20'],
            'run_id' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'comprobantes' => ['present', 'array', 'max:10000'],
            'comprobantes.*' => ['array'],
        ]);

        $cuit = preg_replace('/\D/', '', $datos['empresa_cuit']);
        $empresa = Empresa::query()
            ->whereRaw("REPLACE(REPLACE(cuit, '-', ''), ' ', '') = ?", [$cuit])
            ->where('arca_activo', true)
            ->first();

        if (! $empresa) {
            return response()->json(['message' => 'Empresa ARCA activa no encontrada.'], 404);
        }

        $resultado = $importador->importarComprobantesJson($datos['comprobantes'], $empresa->id);

        if (! empty($datos['run_id'])) {
            ArcaSyncRun::registrarResultado(
                $datos['run_id'],
                $datos['empresa_cuit'],
                count($datos['comprobantes']),
                $resultado,
            );
        }
        Log::info('Sincronización ARCA externa recibida', [
            'empresa_id' => $empresa->id,
            'desde' => $datos['desde'] ?? null,
            'hasta' => $datos['hasta'] ?? null,
            'recibidos' => count($datos['comprobantes']),
            'importadas' => $resultado['importadas'],
            'duplicadas' => $resultado['duplicadas'],
            'errores' => $resultado['errores'],
        ]);

        return response()->json([
            'importadas' => $resultado['importadas'],
            'duplicadas' => $resultado['duplicadas'],
            'errores' => $resultado['errores'],
        ]);
    }
}
