<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'razon_social' => fake()->company(),
            'cuit' => sprintf('%02d-%08d-%d', fake()->numberBetween(20, 34), fake()->unique()->numberBetween(10000000, 99999999), fake()->numberBetween(0, 9)),
            'condicion_fiscal' => 'responsable_inscripto',
            'moneda_default' => 'ARS',
            'activa' => true,
        ];
    }
}
