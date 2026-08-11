<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SincronizarComprobantesArca extends Command
{
    protected $signature = 'arca:sincronizar';

    protected $description = 'Informa cómo ejecutar la sincronización externa de comprobantes ARCA';

    public function handle(): int
    {
        $this->warn('La sincronización ARCA ya no se ejecuta dentro de cPanel.');
        $this->line('Ejecutá el workflow “Sincronizar comprobantes ARCA” desde GitHub Actions.');

        return self::SUCCESS;
    }
}
