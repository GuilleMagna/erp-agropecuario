<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 *
 * Orquesta el orden de ejecución de todos los seeders del proyecto.
 * El orden importa por dependencias: los roles deben existir antes del
 * admin inicial, la empresa antes que cualquier otra entidad.
 *
 * USO HABITUAL:
 *   php artisan migrate --seed       (migra y siembra en un solo comando)
 *   php artisan db:seed              (solo siembra, sin migrar)
 *   php artisan db:seed --class=RolesSeeder   (solo un seeder puntual)
 *
 * SEEDERS DE DATOS DE CATÁLOGO (próximas sesiones de trabajo):
 *   CategoriaAnimalSeeder  — categorías ganaderas estándar AR (Documento 03, sección 7.1)
 *   CultivoSeeder          — soja, maíz, trigo, girasol (Documento 03, sección 6.2)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Roles y permisos base (independientes de la empresa/usuario)
            RolesSeeder::class,

            // 2. Admin inicial (depende de que la empresa ya exista en la DB.
            //    En el arranque real, la empresa se crea desde la interfaz antes
            //    de correr este seeder, o se agrega aquí un EmpresaSeeder
            //    temporal solo para el primer deploy.)
            AdminInicialSeeder::class,
        ]);
    }
}
