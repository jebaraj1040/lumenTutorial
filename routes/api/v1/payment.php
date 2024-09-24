<?php
/*
|--------------------------------------------------------------------------
| Housing Journey Payment API Routes
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/v1/payment', 'middleware' => ['journeyAuthToken']], function ($app) {
    /*
    |--------------------------------------------------------------------------
    | Airpay Payment Gateway Init ROUTES
    |--------------------------------------------------------------------------
    */
    $app->post('init', 'Payment\PaymentService@paymentInititate');
});

$router->group(['prefix' => 'api/v1/payment', 'middleware' => ['paymentAuthToken']], function ($app) {
    /*
    |--------------------------------------------------------------------------
    | Airpay Payment Gateway Callback ROUTES
    |--------------------------------------------------------------------------
    */
    $app->post('airpg-callback', 'Payment\PaymentService@paymentRedirect');
});
