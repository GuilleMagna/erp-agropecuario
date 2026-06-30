<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Services\MrbotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SincronizarComprobantesArca extends Command
{
    protected $signature = 'arca:sincronizar
                            {--desde= : Fecha inicio YYYY-MM-DD (default: primer día del mes actual)}
                            {--hasta= : Fecha fin YYYY-MM-DD (default: hoy)}
                            {--debug  : Abre navegador visible con screenshots para depurar}
                            {--screenshots : Guarda screenshots en modo headless}';

    protected $description = 'Sincroniza Mis Comprobantes Recibidos desde ARCA vía automatización de navegador';

    public function handle(MrbotService $mrbot): int
    {
        $cuit  = config('mrbot.cuit_login');
        $clave = config('mrbot.clave_fiscal');

        if (empty($cuit) || empty($clave)) {
            $this->error('Configurá MRBOT_CUIT_LOGIN y MRBOT_CLAVE_FISCAL en el .env.');
            return self::FAILURE;
        }

        $desde = $this->resolverFecha($this->option('desde'), Carbon::now()->startOfMonth()->format('Y-m-d'));
        $hasta = $this->resolverFecha($this->option('hasta'), Carbon::now()->format('Y-m-d'));

        $this->info("Sincronizando comprobantes recibidos: {$desde} → {$hasta}");

        $scriptPath = base_path('scripts/arca_descarga.js');

        if (!file_exists($scriptPath)) {
            $this->error("Script no encontrado: {$scriptPath}");
            return self::FAILURE;
        }

        // ── Construir comando Node ────────────────────────────────────────────
        $cmd = [
            'node',
            $scriptPath,
            '--cuit',  $cuit,
            '--clave', $clave,
            '--desde', $desde,
            '--hasta', $hasta,
        ];

        if ($this->option('debug'))       $cmd[] = '--debug';
        if ($this->option('screenshots')) $cmd[] = '--screenshots';

        $this->line('Iniciando navegador y consultando ARCA...');
        $this->line('(Esto puede tardar entre 30 y 90 segundos)');

        // ── Ejecutar script ───────────────────────────────────────────────────
        $proceso = new Process($cmd, base_path(), null, null, 300);
        $proceso->run(function (string $type, string $buffer) {
            // stderr del script va a la consola como info
            if ($type === Process::ERR) {
                foreach (explode("\n", trim($buffer)) as $linea) {
                    if ($linea) $this->line("  {$linea}");
                }
            }
        });

        if (!$proceso->isSuccessful()) {
            $this->error('El script Python terminó con error.');
            $this->error($proceso->getErrorOutput());
            return self::FAILURE;
        }

        // ── Leer JSON del stdout ──────────────────────────────────────────────
        $stdout = trim($proceso->getOutput());

        if (empty($stdout)) {
            $this->error('El script no devolvió datos.');
            return self::FAILURE;
        }

        $data = json_decode($stdout, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Respuesta del script no es JSON válido:');
            $this->error(substr($stdout, 0, 300));
            return self::FAILURE;
        }

        // Error devuelto por el propio script
        if (isset($data['__error__'])) {
            $this->error("Error en la descarga: {$data['__error__']}");
            return self::FAILURE;
        }

        if (empty($data)) {
            $this->info('No hay comprobantes en el período seleccionado.');
            return self::SUCCESS;
        }

        $this->info('Descarga OK. Importando ' . count($data) . ' comprobante(s)...');

        // ── Resolver empresa destino (en consola no hay sesión autenticada) ───
        $empresa = Empresa::first();
        if (!$empresa) {
            $this->error('No hay empresa configurada en la base de datos.');
            return self::FAILURE;
        }

        // ── Importar ──────────────────────────────────────────────────────────
        $resultado = $mrbot->importarComprobantesJson($data, $empresa->id);

        $this->info("Importadas: {$resultado['importadas']}");
        $this->line("Duplicadas: {$resultado['duplicadas']}");

        if ($resultado['errores'] > 0) {
            $this->warn("Con errores: {$resultado['errores']}");
        }

        return self::SUCCESS;
    }

    private function resolverFecha(?string $input, string $default): string
    {
        if (!$input) return $default;

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $input);
                if ($d) return $d->format('Y-m-d');
            } catch (\Exception) {}
        }

        return $default;
    }

    private function pythonBin(): string
    {
        foreach (['py', 'python3', 'python'] as $bin) {
            exec("where {$bin} 2>NUL", $out, $code);
            if ($code === 0 && !empty($out)) {
                // Descartar el stub del Microsoft Store (version 0.0.0.0)
                $ver = shell_exec("{$bin} --version 2>&1");
                if ($ver && !str_contains($ver, '0.0.0.0') && preg_match('/Python 3\./', $ver)) {
                    return $bin;
                }
            }
            $out = [];
        }
        throw new \RuntimeException(
            "Python 3 no encontrado. Instalalo desde python.org asegurándote de marcar 'Add Python to PATH', " .
            "luego ejecutá: pip install playwright && playwright install chromium"
        );
    }
}
