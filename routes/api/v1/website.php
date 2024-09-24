<?php

/*
|--------------------------------------------------------------------------
| Website API V1 Routes
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/v1/website'], function ($app) {

    /*
    |--------------------------------------------------------------------------
    | Web auth Route
    |--------------------------------------------------------------------------
    */
    $app->get('web-auth', 'AuthService@createWebsiteSessionToken');

    /*
    |--------------------------------------------------------------------------
    | Pincode Route
    |--------------------------------------------------------------------------
    */
    $app->group(['prefix' => 'pincode', 'middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->post('search', 'HousingJourney\MasterPincodeService@searchPincode');
    });
    /*
    |--------------------------------------------------------------------------
    | Ifsc Route
    |--------------------------------------------------------------------------
    */
    $app->group(['prefix' => 'ifsc-master', 'middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->post('save', 'HousingJourney\MasterIfscService@save');
        $app->post('import', 'HousingJourney\MasterIfscService@import');
        $app->get('list', 'HousingJourney\MasterIfscService@getMasterIfsc');
    });
    /*
    |--------------------------------------------------------------------------
    | State Route
    |--------------------------------------------------------------------------
    */
    $app->group(['prefix' => 'state', 'middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->get('list', 'HousingJourney\MasterStateService@list');
    });



    /*
    |--------------------------------------------------------------------------
    | Field Update Log
    |--------------------------------------------------------------------------
    */
    $app->group(['prefix' => 'field-tracking', 'middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->post('', 'HousingJourney\FieldTrackingService@log');
    });
    /*
    |--------------------------------------------------------------------------
    | Partner Fetch API
    |--------------------------------------------------------------------------
    */
    $app->group(['middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->post('partner-fetch', 'HousingJourney\CoreService@partnerFetch');
        $app->post('pan-fetch', 'DebugService@getKarzaData');
        $app->post('cibil-fetch', 'HousingJourney\CoreService@cibilFetch');
        $app->post('customer-query', 'CustomerQueryService@saveCustomerQuery');
    });
    /*
    |--------------------------------------------------------------------------
    | Auction bid form
    |--------------------------------------------------------------------------
    */
    $app->group(['prefix' => 'auction-bid-form', 'middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->post('save', 'HousingJourney\AuctionBidFormDetailService@save');
    });
    /*
        |--------------------------------------------------------------------------
        | Fetch Error Log Route
        |--------------------------------------------------------------------------
        */
    /*
    |--------------------------------------------------------------------------
    | User Session Activity
    |--------------------------------------------------------------------------
    */
    $app->group(['middleware' => ['websiteAuthToken']], function () use ($app) {
        $app->post('display-log', 'DebugService@readErrorLog');
        $app->post('user-session-activity', 'UserSessionActivityService@save');
    });

    $app->group(['middleware' => ['serviceAuthToken']], function () use ($app) {
        $app->post('save-bvn-calls', 'HousingJourney\CoreService@saveBvncalls');
        $app->post('save-google-chat', 'HousingJourney\CoreService@saveGoogleChat');
        $app->post('save-microsite', 'HousingJourney\CoreService@saveMicrosite');
    });
});
