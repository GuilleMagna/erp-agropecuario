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
    Route::get('/admin/usuarios', function () {
        return view('livewire.admin.gestion-usuarios');
    })->name('admin.usuarios.index');

});

require __DIR__.'/auth.php';