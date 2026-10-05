<?php

namespace Tests\Feature;

use App\Livewire\Compras\GestionCompras;
use App\Livewire\Compras\GestionProveedores;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Rubro;
use App\Models\Usuario;
use App\Services\MrbotService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ClasificacionRubrosTest extends TestCase
{
    private string $empresaA = '11111111-1111-4111-8111-111111111111';

    private string $empresaB = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        Schema::create('empresas', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
        DB::table('empresas')->insert([['id' => $this->empresaA], ['id' => $this->empresaB]]);
        (require database_path('migrations/2026_10_05_000001_create_rubros_table.php'))->up();
        (require database_path('migrations/2026_06_26_000015_create_proveedores_table.php'))->up();
        Schema::table('proveedores', fn (Blueprint $table) => $table->string('actividad')->nullable());
        Schema::create('compras', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_proveedor')->nullable();
            $table->uuid('id_establecimiento')->nullable();
            $table->string('tipo_comprobante');
            $table->string('numero_comprobante');
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('iva_porc', 6, 2)->nullable();
            $table->decimal('iva_importe', 14, 2);
            $table->decimal('total', 14, 2);
            $table->boolean('stock_registrado')->default(false);
            $table->text('observaciones')->nullable();
            $table->string('actividad')->nullable();
            $table->string('rubro')->nullable();
            $table->string('zona')->nullable();
            $table->uuid('id_lote')->nullable();
            $table->uuid('id_campana')->nullable();
            $table->boolean('presentado_arca')->default(false);
            $table->timestamps();
        });
        foreach (['establecimientos', 'insumos', 'lotes', 'campanas'] as $nombre) {
            Schema::create($nombre, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('id_empresa');
                $table->string('nombre');
                $table->boolean('activo')->default(true);
            });
        }
        Schema::create('compra_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_empresa');
            $table->uuid('id_compra');
            $table->uuid('id_insumo')->nullable();
            $table->string('descripcion');
            $table->decimal('cantidad', 14, 2);
            $table->string('unidad')->nullable();
            $table->decimal('precio_unitario', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        Gate::before(fn ($user, $ability) => true);
        $this->actingAs(new Usuario(['id_empresa' => $this->empresaA]));
    }

    private function proveedor(array $datos = []): Proveedor
    {
        return Proveedor::create(array_merge([
            'nombre' => 'Cosechadora', 'cuit' => '30-12345678-9', 'rubro' => 'cosecha',
        ], $datos));
    }

    public function test_crear_un_rubro_y_clasificar_un_cuit_sugiere_su_actividad(): void
    {
        $panel = Livewire::test(GestionProveedores::class)
            ->call('abrirCatalogo')
            ->set('nuevoRubroNombre', 'Servicios de drones')
            ->set('nuevoRubroActividades', ['agricultura', 'ganaderia'])
            ->set('nuevoRubroDefault', 'agricultura')
            ->call('guardarRubro')->assertHasNoErrors()
            ->assertSee('Servicios de drones')
            ->call('abrirModalCrear')
            ->set('nombre', 'Proveedor de drones')
            ->set('cuit', '30-12345678-9')
            ->set('rubro', 'servicios_de_drones')
            ->assertSet('actividad', 'agricultura')
            ->assertSee('Si hay varias actividades')
            ->call('guardar')->assertHasNoErrors();
        $proveedor = Proveedor::porCuit('30123456789')->firstOrFail();
        $this->assertSame('30123456789', $proveedor->cuit);
        $this->assertSame('agricultura', $proveedor->actividadPredeterminada());
        $this->assertArrayNotHasKey('servicios_de_drones', Rubro::opciones($this->empresaB));
    }

    public function test_rubro_exige_actividad_default_entre_las_permitidas_y_nombre_unico(): void
    {
        Livewire::test(GestionProveedores::class)
            ->set('nuevoRubroNombre', 'Nuevo rubro')
            ->set('nuevoRubroActividades', ['agricultura'])
            ->set('nuevoRubroDefault', 'ganaderia')
            ->call('guardarRubro')->assertHasErrors(['nuevoRubroDefault'])
            ->set('nuevoRubroDefault', 'agricultura')
            ->set('nuevoRubroNombre', 'Servicios de cosecha')
            ->call('guardarRubro')->assertHasErrors(['nuevoRubroNombre']);
        $this->assertSame(0, Rubro::count());
    }

    public function test_cuit_con_y_sin_guiones_no_crea_dos_proveedores_en_la_misma_empresa(): void
    {
        $this->proveedor();
        Livewire::test(GestionProveedores::class)
            ->call('abrirModalCrear')
            ->set('nombre', 'Duplicado')->set('cuit', '30123456789')
            ->call('guardar')->assertHasErrors(['cuit']);
        $this->assertSame(1, Proveedor::count());
        $this->actingAs(new Usuario(['id_empresa' => $this->empresaB]));
        Livewire::test(GestionProveedores::class)
            ->call('abrirModalCrear')
            ->set('nombre', 'Otra empresa')->set('cuit', '30123456789')
            ->call('guardar')->assertHasNoErrors();
        $this->assertSame(2, Proveedor::withoutGlobalScope('empresa')->count());
    }

    public function test_cambiar_proveedor_actualiza_default_pero_conserva_imputacion_manual(): void
    {
        $cosechadora = $this->proveedor();
        $veterinario = $this->proveedor(['nombre' => 'Veterinario', 'cuit' => '30999999999', 'rubro' => 'veterinaria']);
        $compra = new GestionCompras;
        $compra->id_proveedor = $cosechadora->id;
        $compra->updatedIdProveedor();
        $this->assertSame('cosecha', $compra->rubro);
        $this->assertSame('agricultura', $compra->actividad);
        $compra->id_proveedor = $veterinario->id;
        $compra->updatedIdProveedor();
        $this->assertSame('ganaderia', $compra->actividad);
        $compra->actividad = 'feedlot';
        $compra->updatedActividad();
        $compra->id_proveedor = $cosechadora->id;
        $compra->updatedIdProveedor();
        $this->assertSame('feedlot', $compra->actividad);
        $compra->rubro = 'siembra';
        $compra->updatedRubro();
        $this->assertSame('feedlot', $compra->actividad);
        $compra->usarActividadPredeterminada();
        $this->assertSame('agricultura', $compra->actividad);
    }

    public function test_editar_default_del_rubro_es_por_empresa_y_conserva_proveedores_historicos(): void
    {
        $proveedor = $this->proveedor(['rubro' => 'combustible', 'actividad' => 'ganaderia']);
        Livewire::test(GestionProveedores::class)
            ->call('editarRubro', 'combustible')
            ->set('nuevoRubroActividades', ['agricultura'])
            ->set('nuevoRubroDefault', 'agricultura')
            ->call('guardarRubro')->assertHasNoErrors()
            ->call('abrirModalEditar', $proveedor->id)
            ->call('guardar')->assertHasNoErrors();
        $this->assertSame('ganaderia', $proveedor->fresh()->actividad);
        $this->assertSame('agricultura', Rubro::predeterminada('combustible', $this->empresaA));
        $this->assertSame('general', Rubro::predeterminada('combustible', $this->empresaB));
    }

    public function test_importacion_hereda_clasificacion_por_cuit_y_es_idempotente(): void
    {
        $proveedor = $this->proveedor();
        $servicio = new MrbotService;
        $factura = ['fecha' => '05/10/2026', 'tipo' => '1', 'punto_de_venta' => 1, 'numero' => 7,
            'cuit' => '30123456789', 'neto' => '1000', 'iva_21' => '210', 'total' => '1210'];
        $resultado = $servicio->importarComprobantesJson([$factura], $this->empresaA);
        $this->assertSame(0, $resultado['errores']);
        $this->assertSame(1, $resultado['importadas']);
        $compra = Compra::firstOrFail();
        $this->assertSame($proveedor->id, $compra->id_proveedor);
        $this->assertSame('agricultura', $compra->actividad);
        $this->assertSame('cosecha', $compra->rubro);
        $this->assertSame(1, Proveedor::count());
        $this->assertSame(1, $servicio->importarComprobantesJson([$factura], $this->empresaA)['duplicadas']);
        $this->assertNull($this->proveedor(['cuit' => '30888888888', 'rubro' => 'otro'])->actividadPredeterminada());
    }

    public function test_pantalla_de_compra_guarda_y_conserva_la_imputacion_elegida(): void
    {
        $proveedor = $this->proveedor();
        $panel = Livewire::test(GestionCompras::class)
            ->call('abrirModalCrear')
            ->assertSeeHtml('wire:model.live="id_proveedor"')
            ->set('id_proveedor', $proveedor->id)
            ->assertSet('rubro', 'cosecha')->assertSet('actividad', 'agricultura')
            ->set('actividad', 'ganaderia')
            ->set('numero_comprobante', '0001-00000099')
            ->set('items.0.descripcion', 'Servicio de cosecha')
            ->set('items.0.cantidad', '1')
            ->set('items.0.precio_unitario', '1000')
            ->call('guardar')->assertHasNoErrors();
        $compra = Compra::firstOrFail();
        $this->assertSame('ganaderia', $compra->actividad);
        $panel->call('abrirModalEditar', $compra->id)
            ->assertSet('actividad', 'ganaderia')
            ->set('rubro', 'siembra')->assertSet('actividad', 'ganaderia')
            ->call('guardar')->assertHasNoErrors();
        $this->assertSame('ganaderia', $compra->fresh()->actividad);
        $panel->call('abrirModalEditar', $compra->id)
            ->call('usarActividadPredeterminada')->assertSet('actividad', 'agricultura')
            ->call('guardar')->assertHasNoErrors();
        $this->assertSame('agricultura', $compra->fresh()->actividad);
    }

    public function test_administracion_de_rubros_requiere_permiso(): void
    {
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        $this->expectException(AuthorizationException::class);
        (new GestionProveedores)->guardarRubro();
    }
}
