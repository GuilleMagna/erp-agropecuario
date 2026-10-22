<?php

namespace Tests\Feature;

use App\Livewire\Admin\GestionUbicaciones;
use App\Livewire\Reportes\ReporteGastos;
use App\Models\Compra;
use App\Models\Establecimiento;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class UbicacionesTest extends TestCase
{
    private string $empresa = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        Schema::create('empresas', fn (Blueprint $t) => $t->uuid('id')->primary());
        DB::table('empresas')->insert(['id' => $this->empresa]);
        (require database_path('migrations/2024_01_01_000002_create_establecimientos_table.php'))->up();
        (require database_path('migrations/2026_08_01_165901_add_zona_to_establecimientos_table.php'))->up();
        foreach (['proveedores', 'gastos_no_arca', 'compras'] as $tabla) {
            Schema::create($tabla, function (Blueprint $t) {
                $t->string('id')->primary();
                $t->uuid('id_empresa')->nullable();
                $t->string('zona')->nullable();
                $t->string('rubro')->nullable();
                $t->timestamps();
            });
        }
        Schema::table('compras', function (Blueprint $t) {
            $t->uuid('id_establecimiento')->nullable();
            $t->date('fecha')->nullable();
            $t->string('estado')->default('pendiente');
            $t->decimal('total', 14, 2)->default(0);
        });
        Schema::create('pagos_gasto_no_arca', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('id_gasto');
            $t->date('mes');
            $t->decimal('importe', 14, 2);
        });
        (require database_path('migrations/2026_10_05_000001_create_rubros_table.php'))->up();
        (require database_path('migrations/2026_10_15_120000_add_deleted_at_to_rubros_table.php'))->up();
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        Gate::before(fn () => true);
        $this->actingAs(new Usuario(['id_empresa' => $this->empresa]));
    }

    private function migrar(): void
    {
        (require database_path('migrations/2026_10_22_120000_create_ubicaciones_table.php'))->up();
    }

    public function test_migra_campos_y_compras_conservando_importes_e_inmuebles(): void
    {
        Schema::create('inmuebles', fn (Blueprint $t) => $t->string('id')->primary());
        DB::table('inmuebles')->insert(['id' => 'departamento']);
        DB::table('establecimientos')->insert(['id' => 'campo', 'id_empresa' => $this->empresa, 'nombre' => 'Campo A', 'zona' => 'corrientes']);
        DB::table('compras')->insert(['id' => 'compra', 'id_establecimiento' => 'campo', 'zona' => 'general', 'total' => 123]);
        $this->migrar();
        $campo = Establecimiento::findOrFail('campo');
        $this->assertSame('corrientes', Ubicacion::findOrFail($campo->id_ubicacion)->id_padre);
        $this->assertSame($campo->id_ubicacion, DB::table('compras')->value('zona'));
        $this->assertEquals(123, DB::table('compras')->sum('total'));
        $this->assertSame('departamento', DB::table('inmuebles')->value('id'));
        (require database_path('migrations/2026_10_22_120000_create_ubicaciones_table.php'))->down();
        $this->assertSame('corrientes', DB::table('compras')->value('zona'));
    }

    public function test_crear_campo_desde_ubicaciones_crea_una_sola_ficha_y_se_mantiene_vinculada(): void
    {
        $this->migrar();
        Livewire::test(GestionUbicaciones::class)->set('nombre', 'Campo A')->set('tipo', 'campo')
            ->set('id_padre', 'corrientes')->call('guardar')->assertHasNoErrors();
        $campo = Establecimiento::sole();
        $ubicacion = Ubicacion::findOrFail($campo->id_ubicacion);
        Livewire::test(GestionUbicaciones::class)->call('editar', $ubicacion->id)
            ->set('nombre', 'Campo Renombrado')->set('id_padre', 'santa_fe')->call('guardar')->assertHasNoErrors();
        $this->assertSame(1, Establecimiento::count());
        $this->assertSame('Campo Renombrado', $campo->fresh()->nombre);
        $campo->refresh()->update(['nombre' => 'Nombre desde ficha', 'zona' => 'corrientes']);
        $this->assertSame('Nombre desde ficha', $ubicacion->fresh()->nombre);
        $this->assertSame('corrientes', $ubicacion->fresh()->id_padre);
        $this->assertCount(4, Ubicacion::opciones());
    }

    public function test_rechaza_ciclos_incluso_por_modelo(): void
    {
        $this->migrar();
        $campo = Ubicacion::create(['nombre' => 'Campo A', 'tipo' => 'campo', 'id_padre' => 'corrientes']);
        $this->expectException(ValidationException::class);
        Ubicacion::findOrFail('corrientes')->update(['id_padre' => $campo->id]);
    }

    public function test_compra_hereda_ubicacion_del_establecimiento_y_rechaza_otra_empresa(): void
    {
        $this->migrar();
        $campo = Establecimiento::create(['nombre' => 'Campo A', 'zona' => 'corrientes']);
        $compra = Compra::create(['id_establecimiento' => $campo->id, 'zona' => 'santa_fe']);
        $this->assertSame($campo->id_ubicacion, $compra->zona);
        $this->expectException(ValidationException::class);
        Compra::create(['id_empresa' => 'otra-empresa', 'id_establecimiento' => $campo->id]);
    }

    public function test_reporte_suma_provincia_y_descendientes_una_sola_vez_y_permite_filtrar_campo(): void
    {
        $this->migrar();
        $ciudad = Ubicacion::create(['nombre' => 'Mercedes', 'tipo' => 'ciudad', 'id_padre' => 'corrientes']);
        $campo = Ubicacion::create(['nombre' => 'Campo A', 'tipo' => 'campo', 'id_padre' => $ciudad->id]);
        foreach (['corrientes' => 100, $campo->id => 200, 'santa_fe' => 400] as $zona => $importe) {
            Compra::create(['zona' => $zona, 'rubro' => 'veterinaria', 'total' => $importe, 'fecha' => '2026-10-10']);
        }
        DB::table('gastos_no_arca')->insert(['id' => 'veterinario', 'zona' => $campo->id, 'rubro' => 'veterinaria']);
        DB::table('pagos_gasto_no_arca')->insert(['id' => 'pago', 'id_gasto' => 'veterinario', 'mes' => '2026-10-01', 'importe' => 50]);
        $panel = Livewire::test(ReporteGastos::class)->set('desde', '2026-10')->set('hasta', '2026-10');
        $serie = collect($panel->viewData('porZona'));
        $this->assertEquals(750, $serie->sum('importe'));
        $this->assertEquals(350, $serie->firstWhere('codigo', 'corrientes')['importe']);
        $panel->set('zona', 'corrientes')->assertHasNoErrors();
        $this->assertEquals(350, $panel->viewData('filas')->sum('importe'));
        $panel->set('zona', $campo->id)->assertHasNoErrors();
        $this->assertEquals(250, $panel->viewData('filas')->sum('importe'));
        $this->assertSame('Corrientes / Mercedes / Campo A', Ubicacion::opciones()[$campo->id]);
    }

    public function test_gestion_exige_permiso(): void
    {
        $this->migrar();
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        Gate::before(fn () => false);
        Livewire::test(GestionUbicaciones::class)->assertForbidden();
    }
}
