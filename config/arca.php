<?php

return [
    // Destinatario del informe por email cuando el cron de sincronización
    // con ARCA (arca:sincronizar) descarga comprobantes nuevos.
    'reporte_email' => env('ARCA_REPORTE_EMAIL'),
];
