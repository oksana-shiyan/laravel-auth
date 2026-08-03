<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Personal Access Token Lifetime
    |--------------------------------------------------------------------------
    |
    | Number of days before newly issued Sanctum tokens expire.
    |
    */

    'token_expiration_days' => (int) env('SANCTUM_TOKEN_EXPIRATION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Login Token Name
    |--------------------------------------------------------------------------
    */

    'login_token_name' => env('API_LOGIN_TOKEN_NAME', 'api-login'),

];
