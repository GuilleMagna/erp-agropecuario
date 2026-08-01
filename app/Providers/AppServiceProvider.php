<?php

namespace App\Providers;

use App\Models\Empresa;
use App\Traits\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Durante el desarrollo, que Eloquent avise (en vez de fallar en
        // silencio) si se intenta acceder a una relación no cargada con
        // lazy loading en un contexto donde eso degradaría el rendimiento
        // (relevante dado el volumen de datos esperado, Documento 03,
        // sección 16 — particionamiento e índices).
        Model::preventLazyLoading(false);

        // Forzar HTTPS en producción (Documento 07, sección 3.1 — toda
        // comunicación cifrada, sin excepciones).
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // Pasa empresa activa y lista de empresas al layout principal
        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                return;
            }

            $empresaActivaId = PerteneceAEmpresa::resolverEmpresaActiva();
            $empresaActiva = $empresaActivaId ? Empresa::find($empresaActivaId) : null;

            $todasEmpresas = auth()->user()->can('sistema.empresas.cambiar')
                ? Empresa::where('activa', true)->orderBy('razon_social')->get()
                : collect();

            $view->with(compact('empresaActiva', 'todasEmpresas'));
        });
    }
}
