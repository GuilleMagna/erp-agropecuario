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

});

require __DIR__.'/auth.php';