<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Boot API error response array
    |--------------------------------------------------------------------------
    |
    */
    'boot' => [
        'code' => 200,
        'message' => 'WEBSITE JOURNEY API-V1',
        'status' => true
    ],
    /*
    |--------------------------------------------------------------------------
    | Internal server error response array
    |--------------------------------------------------------------------------
    |
    */
    'error' => [
        'code' => 500,
        'message' => 'Internal Server Error.',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | Success error response array
    |--------------------------------------------------------------------------
    |
    */

    'success' => [
        'code' => 200,
        'message' => 'Successful request.',
        'status' => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Failure error response array
    |--------------------------------------------------------------------------
    |
    */
    'failure' => [
        'code' => 201,
        'message' => 'Failed to retrieve data.',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | insert error response array
    |--------------------------------------------------------------------------
    |
    */
    'oops' => [
        'code' => 201,
        'message' => 'Somthing went wrong.',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | insert error response array
    |--------------------------------------------------------------------------
    |
    */
    'invalid-mobile' => [
        'code' => 203,
        'message' => 'invalid mobile number.',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | File type mismatch error
    |--------------------------------------------------------------------------
    |
    */
    'unsupported-media-type' => [
        'code' => 415,
        'message' => 'File type must be png,jpg,jpeg and pdf',
        'status' => false
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Timeout Error response array
    |--------------------------------------------------------------------------
    |
    */
    'time-out' => [
        'code' => 504,
        'message' => 'Gateway Timeout Error',
        'status' => false
    ],

    /*
    |--------------------------------------------------------------------------
    | update error response array
    |--------------------------------------------------------------------------
    |
    */
    'update' => [
        'code' => 200,
        'message' => 'Data updated successfully.',
        'status' => true
    ],
    /*
    |--------------------------------------------------------------------------
    | Connection timeout error response array
    |--------------------------------------------------------------------------
    |
    */
    'timeout' => [
        'code' => 208,
        'message' => 'Connection timeout',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | bad request error response array
    |--------------------------------------------------------------------------
    |
    */
    'bad-request' => [
        'code' => 400,
        'message' => 'The request is missing a required parameter.',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | unauthorized request error response array
    |--------------------------------------------------------------------------
    |
    */
    'unauthorized' => [
        'code' => 401,
        'message' => 'Unauthorized',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | Auth Success error response array
    |--------------------------------------------------------------------------
    |
    */
    'auth' => [
        'failure' => 'User Authenticated failure.',
        'success' => 'User Authenticated Successful.',
        'error' => 'Could not create token.'
    ],
    /*
    |--------------------------------------------------------------------------
    | otp-send error response array
    |--------------------------------------------------------------------------
    |
    */
    'otp-send' => [
        'failure' => 'OTP send failure.',
        'success' => 'OTP send successfully.'
    ],
    /*
    |--------------------------------------------------------------------------
    | otp-resend error response array
    |--------------------------------------------------------------------------
    |
    */
    'otp-resend' => [
        'failure' => 'OTP resent failure.',
        'success' => 'OTP resent successfully.'
    ],
    /*
    |--------------------------------------------------------------------------
    | otp-verify error response array
    |--------------------------------------------------------------------------
    |
    */
    'otp-verify' => [
        'failure' => 'OTP verify failure.',
        'success' => 'OTP verify successfully.'
    ],
    /*
    |--------------------------------------------------------------------------
    | otp-expired error response array
    |--------------------------------------------------------------------------
    |
    */
    'otp-expired' => [
        'code' => 203,
        'message' => 'OTP Expired',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | plan-data-add error response array
    |--------------------------------------------------------------------------
    |
    */
    'plan-data-add' => [
        'failure' => 'Failed to add data',
        'success' => 'Data added Successfully'
    ],
    /*
    |--------------------------------------------------------------------------
    |unauth csrf token
    |--------------------------------------------------------------------------
    |
    */
    'forbidden' => [
        'code' => 403,
        'message' => "Access Denied - You don't have permission to access",
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    |auction-data-add error response array
    |--------------------------------------------------------------------------
    |
    */
    'auction-data-add' => [
        'message' => 'Data Added Successfully',
        'failure' => 'Failed To Retrive Data'
    ],
    /*
    |--------------------------------------------------------------------------
    | No Data found
    |--------------------------------------------------------------------------
    |
    */
    'no-data-found' => [
        'code' => 404,
        'message' => 'No data found.',
        'status' => false
    ],
    /*
    |--------------------------------------------------------------------------
    | Core Connection and Error
    |--------------------------------------------------------------------------
    |
    */
    'connection-error' => [
        'code' => 522,
        'message' => 'Connection error',
        'status' => false
    ],
];
