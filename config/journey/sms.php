<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS env variables here
    |--------------------------------------------------------------------------
    |
    */
    'env' => env('APP_ENV'),
    'username' => env('SMS_USERNAME'),
    'password' => env('SMS_PASSWORD'),
    'auth_key' => env('SMS_AUTH_KEY'),
    'otp_expiry' => env('SMS_OTP_EXPIRY'),
    'request_otp_url' => env('REQUEST_OTP_URL'),
    'cc_username' => env('CC_SMS_USERNAME'),
    'cc_password' => env('CC_SMS_PASSWORD'),
];
