<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
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
    }
}
