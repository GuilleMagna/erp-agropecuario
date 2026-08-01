<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Console\Command;

class RepararEmpresaUsuarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usuarios:reparar-empresa {--empresa= : UUID o razón social de la empresa a asignar (default: la primera)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna una empresa a los usuarios que quedaron con id_empresa vacío (bug de altas automáticas por Google)';

    public function handle(): int
    {
        $usuariosSinEmpresa = Usuario::whereNull('id_empresa')->get();

        if ($usuariosSinEmpresa->isEmpty()) {
            $this->info('No hay usuarios sin empresa asignada.');

            return self::SUCCESS;
        }

        $opcion = $this->option('empresa');
        $empresa = $opcion
            ? Empresa::where('id', $opcion)->orWhere('razon_social', $opcion)->first()
            : Empresa::first();

        if (! $empresa) {
            $this->error('No se encontró la empresa indicada (o no hay ninguna empresa cargada).');

            return self::FAILURE;
        }

        $this->info("Empresa a asignar: {$empresa->razon_social}");
        $this->line('Usuarios sin empresa encontrados:');
        foreach ($usuariosSinEmpresa as $usuario) {
            $this->line("  - {$usuario->email} ({$usuario->nombre_completo})");
        }

        foreach ($usuariosSinEmpresa as $usuario) {
            $usuario->id_empresa = $empresa->id;
            $usuario->save();
        }

        $this->info("Reparados: {$usuariosSinEmpresa->count()} usuario(s).");
        $this->warn('Revisá en Sistema → Usuarios si alguno debería quedar en otra empresa distinta a la asignada acá.');

        return self::SUCCESS;
    }
}
