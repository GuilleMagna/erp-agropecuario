<?php

namespace Tests\Feature;

use App\Models\Empresa;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArcaSyncApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('empresas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('razon_social');
            $table->string('cuit')->unique();
            $table->string('condicion_fiscal');
            $table->string('moneda_default')->default('ARS');
            $table->boolean('activa')->default(true);
            $table->boolean('arca_activo')->default(false);
            $table->timestamps();
        });
    }

    public function test_rejects_requests_without_the_shared_token(): void
    {
        config(['services.arca_sync.token' => 'test-secret']);

        $this->postJson('/api/arca/comprobantes', [
            'empresa_cuit' => '20-12345678-9',
            'comprobantes' => [],
        ])->assertUnauthorized();
    }

    public function test_accepts_an_empty_idempotent_sync_for_an_active_company(): void
    {
        config(['services.arca_sync.token' => 'test-secret']);

        Empresa::create([
            'razon_social' => 'Empresa de prueba',
            'cuit' => '20-12345678-9',
            'condicion_fiscal' => 'Responsable Inscripto',
            'moneda_default' => 'ARS',
            'activa' => true,
            'arca_activo' => true,
        ]);

        $this->withToken('test-secret')
            ->postJson('/api/arca/comprobantes', [
                'empresa_cuit' => '20123456789',
                'desde' => '2026-08-01',
                'hasta' => '2026-08-11',
                'comprobantes' => [],
            ])
            ->assertOk()
            ->assertExactJson([
                'importadas' => 0,
                'duplicadas' => 0,
                'errores' => 0,
            ]);
    }

    public function test_rejects_unknown_companies(): void
    {
        config(['services.arca_sync.token' => 'test-secret']);

        $this->withToken('test-secret')
            ->postJson('/api/arca/comprobantes', [
                'empresa_cuit' => '20-99999999-9',
                'comprobantes' => [],
            ])
            ->assertNotFound();
    }
}
