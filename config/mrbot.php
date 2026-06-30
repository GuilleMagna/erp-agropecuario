<?php

return [
    'url'     => env('MRBOT_URL', 'https://api-bots.mrbot.com.ar'),
    'api_key' => env('MRBOT_API_KEY', ''),
    'email'   => env('MRBOT_EMAIL', ''),

    // Credenciales AFIP/ARCA del campo
    'cuit_login'          => env('MRBOT_CUIT_LOGIN', ''),
    'clave_fiscal'        => env('MRBOT_CLAVE_FISCAL', ''),
    'cuit_representado'   => env('MRBOT_CUIT_REPRESENTADO', ''),
    'nombre_representado' => env('MRBOT_NOMBRE_REPRESENTADO', ''),

    'timeout' => (int) env('MRBOT_TIMEOUT', 120),
];
