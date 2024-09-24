<?php

/*
|--------------------------------------------------------------------------
| Otp API V1 Routes
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/v1/otp'], function ($app) {
    /*
    |--------------------------------------------------------------------------
    | SMS OTP ROUTES
    |--------------------------------------------------------------------------
    */
    $app->group(['middleware' => ['otpAuth']], function ($app) {
        $app->group(['middleware' => ['CheckOtpLimit']], function ($app) {
            $app->post('send', 'OtpService@sendOtp');
            $app->post('resend', 'OtpService@reSendOTP');
        });
        $app->post('verify', 'OtpService@verifyOtp');
    });
});
