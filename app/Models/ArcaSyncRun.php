<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ArcaSyncRun extends Model
{
    protected $table = 'arca_sync_runs';

    protected $guarded = [];

    protected $casts = [
        'desde' => 'date',
        'hasta' => 'date',
        'eventos' => 'array',
        'iniciado_at' => 'datetime',
        'finalizado_at' => 'datetime',
    ];

    public static function registrarEstado(array $datos): self
    {
        return DB::transaction(function () use ($datos) {
            $run = self::query()->lockForUpdate()->firstOrNew(['run_id' => $datos['run_id']]);
            $eventos = $run->eventos ?? [];
            $eventos[] = [
                'fecha' => now()->toIso8601String(),
                'tipo' => $datos['estado'],
                'mensaje' => $datos['mensaje'] ?? null,
            ];

            $run->fill([
                'estado' => $datos['estado'],
                'run_url' => $datos['run_url'] ?? $run->run_url,
                'desde' => $datos['desde'] ?? $run->desde,
                'hasta' => $datos['hasta'] ?? $run->hasta,
                'empresas_total' => $datos['empresas_total'] ?? $run->empresas_total ?? 0,
                'eventos' => array_slice($eventos, -50),
                'iniciado_at' => $run->iniciado_at ?? now(),
                'finalizado_at' => $datos['estado'] === 'running' ? null : now(),
            ]);
            $run->save();

            return $run;
        });
    }

    public static function registrarResultado(string $runId, string $empresaCuit, int $recibidos, array $resultado): void
    {
        DB::transaction(function () use ($runId, $empresaCuit, $recibidos, $resultado) {
            $run = self::query()->where('run_id', $runId)->lockForUpdate()->first();

            if (! $run) {
                return;
            }

            $eventos = $run->eventos ?? [];
            $eventos[] = [
                'fecha' => now()->toIso8601String(),
                'tipo' => 'empresa',
                'mensaje' => $empresaCuit,
                'recibidos' => $recibidos,
                'importadas' => $resultado['importadas'],
                'duplicadas' => $resultado['duplicadas'],
                'errores' => (int) $resultado['errores'],
            ];

            $run->update([
                'empresas_procesadas' => $run->empresas_procesadas + 1,
                'recibidos' => $run->recibidos + $recibidos,
                'importadas' => $run->importadas + $resultado['importadas'],
                'duplicadas' => $run->duplicadas + $resultado['duplicadas'],
                'errores' => $run->errores + (int) $resultado['errores'],
                'eventos' => array_slice($eventos, -50),
            ]);
        });
    }
}
