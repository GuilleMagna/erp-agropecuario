<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Módulo Admin
    Route::get('/admin/usuarios', fn () => view('admin.gestion-usuarios'))
        ->middleware('can:admin.usuarios.ver')
        ->name('admin.usuarios.index');

    Route::get('/admin/establecimientos', fn () => view('admin.gestion-establecimientos'))
        ->middleware('can:admin.establecimientos.gestionar')
        ->name('admin.establecimientos.index');

    // Módulo Campos
    Route::get('/campos/lotes', fn () => view('campos.gestion-lotes'))
        ->middleware('can:campos.lotes.ver')
        ->name('campos.lotes.index');

    // Módulo Agricultura
    Route::get('/agricultura/campanas', fn () => view('agricultura.gestion-campanas'))
        ->middleware('can:agricultura.campanas.ver')
        ->name('agricultura.campanas.index');

    Route::get('/agricultura/siembras', fn () => view('agricultura.gestion-siembras'))
        ->middleware('can:agricultura.siembra.ver')
        ->name('agricultura.siembras.index');

    Route::get('/agricultura/labores', fn () => view('agricultura.gestion-labores'))
        ->middleware('can:agricultura.labores.ver')
        ->name('agricultura.labores.index');

    Route::get('/agricultura/cosechas', fn () => view('agricultura.gestion-cosechas'))
        ->middleware('can:agricultura.cosecha.ver')
        ->name('agricultura.cosechas.index');

    // Módulo Ganadería
    Route::get('/ganaderia/animales', fn () => view('ganaderia.gestion-animales'))
        ->middleware('can:ganaderia.animales.ver')
        ->name('ganaderia.animales.index');

    Route::get('/ganaderia/movimientos', fn () => view('ganaderia.gestion-movimientos'))
        ->middleware('can:ganaderia.movimientos.ver')
        ->name('ganaderia.movimientos.index');

    Route::get('/ganaderia/pesajes', fn () => view('ganaderia.gestion-pesajes'))
        ->middleware('can:ganaderia.pesajes.ver')
        ->name('ganaderia.pesajes.index');

    Route::get('/ganaderia/sanidad', fn () => view('ganaderia.gestion-sanidad'))
        ->middleware('can:ganaderia.sanidad.ver')
        ->name('ganaderia.sanidad.index');

    Route::get('/ganaderia/reproduccion', fn () => view('ganaderia.gestion-reproduccion'))
        ->middleware('can:ganaderia.reproduccion.ver')
        ->name('ganaderia.reproduccion.index');

    // Módulo Finanzas
    Route::get('/finanzas/cuentas', fn () => view('finanzas.gestion-cuentas'))
        ->middleware('can:finanzas.cuentas.ver')
        ->name('finanzas.cuentas.index');

    Route::get('/finanzas/transacciones', fn () => view('finanzas.gestion-transacciones'))
        ->middleware('can:finanzas.transacciones.ver')
        ->name('finanzas.transacciones.index');

    // Módulo Insumos
    Route::get('/insumos/catalogo', fn () => view('insumos.gestion-catalogo'))
        ->middleware('can:insumos.catalogo.ver')
        ->name('insumos.catalogo.index');

    Route::get('/insumos/movimientos', fn () => view('insumos.gestion-movimientos'))
        ->middleware('can:insumos.movimientos.ver')
        ->name('insumos.movimientos.index');

    // Módulo RRHH
    Route::get('/rrhh/personal', fn () => view('rrhh.gestion-personal'))
        ->middleware('can:rrhh.personal.ver')
        ->name('rrhh.personal.index');

    Route::get('/rrhh/jornales', fn () => view('rrhh.gestion-jornales'))
        ->middleware('can:rrhh.jornales.ver')
        ->name('rrhh.jornales.index');

    // Módulo Ventas
    Route::get('/ventas/granos', fn () => view('ventas.gestion-ventas-granos'))
        ->middleware('can:ventas.granos.ver')
        ->name('ventas.granos.index');

    Route::get('/ventas/hacienda', fn () => view('ventas.gestion-ventas-hacienda'))
        ->middleware('can:ventas.hacienda.ver')
        ->name('ventas.hacienda.index');

    // Módulo Compras
    Route::get('/compras/proveedores', fn () => view('compras.gestion-proveedores'))
        ->middleware('can:compras.proveedores.gestionar')
        ->name('compras.proveedores.index');

    Route::get('/compras', fn () => view('compras.gestion-compras'))
        ->middleware('can:compras.ver')
        ->name('compras.index');

    Route::get('/compras/importar-arca', fn () => view('compras.importar-compras-arca'))
        ->middleware('can:compras.crear')
        ->name('compras.importar-arca');

    // Módulo Sistema / Configuración
    Route::get('/sistema/perfil', fn () => view('sistema.perfil'))
        ->name('sistema.perfil');

    Route::get('/sistema/empresa', fn () => view('sistema.empresa'))
        ->middleware('can:admin.roles.gestionar')
        ->name('sistema.empresa');

    Route::get('/sistema/roles', fn () => view('sistema.roles'))
        ->middleware('can:admin.roles.gestionar')
        ->name('sistema.roles');

    Route::get('/sistema/auditoria', fn () => view('sistema.auditoria'))
        ->middleware('can:auditoria.ver')
        ->name('sistema.auditoria');

    // Módulo Reportes
    Route::get('/reportes/productivo', fn () => view('reportes.reporte-productivo'))
        ->middleware('can:reportes.productivos.ver')
        ->name('reportes.productivo');

    Route::get('/reportes/economico', fn () => view('reportes.reporte-economico'))
        ->middleware('can:reportes.economicos.ver')
        ->name('reportes.economico');

    Route::get('/reportes/fiscal', fn () => view('reportes.reporte-fiscal'))
        ->middleware('can:reportes.fiscales.ver')
        ->name('reportes.fiscal');

    // Feedlot
    Route::get('/feedlot/corrales', fn () => view('feedlot.gestion-corrales'))
        ->middleware('can:feedlot.corrales.ver')
        ->name('feedlot.corrales.index');

    Route::get('/feedlot/tropas', fn () => view('feedlot.gestion-tropas'))
        ->middleware('can:feedlot.tropas.ver')
        ->name('feedlot.tropas.index');

    Route::get('/feedlot/consumos', fn () => view('feedlot.gestion-consumos'))
        ->middleware('can:feedlot.consumos.registrar')
        ->name('feedlot.consumos.index');

    // Períodos Fiscales y Reintegros IVA
    Route::get('/finanzas/periodos-fiscales', fn () => view('finanzas.gestion-periodos-fiscales'))
        ->middleware('can:finanzas.periodos.gestionar')
        ->name('finanzas.periodos.index');

    Route::get('/finanzas/reintegros-iva', fn () => view('finanzas.gestion-reintegros-iva'))
        ->middleware('can:finanzas.reintegros.gestionar')
        ->name('finanzas.reintegros.index');

});

require __DIR__.'/auth.php';
