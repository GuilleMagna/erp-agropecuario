<?php

namespace Tests\Unit;

use Tests\TestCase;

class LivewireAssetConfigurationTest extends TestCase
{
    public function test_livewire_assets_are_not_auto_injected_when_layout_renders_them_explicitly(): void
    {
        $this->assertFalse(config('livewire.inject_assets'));

        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertSame(1, substr_count($layout, '@livewireStyles'));
        $this->assertSame(1, substr_count($layout, '@livewireScripts'));
    }

    public function test_grain_sales_modal_remains_managed_by_livewire(): void
    {
        $view = file_get_contents(resource_path(
            'views/livewire/ventas/gestion-ventas-granos.blade.php'
        ));

        $this->assertStringContainsString('wire:key="modal-venta-granos"', $view);
        $this->assertStringNotContainsString(
            'class="modal fade show d-block" wire:ignore.self',
            $view
        );
    }

    /**
     * Causa raíz real del popup de ventas "roto": los archivos tenían BOM UTF-8
     * al inicio. Livewire calcula el elemento raíz del componente parseando el
     * primer carácter del HTML compilado; con el BOM antes de "<div>", ese
     * parseo falla y Livewire termina inyectando wire:id en un div hijo en vez
     * de en la raíz real. El componente se renderiza igual en el primer
     * request, pero el registro JS de Livewire.all() pierde el componente tras
     * el primer update AJAX, y los wire:click dejan de disparar sin ningún
     * error visible en consola.
     */
    public function test_livewire_view_files_have_no_utf8_bom(): void
    {
        $conBom = [];
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views/livewire'), \FilesystemIterator::SKIP_DOTS)
        ) as $file) {
            if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $bytes = file_get_contents($file->getPathname());
            if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
                $conBom[] = $file->getPathname();
            }
        }

        $this->assertSame([], $conBom, 'Archivos Blade de Livewire con BOM UTF-8 (rompe la detección del elemento raíz): '.implode(', ', $conBom));
    }
}
