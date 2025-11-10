<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aqui você pode configurar suas configurações para Cross-Origin Resource
    | Sharing ou "CORS". Isso determina quais operações cross-origin podem ser
    | executadas em navegadores web. Você é livre para ajustar essas configurações
    | conforme necessário.
    |
    | Para aprender mais: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Lista explícita de métodos HTTP permitidos para prevenir CSRF.
    | Evitamos usar wildcard (*) quando credentials estão habilitadas.
    |
    */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5174'),
    ],

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Lista explícita de headers permitidos. Incluímos os headers comuns
    | para APIs REST modernas com autenticação JWT.
    |
    */
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-Token',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
