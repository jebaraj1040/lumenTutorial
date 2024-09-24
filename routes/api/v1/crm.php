<?php
/*
|--------------------------------------------------------------------------
| CRM API V1 Routes
|--------------------------------------------------------------------------
*/
$router->group(['prefix' => 'api/v1/crm'], function ($router) {
    /*
    |--------------------------------------------------------------------------
    | Login and Logout
    |--------------------------------------------------------------------------
    */
    $router->post('login-logout', 'Crm\AuthService@authenticate');
    $router->post('forgot-credential', 'Crm\AuthService@forgotPassword');
    $router->post('reset-credential', 'Crm\AuthService@resetPassword');
    $router->post('generate-captcha', 'Crm\CaptchaService@generateCaptcha');
    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'user', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'Crm\UserService@saveUser');
        $router->get('get-data', 'Crm\UserService@getData');
        $router->get('list', 'Crm\UserService@getUser');
        $router->get('edit', 'Crm\UserService@getUserData');
        $router->post('delete', 'Crm\UserService@deleteUser');
        $router->post('update', 'Crm\UserService@updateUser');
        $router->post('update-credential', 'Crm\UserService@updatePassword');
        $router->post('update-profile-image', 'Crm\UserService@updateProfileImage');
        $router->post('update-password', 'Crm\UserService@resetPassword');
        $router->post('export', 'Crm\UserService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Role Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'role', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'Crm\RoleService@saveRole');
        $router->get('list', 'Crm\RoleService@getRole');
        $router->get('edit', 'Crm\RoleService@editRole');
        $router->post('delete', 'Crm\RoleService@deleteRole');
        $router->post('export', 'Crm\RoleService@export');
        /*
        |--------------------------------------------------------------------------
        | Role Menu Mapping
        |--------------------------------------------------------------------------
        */
        $router->post('menu-list-mapping', 'Crm\RoleService@listMenuMapping');
        $router->post('menu-mapping', 'Crm\RoleService@menuMapping');
    });
    /*
    |--------------------------------------------------------------------------
    | Menu Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'menu', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'Crm\MenuService@saveMenu');
        $router->get('list', 'Crm\MenuService@getMenu');
        $router->get('edit', 'Crm\MenuService@editMenu');
        $router->post('delete', 'Crm\MenuService@deleteMenu');
        $router->post('check-access', 'Crm\MenuService@checkMenuAccess');
        $router->post('export', 'Crm\MenuService@export');
        $router->get('parent-list', 'Crm\MenuService@parentList');
        $router->post('orderstatus', 'Crm\MenuService@orderstatus');
        $router->post('orderupdate', 'Crm\MenuService@orderupdate');
    });
    /*
    |--------------------------------------------------------------------------
    | Applicant Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-leads', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\LeadService@list');
        $router->post('detail', 'HousingJourney\LeadService@detail');
        $router->post('export', 'HousingJourney\LeadService@exportLead');
    });
    /*
    |--------------------------------------------------------------------------
    | Address Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-address', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\AddressService@list');
        $router->post('export', 'HousingJourney\AddressService@exportAddress');
    });
    /*
    |--------------------------------------------------------------------------
    | Eligibility Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-eligibility', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\EligibilityService@list');
        $router->post('export', 'HousingJourney\EligibilityService@exportEligibility');
    });
    /*
    |--------------------------------------------------------------------------
    | Employment Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-employment', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\EmploymentDetailService@list');
        $router->post('export', 'HousingJourney\EmploymentDetailService@exportEmploymentDetail');
    });
    /*
    |--------------------------------------------------------------------------
    | Personal Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-personal-details', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\PersonalDetailService@list');
        $router->post('export', 'HousingJourney\PersonalDetailService@exportPersonalDetails');
    });
    /*
    |--------------------------------------------------------------------------
    | Document Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-document-details', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\IncreaseEligibilityDocumentService@list');
        $router->post('export', 'HousingJourney\IncreaseEligibilityDocumentService@exportDocumentDetails');
    });
    /*
    |--------------------------------------------------------------------------
    | Property Loan Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-property-loan-details', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\PropertyLoanDetailService@list');
        $router->post('export', 'HousingJourney\PropertyLoanDetailService@exportPropertyLoanDetails');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey Impressions
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-impressions', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\ImpressionService@list');
        $router->post('export', 'HousingJourney\ImpressionService@exportImpression');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey Applictions
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-applications', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\ApplicationService@list');
        $router->post('export', 'HousingJourney\ApplicationService@exportApplication');
        $router->post('application-details', 'HousingJourney\ApplicationService@getApplicationDataByQuoteId');
        $router->post('quoteid-details', 'HousingJourney\ApplicationService@getQuoteIdDetails');
    });
    /*
    |--------------------------------------------------------------------------
    | BVN Calls Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'bvn-calls', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\BvnCallService@list');
        $router->post('export', 'HousingJourney\BvnCallService@exportBvnCallDetails');
    });
    /*
    |--------------------------------------------------------------------------
    | Google Chat Details
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'google-chat', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\GoogleChatService@list');
        $router->post('export', 'HousingJourney\GoogleChatService@exportGoogleChatDetails');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey masterproduct
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'masterproduct', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'HousingJourney\MasterProductService@saveProduct');
        $router->get('list', 'HousingJourney\MasterProductService@getMasterproducts');
        $router->get('edit', 'HousingJourney\MasterProductService@editProduct');
        $router->post('delete', 'Crm\MasterProductService@deleteProduct');
        $router->post('check-access', 'Crm\MasterProductService@checkProductAccess');
        $router->get('filter', 'HousingJourney\MasterProductService@filter');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey masterpincode
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'masterpincode', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'HousingJourney\MasterPincodeService@save');
        $router->get('list', 'HousingJourney\MasterPincodeService@getMasterpincodes');
        $router->get('edit', 'HousingJourney\MasterPincodeService@editpincode');
        $router->post('delete', 'Crm\MasterPincodeService@deleteProduct');
        $router->post('check-access', 'Crm\MasterPincodeService@checkProductAccess');
        $router->get('filter', 'HousingJourney\MasterPincodeService@filter');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey masterproject
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'masterproject', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'HousingJourney\MasterProjectService@save');
        $router->get('list', 'HousingJourney\MasterProjectService@getMasterprojects');
        $router->get('edit', 'HousingJourney\MasterProjectService@editProduct');
        $router->post('delete', 'Crm\MasterProjectService@deleteProduct');
        $router->post('check-access', 'Crm\MasterProjectService@checkProductAccess');
        $router->get('filter', 'HousingJourney\MasterProjectService@filter');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey mastercompany
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'mastercompany', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'HousingJourney\MasterCompanyService@save');
        $router->get('list', 'HousingJourney\MasterCompanyService@getMastercompanys');
        $router->get('edit', 'HousingJourney\MasterCompanyService@editCompany');
        $router->post('delete', 'Crm\MasterCompanyService@deleteProduct');
        $router->post('check-access', 'Crm\MasterCompanyService@checkProductAccess');
        $router->get('filter', 'HousingJourney\MasterCompanyService@filter');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey masterindustry
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'masterindustry', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('save', 'HousingJourney\MasterIndustryTypeService@save');
        $router->get('list', 'HousingJourney\MasterIndustryTypeService@getMasterindustries');
        $router->get('edit', 'HousingJourney\MasterIndustryTypeService@editIndustry');
        $router->post('delete', 'Crm\MasterIndustryTypeService@deleteIndustry');
        $router->post('check-access', 'Crm\MasterIndustryTypeService@checkIndustryAccess');
        $router->get('filter', 'HousingJourney\MasterIndustryTypeService@filter');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey auctionbid
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'hj-auctionbid', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\AuctionBidFormDetailService@list');
        $router->post('export', 'HousingJourney\AuctionBidFormDetailService@export');
    });
    /*
    |--------------------------------------------------------------------------
    | API Log Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'Crm\LogService@getFilterData');
        $router->post('list', 'Crm\LogService@getLogList');
        $router->post('export', 'Crm\LogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | PaymentTransaction Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'paymentTransaction', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\PaymentTransactionService@getPaymentTransactionList');
        $router->post('export', 'HousingJourney\PaymentTransactionService@export');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey panlog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'panlog', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\PanLogService@getFilterData');
        $router->post('list', 'HousingJourney\PanLogService@getLogList');
        $router->post('export', 'HousingJourney\PanLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | PaymentLogsDetails Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'payment-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'Payment\PaymentService@getPaymentLogList');
        $router->post('export', 'Payment\PaymentService@exportPaymentLog');
    });
    /*
    |--------------------------------------------------------------------------
    | LumenLogsDetails Management
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'lumen-log'], function () use ($router) {
        $router->post('logList', 'LumenLogService\LumenLogService@readLumenLog');
        $router->post('logExport', 'LumenLogService\LumenLogService@exportLumenLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey ccPushLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'cc-push-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\CCPushService@getFilterData');
        $router->post('list', 'HousingJourney\CCPushService@getCCPushLog');
        $router->post('export', 'HousingJourney\CCPushService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey smsLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'sms-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'HousingJourney\SmsLogService@getLog');
        $router->post('filter-data', 'HousingJourney\SmsLogService@getFilterData');
        $router->post('export', 'HousingJourney\SmsLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | userSessionActivityLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'user-session-activity-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'UserSessionActivityService@userSessionList');
        $router->post('filter-data', 'UserSessionActivityService@getFilterData');
        $router->post('export', 'UserSessionActivityService@userSessionExportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | userPortfolioLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'user-portfolio-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'UserSessionActivityService@userPortfolioList');
        $router->post('filter-data', 'UserSessionActivityService@getFilterData');
        $router->post('export', 'UserSessionActivityService@userPortFolioExportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey BreLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'bre-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\BreLogService@getFilterData');
        $router->post('list', 'HousingJourney\BreLogService@getLogList');
        $router->post('export', 'HousingJourney\BreLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey OtpLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'otp-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\OtpLogService@getFilterData');
        $router->post('list', 'HousingJourney\OtpLogService@getLogList');
        $router->post('export', 'HousingJourney\OtpLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey KrazaLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'karza-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\KarzaLogService@getFilterData');
        $router->post('list', 'HousingJourney\KarzaLogService@getLogList');
        $router->post('export', 'HousingJourney\KarzaLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey CibilLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'cibil-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\CibilLogService@getFilterData');
        $router->post('list', 'HousingJourney\CibilLogService@getLogList');
        $router->post('export', 'HousingJourney\CibilLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey Microsite
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'microsite', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\MicrositeService@getMicrositeList');
        $router->post('export', 'HousingJourney\MicrositeService@exportList');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey TalismaCreateContactLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'talisma-create-contact-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\TalismaCreateContactLogService@getFilterData');
        $router->post('list', 'HousingJourney\TalismaCreateContactLogService@getLogList');
        $router->post('export', 'HousingJourney\TalismaCreateContactLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey TalismaResolveContactLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'talisma-resolve-contact-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\TalismaResolveContactLogService@getFilterData');
        $router->post('list', 'HousingJourney\TalismaResolveContactLogService@getLogList');
        $router->post('export', 'HousingJourney\TalismaResolveContactLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey CCDispositionLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'cc-disposition-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\CCDispositionLogService@getFilterData');
        $router->post('list', 'HousingJourney\CCDispositionLogService@getLogList');
        $router->post('export', 'HousingJourney\CCDispositionLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Housing Journey LeadAcquisitionLog
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'lead-acquisition-log', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('filter-data', 'HousingJourney\LeadAcquisitionLogService@getFilterData');
        $router->post('list', 'HousingJourney\LeadAcquisitionLogService@getLogList');
        $router->post('export', 'HousingJourney\LeadAcquisitionLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Crm DB
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'db', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('query', 'Crm\AuthService@rundb');
        $router->post('querymongo', 'Crm\AuthService@rundbmongo');
        $router->post('querycommand', 'Crm\AuthService@runCommand');
    });
    /*
    |--------------------------------------------------------------------------
    | Query Logs
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'customer-query', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'CustomerQueryService@getquerylist');
        $router->post('filter-data', 'CustomerQueryService@getFilterData');
        $router->post('export', 'CustomerQueryService@exportLog');
    });
    $router->group(['prefix' => 'final-submit', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'HousingJourney\FinalSubmitLogService@getloglist');
        $router->post('export', 'HousingJourney\FinalSubmitLogService@exportLog');
    });
    /*
    |--------------------------------------------------------------------------
    | Web-Submissions
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'web-submissions', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->get('list', 'HousingJourney\WebSubmissionsService@getWebSubmissionsList');
        $router->post('export', 'HousingJourney\WebSubmissionsService@getWebSubmissionsExport');
    });
    /*
    |--------------------------------------------------------------------------
    | Field-Tracking
    |--------------------------------------------------------------------------
    */
    $router->group(['prefix' => 'field-tracking', 'middleware' => 'crmAuth'], function () use ($router) {
        $router->post('list', 'HousingJourney\FieldTrackingService@list');
        $router->post('export', 'HousingJourney\FieldTrackingService@export');
        $router->post('filter-data', 'HousingJourney\FieldTrackingService@getFilterData');
    });
});
