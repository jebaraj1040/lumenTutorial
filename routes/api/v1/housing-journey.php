<?php
/*
|--------------------------------------------------------------------------
| Housing Journey API V1 Routes
|--------------------------------------------------------------------------
*/
$router->group(['prefix' => 'api/v1'], function () use ($router) {

    $router->get('/', function () {
        return response()->json([
            'message' => 'Housing Journey Api V1',
            'code' => 200,
            'data' => [
                'version' => 'v1'
            ]
        ]);
    });
    /*
    |--------------------------------------------------------------------------
    | Auth Token Create Routes
    |--------------------------------------------------------------------------
    */
    $router->get('journey-auth', 'AuthService@getJourneyAuthToken');
    $router->get('core-auth', 'AuthService@createCoreAuthToken');
    $router->get('cc-auth', 'AuthService@createCCAuthToken');
    /*
    |--------------------------------------------------------------------------
    | Language Switch Routes
    |--------------------------------------------------------------------------
    */
    $router->group(['middleware' => ['journeyAuthToken', 'localization']], function ($router) {
        $router->get('language-switch', 'LanguageService@setLanguage');
    });
    /*
    |--------------------------------------------------------------------------
    | Cookie Token Authentication
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'housing-journey', 'middleware' => ['journeyAuthToken', 'localization']], function () use ($router) {
        /*
        |--------------------------------------------------------------------------
        | Common Housing Journey Routes
        |--------------------------------------------------------------------------
        | Personal Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'personal-detail'], function () use ($router) {
            $router->post('save', 'HousingJourney\PersonalDetailService@save');
            $router->post('view', 'HousingJourney\PersonalDetailService@view');
            $router->post('fetch-pan-detail', 'HousingJourney\PersonalDetailService@fetchPanDetail');
            $router->post('get-personal-details', 'HousingJourney\PersonalDetailService@getPersonalDetails');
            $router->post('address-fetch-karza', 'HousingJourney\PersonalDetailService@fetchAddress');
            $router->post('karza', 'HousingJourney\PersonalDetailService@karzaApicall');
        });
        /*
        |--------------------------------------------------------------------------
        | Lead Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'lead'], function () use ($router) {
            $router->post('save', 'HousingJourney\LeadService@save');
            $router->post('view', 'HousingJourney\LeadService@view');
            $router->post('co-applicant-save', 'HousingJourney\LeadService@coApplicantSave');
            $router->get('view-sanction', 'HousingJourney\LeadService@getSLData');
            $router->post('co-applicant-karza', 'HousingJourney\LeadService@karzaApicall');
            $router->post('view-co-applicant-eligibility', 'HousingJourney\LeadService@viewCoApplicantEligibility');
            $router->post('view-co-applicant-personal', 'HousingJourney\LeadService@viewCoApplicantPersonalData');
            $router->post('view-co-applicant-employment', 'HousingJourney\LeadService@viewCoApplicantEmploymentData');
        });

        /*
        |--------------------------------------------------------------------------
        | Data Layer Update Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'data-layer'], function () use ($router) {
            $router->post('update', 'HousingJourney\ApplicationService@updateTraversedStatus');
        });
        /*
        |--------------------------------------------------------------------------
        | Upload Document Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'upload-document'], function () use ($router) {
            $router->post('save', 'HousingJourney\IncreaseEligibilityDocumentService@save');
            $router->post('update', 'HousingJourney\IncreaseEligibilityDocumentService@update');
            $router->post('view', 'HousingJourney\IncreaseEligibilityDocumentService@view');
            $router->post('document-list', 'HousingJourney\IncreaseEligibilityDocumentService@documentList');
            $router->post('files-remove', 'HousingJourney\IncreaseEligibilityDocumentService@filesRemove');
            $router->post('file-remove', 'HousingJourney\IncreaseEligibilityDocumentService@fileRemove');
            $router->post('final-api-call', 'HousingJourney\MasterDocumentService@finalSumbitApiCall');
        });
        /*
        |--------------------------------------------------------------------------
        | Employment Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'employment-detail'], function () use ($router) {
            $router->post('save', 'HousingJourney\EmploymentDetailService@save');
            $router->post('view', 'HousingJourney\EmploymentDetailService@view');
            $router->post('fetch-industry-type', 'HousingJourney\EmploymentDetailService@fetchIndustryType');
        });
        /*
        |--------------------------------------------------------------------------
        | Address Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'address-detail'], function () use ($router) {
            $router->post('save', 'HousingJourney\AddressService@save');
            $router->post('view', 'HousingJourney\AddressService@edit');
            $router->post('fetch-address', 'HousingJourney\AddressService@fetchAddress');
        });
        /*
        |--------------------------------------------------------------------------
        | Property Loan Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'property-loan-detail'], function () use ($router) {
            $router->post('save', 'HousingJourney\PropertyLoanDetailService@save');
            $router->post('view', 'HousingJourney\PropertyLoanDetailService@view');
            $router->post('offer-save', 'HousingJourney\PropertyLoanDetailService@saveOffer');
            $router->post('bre-two-data', 'HousingJourney\EligibilityService@getBreTwoData');
            $router->post('fetch-property', 'HousingJourney\PropertyLoanDetailService@fetchPropertyData');
            $router->post('search-project', 'HousingJourney\PropertyLoanDetailService@searchProject');
            $router->post('search-loan-provider', 'HousingJourney\PropertyLoanDetailService@searchLoanProvider');
            $router->post('exist-property-save', 'HousingJourney\PropertyLoanDetailService@existPropertySave');
        });
        /*
        |--------------------------------------------------------------------------
        | Eligibility Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'eligibility-detail'], function () use ($router) {
            $router->post('save', 'HousingJourney\EligibilityService@save');
            $router->post('view', 'HousingJourney\EligibilityService@view');
            $router->post('bre-one-data', 'HousingJourney\EligibilityService@getBreOneData');
        });
        /*
        |--------------------------------------------------------------------------
        | Final Submit Route
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'final-submit'], function () use ($router) {
            $router->post('save', 'HousingJourney\CoreService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Pincode Route
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'pincode'], function () use ($router) {
            $router->post('search', 'HousingJourney\MasterPincodeService@searchPincode');
        });
        /*
        |--------------------------------------------------------------------------
        | State Route
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'state'], function () use ($router) {
            $router->get('list', 'HousingJourney\MasterStateService@list');
        });
        /*
        |--------------------------------------------------------------------------
        | Stage Check
        |--------------------------------------------------------------------------
        */
        $router->post('stepper-stage', 'HousingJourney\ImpressionService@stepperStage');
        /*
        |--------------------------------------------------------------------------
        | Master Dropdown Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'master-data'], function () use ($router) {
            $router->get('relationship-master', 'HousingJourney\LeadService@getRelationshipMasterData');
            $router->get('property', 'HousingJourney\MasterDropdownService@getPropertyPageDropdown');
            $router->get('employment', 'HousingJourney\MasterDropdownService@getEmploymentMasterData');
            $router->post('company-master', 'HousingJourney\MasterDropdownService@getCompanyMasterData');
            $router->post('document', 'HousingJourney\MasterDocumentService@getDocumentDropdown');
        });
        /*
        |--------------------------------------------------------------------------
        | Field Update Log
        |--------------------------------------------------------------------------
        */
        $router->post('field-tracking', 'HousingJourney\FieldTrackingService@log');
        /*
        |--------------------------------------------------------------------------
        | Resume Journey
        |--------------------------------------------------------------------------
        */
        $router->post('resume-journey-list', 'HousingJourney\PersonalDetailService@resumeJourneyCheck');
        $router->post('create-application', 'HousingJourney\PersonalDetailService@createApplication');
        $router->post('resume-application', 'HousingJourney\PersonalDetailService@resumeApplication');
        /*
        |--------------------------------------------------------------------------
        | Remove token Route
        |--------------------------------------------------------------------------
        */
        $router->get('remove-token', 'AuthService@removeRedisToken');

        /*
        |--------------------------------------------------------------------------
        | Master Search Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'master-search'], function () use ($router) {
            $router->get('pincode/{pincode}', 'DebugService@searchPinCode');
            $router->get('industry-type/{industry_type}', 'DebugService@searchIndustry');
            $router->get('company/{company_name}', 'DebugService@searchCompany');
            $router->get('project/{project_name}', 'DebugService@searchProject');
            $router->get('ifsc/{ifsc}', 'DebugService@searchIfsc');
            $router->get('state/{state}', 'DebugService@searchState');
            $router->get('employment-type/{employment_type}', 'DebugService@searchEmploymentType');
            $router->post('bre-data', 'DebugService@getBreData');
            $router->post('customer-data', 'DebugService@getCustomerData');
            $router->post('karza-pan', 'DebugService@getKarzaData');
            $router->post('cibil-data', 'DebugService@fetchCibilData');
            $router->post('final-submit', 'DebugService@getFinalSumbitData');
            $router->post('partner-fetch', 'DebugService@fetchPartnerData');
            $router->post('product-mapping', 'DebugService@propertyDetailsMapping');
            $router->post('application-update', 'DebugService@applicationDateUpdate');
            $router->post('field-track-update', 'DebugService@ccPushTagUpdate');
        });
        /*
            |--------------------------------------------------------------------------
            | Final Sumbit API call in Sanction Page
            |--------------------------------------------------------------------------
        */
        $router->post('final-api-call', 'HousingJourney\LeadService@finalSumbitApiCall');
    });

    /*
        |--------------------------------------------------------------------------
        | Unsubscribe email user
        |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'housing-journey'], function () use ($router) {
        $router->post('unsubscribe', 'HousingJourney\CoreService@unsubscribeUsers');
    });

    /*
    |--------------------------------------------------------------------------
    | CC API V1 Routes
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'cc', 'middleware' => ['ccAuthToken']], function () use ($router) {
        /*
        |--------------------------------------------------------------------------
        | CC Receive Routes
        |--------------------------------------------------------------------------
        */
        $router->post('application-disposition-update', 'HousingJourney\CCPushService@receiveRecord');
    });

    /*
    |--------------------------------------------------------------------------
    | CC Quote Info API V1 Routes
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'cc-info', 'middleware' => ['ccInfoAuthToken']], function () use ($router) {
        /*
        |--------------------------------------------------------------------------
        | Get CC Quote Info Routes
        |--------------------------------------------------------------------------
        */
        $router->post('cc-quote-info', 'HousingJourney\CCPushService@getCCQuoteIDData');
        $router->post('send-sms', 'OtpService@sendOtpCcLog');
    });
});
