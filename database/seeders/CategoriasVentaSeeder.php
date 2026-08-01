<?php

namespace Database\Seeders;

use App\Models\CategoriaVenta;
use Illuminate\Database\Seeder;

class CategoriasVentaSeeder extends Seeder
{
    public function run(): void
    {
        CategoriaVenta::firstOrCreate(
            ['nombre' => 'Vacas'],
            ['tipo_cantidad' => 'animales_kg', 'activo' => true]
        );

        CategoriaVenta::firstOrCreate(
            ['nombre' => 'Cereales'],
            ['tipo_cantidad' => 'quintales', 'activo' => true]
        );
    }
}
