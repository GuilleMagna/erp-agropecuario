<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AdminInicialSeeder
 *
 * Crea el único usuario que se siembra automáticamente: el administrador
 * inicial del sistema, que permite entrar la primera vez para crear el
 * resto de los usuarios desde la interfaz (Documento 02, sección 1.1 —
 * los usuarios se crean desde la aplicación, no se migran).
 *
 * Los datos se toman del .env para no hardcodear credenciales en el código.
 * Antes de correr este seeder, completar en .env:
 *   ADMIN_INICIAL_EMAIL=
 *   ADMIN_INICIAL_PASSWORD=
 *
 * IMPORTANTE: este seeder es idempotente (usa firstOrCreate), así que
 * correrlo más de una vez no duplica el usuario ni genera error.
 */
class AdminInicialSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar que las variables necesarias estén configuradas
        if (empty(env('ADMIN_INICIAL_EMAIL')) || empty('ADMIN_INICIAL_PASSWORD')) {
            $this->command->error(
                'Configurar ADMIN_INICIAL_EMAIL y ADMIN_INICIAL_PASSWORD en .env antes de correr este seeder.'
            );
            return;
        }

        // Tomar la empresa existente (debe existir al menos una antes de este seeder)
        $empresa = Empresa::first();
        if (! $empresa) {
            $this->command->error(
                'No hay ninguna empresa en la base de datos. Crear la empresa desde la interfaz primero, o agregarla en EmpresaSeeder.'
            );
            return;
        }

        $admin = Usuario::firstOrCreate(
            ['email' => env('ADMIN_INICIAL_EMAIL')],
            [
                'id_empresa' => $empresa->id,
                'nombre'     => env('ADMIN_INICIAL_NOMBRE', 'Administrador'),
                'apellido'   => env('ADMIN_INICIAL_APELLIDO', 'Sistema'),
                'email'      => env('ADMIN_INICIAL_EMAIL'),
                'password'   => Hash::make(env('ADMIN_INICIAL_PASSWORD')),
                'activo'     => true,
            ]
        );

        // Asignar el rol de administrador del sistema
        // (los roles deben existir: correr RolesSeeder antes que este)
        $admin->assignRole('administrador_sistema');

        // Dar acceso a todos los establecimientos existentes
        $admin->establecimientos()->syncWithoutDetaching(
            \App\Models\Establecimiento::pluck('id')
        );

        $this->command->info("Usuario administrador listo: {$admin->email}");
        $this->command->warn('Acordate de cambiar la contraseña desde la interfaz después del primer acceso.');
    }
}
