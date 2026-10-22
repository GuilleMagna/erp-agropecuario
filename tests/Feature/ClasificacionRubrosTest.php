<?php

namespace Tests\Feature;

use App\Livewire\Compras\GestionCompras;
use App\Livewire\Compras\GestionProveedores;
use App\Livewire\Finanzas\GestionGastosNoArca;
use App\Livewire\Reportes\ReporteCompras;
use App\Livewire\Reportes\ReporteGastos;
use App\Models\Compra;
use App\Models\GastoNoArca;
use App\Models\PagoGastoNoArca;
use App\Models\Proveedor;
use App\Models\Rubro;
use App\Models\Usuario;
use App\Services\MrbotService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        (require database_path('migrations/2026_10_15_120000_add_deleted_at_to_rubros_table.php'))->up();
        (require database_path('migrations/2026_06_26_000015_create_proveedores_table.php'))->up();
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('actividad')->nullable();
            $table->string('zona')->nullable();
        });
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

    private function comprobante(array $datos = []): Compra
    {
        return Compra::create(array_merge([
            'tipo_comprobante' => 'factura_a', 'numero_comprobante' => '0001-123',
            'fecha' => '2026-10-01', 'estado' => 'recibida', 'subtotal' => 100,
            'iva_importe' => 21, 'total' => 121,
        ], $datos));
    }

    public function test_completar_pendientes_preserva_manuales_y_aisla_empresa(): void
    {
        $p = $this->proveedor();
        $pendiente = $this->comprobante(['id_proveedor' => $p->id]);
        $manual = $this->comprobante(['id_proveedor' => $p->id, 'actividad' => 'ganaderia', 'rubro' => 'alquileres']);
        $otra = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $p->id]);
        $sinProveedor = $this->comprobante();
        $panel = Livewire::test(GestionCompras::class)->call('completarClasificacion')->assertHasNoErrors();
        $this->assertSame('agricultura', $pendiente->fresh()->actividad);
        $this->assertSame('cosecha', $pendiente->fresh()->rubro);
        $this->assertSame('ganaderia', $manual->fresh()->actividad);
        $this->assertSame('alquileres', $manual->fresh()->rubro);
        $this->assertNull($otra->fresh()->actividad);
        $this->assertNull($sinProveedor->fresh()->actividad);
        $panel->call('completarClasificacion')->assertSee('0 comprobantes completados');
    }

    public function test_informe_resta_creditos_excluye_cancelados_y_otra_empresa(): void
    {
        $this->comprobante(['rubro' => 'cosecha', 'actividad' => 'agricultura']);
        $this->comprobante(['rubro' => 'cosecha', 'actividad' => 'agricultura', 'tipo_comprobante' => 'nota_credito', 'subtotal' => -20, 'iva_importe' => -4.2, 'total' => -24.2]);
        $this->comprobante(['estado' => 'cancelada']);
        $this->comprobante(['id_empresa' => $this->empresaB]);
        Livewire::test(ReporteCompras::class)
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 1 && (float) $filas->sum('importe') === 96.8 && (int) $filas->sum('cantidad') === 2)
            ->set('actividad', 'ganaderia')->assertViewHas('filas', fn ($filas) => $filas->isEmpty());
    }

    public function test_informe_exporta_y_actualizacion_exige_permiso(): void
    {
        $this->comprobante(['rubro' => 'cosecha', 'actividad' => 'agricultura']);
        Livewire::test(ReporteCompras::class)->call('exportar')->assertFileDownloaded('informe-compras.csv');
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        Gate::before(fn ($user, $ability) => false);
        Livewire::test(GestionCompras::class)->call('completarClasificacion')->assertForbidden();
    }

    public function test_listado_muestra_sugerencias_sin_modificar_comprobantes(): void
    {
        $p = $this->proveedor();
        $compra = $this->comprobante(['id_proveedor' => $p->id]);
        Livewire::test(GestionCompras::class)->assertSee('Sugerida')->assertSee('Servicios de cosecha')->assertSee('Agricultura');
        $this->assertNull($compra->fresh()->actividad);
        $this->assertNull($compra->fresh()->rubro);
        $manual = $this->comprobante(['id_proveedor' => $p->id, 'actividad' => 'ganaderia', 'rubro' => 'alquileres']);
        $this->assertSame('ganaderia', $manual->actividadSugerida());
        $this->assertSame('alquileres', $manual->rubroSugerido());
    }

    public function test_graficos_agrupan_pendientes_y_conservan_importes_negativos(): void
    {
        foreach ([null, '', 'otro'] as $rubro) {
            $this->comprobante(['rubro' => $rubro, 'actividad' => null]);
        }
        $this->comprobante(['rubro' => 'cosecha', 'actividad' => 'agricultura', 'total' => -25]);
        $this->comprobante(['estado' => 'cancelada', 'total' => 5000]);
        $this->comprobante(['id_empresa' => $this->empresaB, 'total' => 9000]);
        Livewire::test(ReporteCompras::class)
            ->assertSee('Sin rubro')->assertSee('Ver y completar')
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 3)
            ->assertViewHas('porRubro', fn ($serie) => count($serie) === 3
                && collect($serie)->firstWhere('codigo', 'sin_clasificar')['cantidad'] === 2
                && collect($serie)->firstWhere('codigo', 'sin_clasificar')['importe'] === 242.0
                && collect($serie)->firstWhere('codigo', 'cosecha')['importe'] === -25.0)
            ->assertViewHas('porActividad', fn ($serie) => count($serie) === 2)
            ->set('rubro', 'cosecha')->assertViewHas('porRubro', fn ($serie) => count($serie) === 1);
    }

    public function test_enlaces_del_informe_abren_el_mismo_conjunto_de_compras(): void
    {
        $p = $this->proveedor();
        $pendiente = $this->comprobante(['id_proveedor' => $p->id, 'actividad' => '']);
        $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'cosecha']);
        $this->comprobante(['id_proveedor' => $p->id, 'estado' => 'cancelada']);
        $this->comprobante(['id_proveedor' => $p->id, 'fecha' => '2026-09-01']);
        $this->comprobante(['id_empresa' => $this->empresaB]);
        $reporte = Livewire::test(ReporteCompras::class)->set('desde', '2026-10-01')->set('hasta', '2026-10-31')->set('proveedor', $p->id);
        $url = $reporte->instance()->enlaceCompras('sin_clasificar', 'sin_clasificar', 'sin_asignar', 'sin_asignar');
        parse_str(parse_url($url, PHP_URL_QUERY), $filtros);
        $this->assertSame($this->empresaA, $filtros['empresa']);
        Livewire::test(GestionCompras::class)
            ->set('filtroFechaDesde', $filtros['filtroFechaDesde'])->set('filtroFechaHasta', $filtros['filtroFechaHasta'])
            ->set('filtroProveedor', $filtros['filtroProveedor'])->set('filtroRubro', $filtros['filtroRubro'])
            ->set('filtroActividad', $filtros['filtroActividad'])->set('filtroLote', $filtros['filtroLote'])
            ->set('filtroCampana', $filtros['filtroCampana'])->set('filtroExcluirCanceladas', true)
            ->assertViewHas('compras', fn ($compras) => $compras->total() === 1 && $compras->first()->id === $pendiente->id)
            ->call('limpiarFiltros')->assertSet('filtroRubro', '')->assertSet('filtroExcluirCanceladas', false);
    }

    public function test_guardar_rubro_del_proveedor_completa_todas_sus_compras_pendientes(): void
    {
        $p = $this->proveedor(['rubro' => 'otro']);
        $pendientes = [];
        foreach ([null, '', 'otro'] as $rubro) {
            $pendientes[] = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => $rubro]);
        }
        $actividadManual = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'otro', 'actividad' => 'ganaderia']);
        $rubroManual = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'combustible', 'actividad' => 'general']);
        $otraEmpresa = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $p->id]);
        $otroProveedor = $this->comprobante(['id_proveedor' => $this->proveedor(['cuit' => '30888888888'])->id]);
        Livewire::test(GestionProveedores::class)->call('abrirModalEditar', $p->id)
            ->set('rubro', 'reparaciones')->set('actividad', 'agricultura')->call('guardar')->assertHasNoErrors();
        foreach ($pendientes as $compra) {
            $this->assertSame('reparaciones', $compra->fresh()->rubro);
            $this->assertSame('agricultura', $compra->fresh()->actividad);
            $this->assertSame('121.00', $compra->fresh()->total);
            $this->assertSame('2026-10-01', $compra->fresh()->fecha->toDateString());
        }
        $this->assertSame('reparaciones', $actividadManual->fresh()->rubro);
        $this->assertSame('ganaderia', $actividadManual->fresh()->actividad);
        $this->assertSame('reparaciones', $rubroManual->fresh()->rubro);
        $this->assertSame('general', $rubroManual->fresh()->actividad);
        $this->assertNull($otraEmpresa->fresh()->rubro);
        $this->assertNull($otroProveedor->fresh()->rubro);
        Livewire::test(GestionCompras::class)->set('filtroProveedor', $p->id)->set('filtroRubro', 'sin_clasificar')
            ->assertViewHas('compras', fn ($compras) => $compras->total() === 0);
    }

    public function test_completar_y_editar_conservan_otro_como_rubro_valido(): void
    {
        $p = $this->proveedor(['rubro' => 'reparaciones', 'actividad' => 'agricultura']);
        $compra = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'otro']);
        $manual = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'otro', 'actividad' => 'ganaderia']);
        Livewire::test(GestionCompras::class)->call('abrirModalEditar', $compra->id)
            ->assertSet('rubro', 'otro');
        Livewire::test(GestionCompras::class)->call('completarClasificacion')->assertHasNoErrors();
        $this->assertSame('otro', $compra->fresh()->rubro);
        $this->assertNull($compra->fresh()->actividad);
        $this->assertSame('otro', $manual->fresh()->rubro);
        $this->assertSame('ganaderia', $manual->fresh()->actividad);
    }

    public function test_cambio_masivo_de_rubro_incluye_todas_las_paginas_filtradas(): void
    {
        $p = $this->proveedor();
        $ids = [];
        for ($i = 0; $i < 25; $i++) {
            $ids[] = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'cosecha', 'actividad' => 'agricultura'])->id;
        }
        $otroProveedor = $this->comprobante(['rubro' => 'cosecha']);
        $otraEmpresa = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $p->id, 'rubro' => 'cosecha']);
        $otraFecha = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'cosecha', 'fecha' => '2026-09-01']);
        Livewire::test(GestionCompras::class)->set('filtroProveedor', $p->id)->set('filtroFechaDesde', '2026-10-01')
            ->call('seleccionarFiltrados')->assertSet('seleccionados', fn ($seleccionados) => count($seleccionados) === 25)
            ->call('abrirModalMasivo')->set('accionMasiva', 'rubro')->set('valorMasivoRubro', 'reparaciones')
            ->call('aplicarMasivo')->assertHasNoErrors()->assertSet('seleccionados', []);
        $this->assertSame(25, Compra::whereIn('id', $ids)->where('rubro', 'reparaciones')->where('actividad', 'agricultura')->where('total', 121)->count());
        $this->assertSame('cosecha', $otroProveedor->fresh()->rubro);
        $this->assertSame('cosecha', $otraEmpresa->fresh()->rubro);
        $this->assertSame('cosecha', $otraFecha->fresh()->rubro);
        $this->assertSame('cosecha', $p->fresh()->rubro);
    }

    public function test_rubro_masivo_valida_catalogo_y_no_modifica_otra_empresa(): void
    {
        $propia = $this->comprobante(['rubro' => 'cosecha']);
        $ajena = $this->comprobante(['id_empresa' => $this->empresaB, 'rubro' => 'cosecha']);
        Livewire::test(GestionCompras::class)->set('seleccionados', [$propia->id, $ajena->id])
            ->set('accionMasiva', 'rubro')->set('valorMasivoRubro', 'no_existe')
            ->call('aplicarMasivo')->assertHasErrors('valorMasivoRubro')
            ->set('valorMasivoRubro', 'seguros')->call('aplicarMasivo')->assertHasNoErrors();
        $this->assertSame('seguros', $propia->fresh()->rubro);
        $this->assertSame('cosecha', $ajena->fresh()->rubro);
    }

    public function test_rubros_base_y_personalizados_se_ordenan_por_nombre_sin_acentos(): void
    {
        Rubro::create(['codigo' => 'salud_abaco', 'nombre' => 'Ábaco Salud', 'actividades' => ['general'], 'actividad_default' => 'general']);
        Rubro::create(['codigo' => 'aa_final', 'nombre' => 'Zeta Servicios', 'actividades' => ['general'], 'actividad_default' => 'general']);
        $opciones = Rubro::opciones();
        $this->assertSame('salud_abaco', array_key_first($opciones));
        $this->assertSame('aa_final', array_key_last($opciones));
        $claves = array_keys($opciones);
        $this->assertLessThan(array_search('seguros', $claves), array_search('reparaciones', $claves));
        $this->assertSame('Servicios de cosecha', $opciones['cosecha']);
    }

    public function test_eliminar_rubro_base_deja_proveedores_y_compras_sin_rubro_solo_en_empresa_activa(): void
    {
        $p = $this->proveedor(['actividad' => 'agricultura']);
        $compra = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'cosecha', 'actividad' => 'ganaderia']);
        $pOtra = $this->proveedor(['id_empresa' => $this->empresaB]);
        $compraOtra = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $pOtra->id, 'rubro' => 'cosecha']);
        Livewire::test(GestionProveedores::class)->call('abrirCatalogo')->call('eliminarRubro', 'cosecha')->assertHasNoErrors();
        $this->assertNull($p->fresh()->rubro);
        $this->assertSame('agricultura', $p->fresh()->actividad);
        $this->assertNull($compra->fresh()->rubro);
        $this->assertSame('ganaderia', $compra->fresh()->actividad);
        $this->assertSame('121.00', $compra->fresh()->total);
        $this->assertSame('cosecha', $pOtra->fresh()->rubro);
        $this->assertSame('cosecha', $compraOtra->fresh()->rubro);
        $this->assertArrayNotHasKey('cosecha', Rubro::catalogo($this->empresaA));
        $this->assertArrayHasKey('cosecha', Rubro::catalogo($this->empresaB));
        $this->assertTrue(Rubro::withTrashed()->where('codigo', 'cosecha')->firstOrFail()->trashed());
        Livewire::test(GestionCompras::class)->set('filtroRubro', 'sin_clasificar')
            ->assertViewHas('compras', fn ($compras) => $compras->total() === 1);
    }

    public function test_eliminar_personalizado_permite_crearlo_de_nuevo_sin_reclasificar_compras(): void
    {
        Rubro::create(['codigo' => 'obra_social', 'nombre' => 'Obra social', 'actividades' => ['general'], 'actividad_default' => 'general']);
        $compra = $this->comprobante(['rubro' => 'obra_social']);
        $panel = Livewire::test(GestionProveedores::class)->call('eliminarRubro', 'obra_social');
        $this->assertArrayNotHasKey('obra_social', Rubro::opciones());
        $panel->set('nuevoRubroNombre', 'Obra social')->set('nuevoRubroActividades', ['general'])->set('nuevoRubroDefault', 'general')
            ->call('guardarRubro')->assertHasNoErrors();
        $this->assertArrayHasKey('obra_social', Rubro::opciones());
        $this->assertNull($compra->fresh()->rubro);
    }

    public function test_eliminar_rubro_exige_permiso_de_edicion_de_compras(): void
    {
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        Gate::before(fn ($user, $ability) => $ability === 'compras.proveedores.gestionar');
        Livewire::test(GestionProveedores::class)->call('eliminarRubro', 'cosecha')->assertForbidden();
        $this->assertArrayHasKey('cosecha', Rubro::opciones());
    }

    public function test_zona_se_infiere_de_provincia_o_direccion_sin_confundir_calle(): void
    {
        $this->assertSame('santa_fe', Proveedor::inferirZona(null));
        $this->assertSame('corrientes', Proveedor::inferirZona('CORRIENTES'));
        $this->assertSame('corrientes', Proveedor::inferirZona(null, null, 'Ruta 12, Corrientes'));
        $this->assertSame('corrientes', Proveedor::inferirZona(null, null, 'Provincia de Corrientes'));
        $this->assertSame('santa_fe', Proveedor::inferirZona(null, null, 'Corrientes 123'));
        $this->assertSame('santa_fe', Proveedor::inferirZona('Santa Fe', null, 'Corrientes 123'));
        Livewire::test(GestionProveedores::class)->call('abrirModalCrear')->set('provincia', 'Corrientes')
            ->assertSet('zona', 'corrientes')->set('zona', 'santa_fe')->set('ciudad', 'Corrientes')->assertSet('zona', 'santa_fe');
    }

    public function test_cambiar_zona_del_cuit_actualiza_compras_y_graficos_solo_de_su_empresa(): void
    {
        $p = $this->proveedor();
        $compra = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'cosecha', 'actividad' => 'agricultura']);
        $otra = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $p->id, 'zona' => 'santa_fe']);
        $this->assertSame('santa_fe', $compra->zona);
        Livewire::test(GestionProveedores::class)->call('abrirModalEditar', $p->id)->set('zona', 'corrientes')->call('guardar')->assertHasNoErrors();
        $this->assertSame('corrientes', $compra->fresh()->zona);
        $this->assertSame('santa_fe', $otra->fresh()->zona);
        $this->assertSame('121.00', $compra->fresh()->total);
        $this->assertSame('agricultura', $compra->fresh()->actividad);
        $this->assertSame('corrientes', $this->comprobante(['id_proveedor' => $p->id])->zona);
        $report = Livewire::test(ReporteCompras::class)->set('zona', 'corrientes')
            ->assertViewHas('porZona', fn ($serie) => count($serie) === 1 && $serie[0]['codigo'] === 'corrientes' && $serie[0]['importe'] === 242.0);
        parse_str(parse_url($report->instance()->enlaceCompras(), PHP_URL_QUERY), $filtros);
        $this->assertSame('corrientes', $filtros['filtroZona']);
        Livewire::test(GestionCompras::class)->set('filtroZona', 'corrientes')
            ->assertViewHas('compras', fn ($compras) => $compras->total() === 2);
    }

    public function test_migracion_zonas_completa_historial_y_preserva_corrientes_existente(): void
    {
        $p = $this->proveedor(['provincia' => 'Corrientes']);
        $compra = $this->comprobante(['id_proveedor' => $p->id]);
        DB::table('proveedores')->where('id', $p->id)->update(['zona' => null]);
        DB::table('compras')->where('id', $compra->id)->update(['zona' => 'general']);
        $sf = $this->proveedor(['cuit' => '30888888888', 'direccion' => 'Corrientes 123']);
        $vieja = $this->comprobante(['id_proveedor' => $sf->id, 'zona' => 'corrientes']);
        $migracion = require database_path('migrations/2026_10_15_130000_clasificar_zonas_de_proveedores_y_compras.php');
        $migracion->up();
        $this->assertSame('corrientes', $p->fresh()->zona);
        $this->assertSame('corrientes', $compra->fresh()->zona);
        $this->assertSame('santa_fe', $sf->fresh()->zona);
        $this->assertSame('corrientes', $vieja->fresh()->zona);
        $migracion->up();
        $this->assertSame('corrientes', $vieja->fresh()->zona);
    }

    public function test_el_trebol_historico_se_agrupa_y_enlaza_como_santa_fe(): void
    {
        $this->comprobante(['zona' => 'el_trebol']);
        $this->comprobante(['zona' => 'santa_fe']);
        $this->comprobante(['zona' => 'corrientes']);
        Livewire::test(ReporteCompras::class)->set('zona', 'santa_fe')
            ->assertViewHas('porZona', fn ($serie) => count($serie) === 1 && $serie[0]['cantidad'] === 2);
        Livewire::test(GestionCompras::class)->set('filtroZona', 'santa_fe')
            ->assertViewHas('compras', fn ($compras) => $compras->total() === 2);
    }

    private function prepararGastos(bool $conRubro = true, bool $conZona = true): void
    {
        (require database_path('migrations/2026_07_01_000002_create_gastos_no_arca_table.php'))->up();
        Schema::table('gastos_no_arca', function (Blueprint $table) use ($conRubro, $conZona) {
            if ($conRubro) {
                $table->string('rubro')->nullable();
            }
            if ($conZona) {
                $table->string('zona')->default('santa_fe');
            }
            $table->uuid('id_inmueble')->nullable();
        });
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre');
            $table->string('localidad')->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    public function test_informe_gastos_separa_compras_no_arca_generales_y_otra_empresa(): void
    {
        $this->prepararGastos();
        $this->comprobante(['rubro' => 'energia', 'total' => 121]);
        $this->comprobante(['rubro' => 'energia', 'total' => -21, 'tipo_comprobante' => 'nota_credito']);
        $this->comprobante(['rubro' => 'energia', 'estado' => 'cancelada', 'total' => 500]);
        foreach ([$this->empresaA, null, $this->empresaB] as $empresa) {
            $g = GastoNoArca::create(['nombre' => 'EPE servicio', 'categoria' => 'servicios_publicos', 'id_empresa' => $empresa]);
            PagoGastoNoArca::create(['id_gasto' => $g->id, 'mes' => '2026-10-01', 'importe' => 50]);
            PagoGastoNoArca::create(['id_gasto' => $g->id, 'mes' => '2025-10-01', 'importe' => 5000]);
        }
        Livewire::test(ReporteGastos::class)->set('desde', '2026-10')->set('hasta', '2026-10')
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 3 && $filas->sum('importe') === 200.0)
            ->set('incluirGenerales', false)->assertViewHas('filas', fn ($filas) => $filas->sum('importe') === 150.0)
            ->set('origen', 'compras')->assertViewHas('filas', fn ($filas) => $filas->sum('importe') === 100.0)
            ->call('exportar')->assertFileDownloaded('informe-gastos.csv');
    }

    public function test_gasto_epe_se_subclasifica_y_permite_cambiar_rubro(): void
    {
        $this->prepararGastos();
        $g = GastoNoArca::create(['nombre' => 'EPE EL TREBOL', 'categoria' => 'servicios_publicos']);
        $this->assertSame('energia', $g->rubro);
        $this->assertSame('gas', GastoNoArca::rubroSugerido('Litoral Gas', 'servicios_publicos'));
        $this->assertSame('servicios_agua', GastoNoArca::rubroSugerido('Aguas Santafesinas', 'servicios_publicos'));
        Livewire::test(GestionGastosNoArca::class)->call('abrirModalEditar', $g->id)
            ->assertSet('rubro', 'energia')->set('rubro', 'gas')->call('guardarServicio')->assertHasNoErrors();
        $this->assertSame('gas', $g->fresh()->rubro);
    }

    public function test_reporte_gastos_exige_permisos_de_ambos_origenes(): void
    {
        $this->prepararGastos();
        Gate::swap(new \Illuminate\Auth\Access\Gate($this->app, fn () => auth()->user()));
        Gate::before(fn ($user, $ability) => $ability !== 'compras.ver');
        Livewire::test(ReporteGastos::class)->assertForbidden();
    }

    public function test_migracion_gastos_clasifica_epe_sin_cambiar_importes(): void
    {
        $this->prepararGastos(false);
        $id = (string) Str::uuid();
        DB::table('gastos_no_arca')->insert(['id' => $id, 'nombre' => 'EPE CASA', 'categoria' => 'servicios_publicos', 'orden' => 0, 'activo' => true]);
        $proveedor = $this->proveedor(['nombre' => 'EMPRESA PROVINCIAL DE LA ENERGIA']);
        $compra = $this->comprobante(['id_proveedor' => $proveedor->id, 'rubro' => 'insumos']);
        (require database_path('migrations/2026_10_15_140000_clasificar_rubros_de_gastos.php'))->up();
        $this->assertSame('energia', DB::table('gastos_no_arca')->where('id', $id)->value('rubro'));
        $this->assertSame('energia', $proveedor->fresh()->rubro);
        $this->assertSame('energia', $compra->fresh()->rubro);
        $this->assertSame('121.00', $compra->fresh()->total);
    }

    public function test_zona_no_arca_clasifica_historial_y_filtra_informe_conjunto(): void
    {
        $this->prepararGastos();
        $gasto = GastoNoArca::create(['nombre' => 'EPE servicio', 'categoria' => 'servicios_publicos', 'id_empresa' => $this->empresaA]);
        PagoGastoNoArca::create(['id_gasto' => $gasto->id, 'mes' => '2026-08-01', 'importe' => 200]);
        $this->comprobante(['fecha' => '2026-08-01', 'zona' => 'corrientes', 'rubro' => 'energia', 'total' => 100]);
        $this->comprobante(['fecha' => '2026-08-01', 'zona' => 'santa_fe', 'rubro' => 'energia', 'total' => 50]);
        Livewire::test(GestionGastosNoArca::class)->call('abrirModalEditar', $gasto->id)->set('zona', 'corrientes')->call('guardarServicio')->assertHasNoErrors();
        $this->assertSame('corrientes', $gasto->fresh()->zona);
        $this->assertSame('200.00', $gasto->pagos()->first()->importe);
        $report = Livewire::test(ReporteGastos::class)->set('desde', '2026-08')->set('hasta', '2026-08')->set('zona', 'corrientes')
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 2 && $filas->sum('importe') === 300.0)
            ->assertViewHas('porZona', fn ($serie) => count($serie) === 1 && $serie[0]['codigo'] === 'corrientes' && $serie[0]['importe'] === 300.0);
        parse_str(parse_url($report->instance()->enlace('energia', 'no_arca', 'corrientes'), PHP_URL_QUERY), $filtros);
        $this->assertSame('corrientes', $filtros['filtroZona']);
        Livewire::test(GestionGastosNoArca::class)->set('vistaActiva', 'catalogo')->set('filtroZona', 'corrientes')
            ->assertViewHas('catalogo', fn ($catalogo) => $catalogo->count() === 1);
    }

    public function test_migracion_zonas_no_arca_solo_infiere_corrientes_explicito(): void
    {
        $this->prepararGastos(true, false);
        foreach (['EPE Corrientes', 'EPE Santa Fe', 'EPE calle Corrientes 123'] as $nombre) {
            DB::table('gastos_no_arca')->insert(['id' => (string) Str::uuid(), 'nombre' => $nombre, 'categoria' => 'servicios_publicos', 'orden' => 0, 'activo' => true]);
        }
        (require database_path('migrations/2026_10_15_150000_add_zona_to_gastos_no_arca.php'))->up();
        $this->assertSame('corrientes', DB::table('gastos_no_arca')->where('nombre', 'EPE Corrientes')->value('zona'));
        $this->assertSame('santa_fe', DB::table('gastos_no_arca')->where('nombre', 'EPE Santa Fe')->value('zona'));
        $this->assertSame('santa_fe', DB::table('gastos_no_arca')->where('nombre', 'EPE calle Corrientes 123')->value('zona'));
    }

    public function test_zona_general_se_guarda_en_proveedores_gastos_y_reporte(): void
    {
        $this->prepararGastos();
        $proveedor = $this->proveedor();
        $compra = $this->comprobante(['id_proveedor' => $proveedor->id]);
        Livewire::test(GestionProveedores::class)->call('abrirModalEditar', $proveedor->id)
            ->set('zona', 'general')->call('guardar')->assertHasNoErrors();
        $this->assertSame('general', $proveedor->fresh()->zonaPredeterminada());
        $this->assertSame('general', $compra->fresh()->zona);
        $gasto = GastoNoArca::create(['nombre' => 'Servicio general', 'categoria' => 'otros', 'id_empresa' => $this->empresaA]);
        PagoGastoNoArca::create(['id_gasto' => $gasto->id, 'mes' => '2026-10-01', 'importe' => 50]);
        Livewire::test(GestionGastosNoArca::class)->call('abrirModalEditar', $gasto->id)
            ->set('zona', 'general')->call('guardarServicio')->assertHasNoErrors();
        $this->assertSame('general', $gasto->fresh()->zona);
        Livewire::test(ReporteGastos::class)->set('desde', '2026-10')->set('hasta', '2026-10')->set('zona', 'general')
            ->assertViewHas('porZona', fn ($serie) => count($serie) === 1 && $serie[0]['codigo'] === 'general' && $serie[0]['importe'] === 171.0);
    }

    public function test_zona_masiva_cambia_solo_la_seleccion_y_valida_opciones(): void
    {
        $p = $this->proveedor();
        $ids = [];
        for ($i = 0; $i < 24; $i++) {
            $ids[] = $this->comprobante(['id_proveedor' => $p->id, 'rubro' => 'reparaciones', 'actividad' => 'agricultura', 'zona' => 'santa_fe'])->id;
        }
        $ajena = $this->comprobante(['id_empresa' => $this->empresaB, 'zona' => 'corrientes']);
        $noSeleccionada = $this->comprobante(['zona' => 'santa_fe']);
        $panel = Livewire::test(GestionCompras::class)->set('filtroProveedor', $p->id)->call('seleccionarFiltrados')
            ->call('abrirModalMasivo')->assertSee('Zona')->set('accionMasiva', 'zona')
            ->call('aplicarMasivo')->assertHasErrors('valorMasivoZona')
            ->set('valorMasivoZona', 'invalida')->call('aplicarMasivo')->assertHasErrors('valorMasivoZona')
            ->set('valorMasivoZona', 'general')->call('aplicarMasivo')->assertHasNoErrors()->assertSet('seleccionados', []);
        $this->assertSame(24, Compra::whereIn('id', $ids)->where('zona', 'general')->where('rubro', 'reparaciones')->where('actividad', 'agricultura')->where('total', 121)->count());
        $this->assertSame('santa_fe', $noSeleccionada->fresh()->zona);
        $this->assertSame('santa_fe', $p->fresh()->zona);
        $panel->set('seleccionados', [$ids[0], $ajena->id])->set('accionMasiva', 'zona')->set('valorMasivoZona', 'santa_fe')->call('aplicarMasivo');
        $this->assertSame('corrientes', $ajena->fresh()->zona);
    }

    public function test_el_trebol_no_se_ofrece_y_su_historial_se_unifica_en_santa_fe(): void
    {
        $compra = $this->comprobante(['zona' => 'el_trebol']);
        $general = $this->comprobante(['zona' => 'general']);
        $this->assertSame(['corrientes', 'general', 'santa_fe'], array_keys(Compra::ZONAS));
        $this->assertSame('Santa Fe', $compra->zona_label);
        Livewire::test(GestionCompras::class)->call('abrirModalEditar', $compra->id)->assertSet('zona', 'santa_fe');
        $m = require database_path('migrations/2026_10_19_160000_unificar_zona_el_trebol_en_santa_fe.php');
        $m->up();
        $m->up();
        $this->assertSame('santa_fe', $compra->fresh()->zona);
        $this->assertSame('121.00', $compra->fresh()->total);
        $this->assertSame('general', $general->fresh()->zona);
    }

    public function test_rubro_del_cuit_reemplaza_tambien_comprobantes_ya_clasificados_entre_empresas(): void
    {
        $origen = $this->proveedor(['rubro' => 'otro', 'cuit' => '30-12345678-9']);
        $destino = $this->proveedor(['id_empresa' => $this->empresaB, 'cuit' => '30.12345678.9', 'rubro' => 'combustible', 'actividad' => 'ganaderia', 'zona' => 'corrientes']);
        $propia = $this->comprobante(['id_proveedor' => $origen->id, 'rubro' => 'seguros', 'actividad' => 'general', 'zona' => 'general']);
        $pendiente = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $destino->id, 'rubro' => 'otro', 'actividad' => 'ganaderia', 'zona' => 'corrientes']);
        $manual = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $destino->id, 'rubro' => 'seguros']);
        $otro = $this->proveedor(['id_empresa' => $this->empresaB, 'cuit' => '30888888888', 'rubro' => 'combustible']);
        Livewire::test(GestionProveedores::class)->call('abrirModalEditar', $origen->id)
            ->set('rubro', 'reparaciones')->set('actividad', 'agricultura')->call('guardar')->assertHasNoErrors();
        $this->assertSame('reparaciones', $propia->fresh()->rubro);
        $this->assertSame('general', $propia->fresh()->actividad);
        $this->assertSame('general', $propia->fresh()->zona);
        $this->assertSame('reparaciones', $destino->fresh()->rubro);
        $this->assertSame('ganaderia', $destino->fresh()->actividad);
        $this->assertSame('corrientes', $destino->fresh()->zona);
        $this->assertSame('reparaciones', $pendiente->fresh()->rubro);
        $this->assertSame('ganaderia', $pendiente->fresh()->actividad);
        $this->assertSame('corrientes', $pendiente->fresh()->zona);
        $this->assertSame('121.00', $pendiente->fresh()->total);
        $this->assertSame('reparaciones', $manual->fresh()->rubro);
        $this->assertSame('combustible', $otro->fresh()->rubro);
    }

    public function test_rubro_personalizado_se_copia_al_catalogo_de_la_otra_empresa(): void
    {
        Rubro::create(['codigo' => 'salud_privada', 'nombre' => 'Salud privada', 'actividades' => ['general'], 'actividad_default' => 'general']);
        $p = $this->proveedor(['rubro' => 'otro']);
        $otro = $this->proveedor(['id_empresa' => $this->empresaB, 'rubro' => 'otro']);
        Livewire::test(GestionProveedores::class)->call('abrirModalEditar', $p->id)->set('rubro', 'salud_privada')->set('actividad', 'general')->call('guardar')->assertHasNoErrors();
        $this->assertSame('salud_privada', $otro->fresh()->rubro);
        $this->assertSame('Salud privada', Rubro::opciones($this->empresaB)['salud_privada']);
    }

    public function test_nuevo_proveedor_de_otra_empresa_hereda_rubro_del_cuit(): void
    {
        $this->proveedor(['cuit' => '30-12345678-9', 'rubro' => 'seguros']);
        $nuevo = $this->proveedor(['id_empresa' => $this->empresaB, 'cuit' => '30123456789', 'rubro' => null, 'zona' => 'corrientes']);
        $this->assertSame('seguros', $nuevo->rubro);
        $this->assertSame('corrientes', $nuevo->zona);
    }

    public function test_asignar_otro_al_cuit_tambien_reemplaza_sus_rubros_anteriores(): void
    {
        $origen = $this->proveedor(['rubro' => 'seguros']);
        $destino = $this->proveedor(['id_empresa' => $this->empresaB, 'rubro' => 'reparaciones']);
        $compra = $this->comprobante(['id_proveedor' => $origen->id, 'rubro' => 'seguros', 'actividad' => 'ganaderia']);
        $otra = $this->comprobante(['id_empresa' => $this->empresaB, 'id_proveedor' => $destino->id, 'rubro' => 'reparaciones', 'actividad' => 'general']);
        Livewire::test(GestionProveedores::class)->call('abrirModalEditar', $origen->id)->set('rubro', 'otro')->call('guardar')->assertHasNoErrors();
        $this->assertSame('otro', $compra->fresh()->rubro);
        $this->assertSame('otro', $otra->fresh()->rubro);
        $this->assertSame('ganaderia', $compra->fresh()->actividad);
        $this->assertSame('general', $otra->fresh()->actividad);
    }

    public function test_filtro_proveedores_sin_rubro_excluye_otro(): void
    {
        $ids = [];
        foreach ([null, '', 'otro'] as $i => $rubro) {
            $p = $this->proveedor(['cuit' => '3012345678'.$i, 'rubro' => $rubro]);
            $ids[] = $p->id;
        }
        $this->proveedor(['cuit' => '30999999999', 'rubro' => 'seguros']);
        $this->proveedor(['id_empresa' => $this->empresaB, 'cuit' => '30888888888', 'rubro' => null]);
        $panel = Livewire::test(GestionProveedores::class)->set('filtroRubro', 'sin_clasificar')
            ->assertSee('Sin rubro')->assertViewHas('proveedores', fn ($proveedores) => $proveedores->total() === 2);
        $panel->call('abrirModalEditar', $ids[0])->set('rubro', 'seguros')->set('actividad', 'general')
            ->call('guardar')->assertHasNoErrors()
            ->assertViewHas('proveedores', fn ($proveedores) => $proveedores->total() === 1);
        $panel->set('filtroRubro', 'seguros')->assertViewHas('proveedores', fn ($proveedores) => $proveedores->total() === 2);
    }

    public function test_otro_es_un_grupo_independiente_de_sin_clasificar_en_ambos_reportes(): void
    {
        $this->prepararGastos();
        $otro = $this->comprobante(['rubro' => 'otro', 'total' => 100]);
        $this->comprobante(['rubro' => null, 'total' => 50]);
        Livewire::test(ReporteCompras::class)
            ->assertViewHas('sinRubro', fn ($grupo) => $grupo['cantidad'] === 1 && $grupo['importe'] === 50.0)
            ->assertViewHas('porRubro', fn ($serie) => collect($serie)->firstWhere('codigo', 'otro')['importe'] === 100.0)
            ->set('rubro', 'otro')->assertViewHas('filas', fn ($filas) => $filas->count() === 1 && (float) $filas->sum('importe') === 100.0);
        Livewire::test(ReporteGastos::class)->set('rubro', 'otro')
            ->assertViewHas('filas', fn ($filas) => $filas->count() === 1 && $filas->sum('importe') === 100.0);
        Livewire::test(GestionCompras::class)->set('filtroRubro', 'sin_clasificar')
            ->assertViewHas('compras', fn ($compras) => $compras->total() === 1 && $compras->first()->id !== $otro->id)
            ->set('filtroRubro', 'otro')->assertViewHas('compras', fn ($compras) => $compras->total() === 1 && $compras->first()->id === $otro->id);
    }
}
