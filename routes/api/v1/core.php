<?php
/*
|--------------------------------------------------------------------------
| Housing Journey Core API V1 Routes
|--------------------------------------------------------------------------
*/
$router->group(['prefix' => 'api/v1/core'], function () use ($router) {
    $router->group(['middleware' => ['coreAuthToken']], function ($router) {
        /*
        |--------------------------------------------------------------------------
        | Pincode Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'pincode-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterPincodeService@save');
            $router->post('import', 'HousingJourney\MasterPincodeService@import');
            $router->post('view', 'HousingJourney\MasterPincodeService@view');
            $router->get('state-list', 'HousingJourney\MasterPincodeService@stateList');
        });
        /*
        |--------------------------------------------------------------------------
        | Project Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'project-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterProjectService@save');
            $router->post('import', 'HousingJourney\MasterProjectService@import');
        });
        /*
        |--------------------------------------------------------------------------
        | Company Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'company-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterCompanyService@save');
            $router->post('import', 'HousingJourney\MasterCompanyService@import');
        });

        /*
        |--------------------------------------------------------------------------
        | Branch Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'branch-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterBranchService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Product Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'product-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterProductService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Employment Type Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'employment-type-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterEmploymentTypeService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Industry Type Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'industry-type-master'], function () use ($router) {
            $router->post('import', 'HousingJourney\MasterIndustryTypeService@import');
            $router->post('save', 'HousingJourney\MasterIndustryTypeService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Industry Segment Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'industry-segment-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterIndustrySegmentService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Property Type Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'property-type-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterPropertyTypeService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Property Current State Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'property-current-state-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterPropertyCurrentStateService@save');
        });
        /*
        |--------------------------------------------------------------------------
        |  Document Type Master Detail Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'document-type-master'], function () use ($router) {
            $router->post('save', 'HousingJourney\MasterDocumentService@save');
        });
        /*
        |--------------------------------------------------------------------------
        | Professional Type Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'professional-type'], function () use ($router) {
            $router->post('import', 'HousingJourney\MasterProfessionalTypeService@import');
        });
        /*
        |--------------------------------------------------------------------------
        | Employment Salary Mode Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'employment-salary-mode'], function () use ($router) {
            $router->post('import', 'HousingJourney\MasterEmploymentSalaryModeService@import');
        });
        /*
        |--------------------------------------------------------------------------
        | Employment Constitution Type Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'employment-constitution-type'], function () use ($router) {
            $router->post('import', 'HousingJourney\MasterEmploymentConstitutionTypeService@import');
        });
        /*
        |--------------------------------------------------------------------------
        | Property Purpose Routes
        |--------------------------------------------------------------------------
        */
        $router->group(['prefix' => 'property-purpose'], function () use ($router) {
            $router->post('import', 'HousingJourney\MasterPropertyPurposeService@import');
        });
    });
});
