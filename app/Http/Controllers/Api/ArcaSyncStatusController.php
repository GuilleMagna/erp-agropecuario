<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArcaSyncRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArcaSyncStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tokenConfigurado = (string) config('services.arca_sync.token');
        $tokenRecibido = (string) $request->bearerToken();

        if ($tokenConfigurado === '' || $tokenRecibido === '' || ! hash_equals($tokenConfigurado, $tokenRecibido)) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        $datos = $request->validate([
            'run_id' => ['required', 'string', 'max:100'],
            'estado' => ['required', Rule::in(['running', 'success', 'failure', 'cancelled'])],
            'mensaje' => ['nullable', 'string', 'max:2000'],
            'run_url' => ['nullable', 'url', 'max:255'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'empresas_total' => ['nullable', 'integer', 'min:0'],
        ]);

        $run = ArcaSyncRun::registrarEstado($datos);

        return response()->json(['id' => $run->id, 'estado' => $run->estado]);
    }
}
