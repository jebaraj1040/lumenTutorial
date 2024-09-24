<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\EligibilityRepository;
use Illuminate\Http\Request;
use App\Services\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\MasterDocumentRepository;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use App\Utils\CoreTrait;

class MasterDocumentService extends Service
{
    /**
     * insert into document type table
     *
     */
    use CoreTrait;
    public function save(
        Request $request,
        MasterApiLogRepository $masterApiLogRepo,
        MasterDocumentRepository $documentMasterRepo
    ) {
        try {
            $rules = [
                "name" => "required",
                "max_file" => "required"
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $requestUrl = $request->url . $request->path();
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type']
                = config('constants/masterApiType.DOCUMENT_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogData = $masterApiLogRepo->save($requestData);
            $request['handle'] = strtolower(str_replace(' ', '-', $request->name));
            $documentSave = $documentMasterRepo->save($request->all());
            if ($documentSave) {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.SUCCESS');
                $response = $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    []
                );
                $masterApiLogRepo->update(
                    $masterApiLogData['id'],
                    json_encode($response),
                    $requestData['customHeader']['X-Api-Status']
                );
                return $response;
            } else {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                $response = $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
                $masterApiLogRepo->update(
                    $masterApiLogData['id'],
                    json_encode($response),
                    $requestData['customHeader']['X-Api-Status']
                );
                return $response;
            }
        } catch (Throwable | HttpClientException $throwable) {
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
            $masterApiLogRepo->save($requestData);
            throw new Throwable(sprintf("Service : MasterDocumentService,
            Method : save : %s", $throwable->__toString()));
        }
    }

    /**
     * Get document dropdown
     *
     */

    public function getDocumentDropdown(Request $request, EligibilityRepository $eligiRepo, PropertyLoanDetailRepository $propRepo)
    {
        try {
            $docRepo = new MasterDocumentRepository();
            $empRepo = new EmploymentDetailRepository();
            $impRepo = new ImpressionRepository();
            $documentDetail['document_list'] = $docRepo->getDocumentDropdown();
            $documentDetail['employee_details'] = $empRepo->getEmployeeDetails($request);
            $documentDetail['eligibility_details'] = $eligiRepo->getBre2Eligibile($request);
            $documentDetail['property_details'] = $propRepo->view($request);
            $documentDetail['product_type'] = $impRepo->getProductType($documentDetail['employee_details']['applicationData']['master_product_id']);
            unset($documentDetail['eligibility_details']['quote_id']);
            unset($documentDetail['eligibility_details']['created_at']);
            unset($documentDetail['eligibility_details']['deleted_at']);
            unset($documentDetail['eligibility_details']['id']);
            unset($documentDetail['eligibility_details']['lead_id']);
            unset($documentDetail['eligibility_details']['updated_at']);
            unset($documentDetail['employee_details']['quote_id']);
            unset($documentDetail['employee_details']['applicationData']['quote_id']);
            return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $documentDetail);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }

    public function finalSumbitApiCall(Request $request)
    {
        try {
            $apRepo = new ApplicationRepository();
            $appData = $apRepo->getApplication($request['quote_id']);
            if ($appData->is_paid == 1) {
                $finalSumbitData['quote_id'] = $request->quote_id;
                $finalSumbitData['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                $finalSumbit  = $this->prepareFinalSubmitData($finalSumbitData);
                if ($finalSumbit && isset($finalSumbit['WebSiteID'])) {
                    // update into application
                    $application['is_purchased'] = 1;
                    $application['lead_id'] = $appData['lead_id'];
                    $application['quote_id'] = $request['quote_id'];
                    $apRepo->save($application);
                } elseif ($finalSumbit && $finalSumbit == config('journey/http-status.timeout.message')) {
                    return $this->responseJson(
                        config('journey/http-status.timeout.status'),
                        config('journey/http-status.timeout.message'),
                        config('journey/http-status.timeout.code')
                    );
                } else {
                    $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        $finalSumbit
                    );
                }
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("finalSumbitApiCall " . $throwable->__toString());
        }
    }
}
