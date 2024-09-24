<?php

namespace App\Utils;

use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\FieldTrackingRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;

trait CommonTrait
{
    /**
     * define the guzzleApiCall method
     *
     * @param  $apiLogRepo
     * @return mixed
     */


    /**
     * get Current stepId
     *
     * @param  $requestData
     */
    public function getCurrentStepId($requestData)
    {
        $nextStage = config('constants/productStepHandle.' . $requestData['next_stage']);
        $impRepo = new ImpressionRepository();
        $currenStepId = '';
        switch ($nextStage) {
            case "personal-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.personal-details'));
                break;
            case "employment-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.employment-details'));
                break;
            case "applicant-eligibility":

                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.eligibility'));
                break;
            case "co-applicant-personal-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.co-applicant-personal-details'));
                break;
            case "co-applicant-employment-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.co-applicant-employment-details'));
                break;
            case "co-applicant-eligibility":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.co-applicant-eligibility'));
                break;
            case "address-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.address-details'));
                break;
            case "property-loan-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.property-loan-details'));
                break;
            case "offer-details":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.offer-details'));
                break;
            case "document-upload":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.document-upload'));
                break;
            case "paymentL1":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.payment'));
                break;
            case "sanction-letter":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.sanction-letter'));
                break;
            case "paymentL2":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.payment'));
                break;
            case "congratulation":
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.congratulation'));
                break;
        }
        return $currenStepId;
    }
    /**
     * push data to field trackinglog
     *
     * @param  $requestData
     */
    public function pushDataFieldTrackingLog($requestData)
    {
        $apRepo = new ApplicationRepository();
        $appData = $apRepo->getQuoteIdDetails($requestData);
        $logData['lead_id'] = $appData->lead_id ?? null;
        $logData['quote_id'] = $requestData->quote_id ?? null;
        $logData['cc_quote_id'] = $appData->cc_quote_id ?? null;
        $logData['mobile_number'] = $appData->mobile_number ?? null;
        $logData['master_product_id'] = $appData->master_product_id ?? null;
        if (isset($requestData['api_type']) &&  $requestData['api_type'] == 'PAYMENT_CALL_BACK') {
            $logData['lead_id'] = $appData->lead_id ?? null;
            $logData['quote_id'] = $requestData['quote_id'] ?? null;
            $logData['api_source'] = $requestData['api_source'] ?? null;
            $logData['api_source_page'] = $requestData['api_source_page'] ?? null;
            $logData['api_data'] =
                ($requestData['api_type'] == 'PAYMENT_CALL_BACK') ? $requestData->all() : $requestData;
        } else {
            $logData['api_source'] = $requestData->header('X-api-Source') ?? null;
            $logData['api_source_page'] = $requestData->header('X-api-Source-Page') ?? null;
            $logData['api_data'] = $requestData->all();
        }
        $logData['api_type'] = config('constants/apiType.FIELD_TRACKING');
        $logData['api_header'] = $requestData->header ?? null;
        $logData['api_url'] = env('WEBSITE_URL') . 'api/v1/housing-journey/field-tracking';
        $logData['cc_push_status'] = 0;
        $logData['cc_push_tag'] = 0;
        $logData['cc_push_stage_id'] = $apRepo->getCCStage($requestData['cc_stage_handle']);
        $ccSubStageData = $apRepo->getCCSubStage($requestData['cc_sub_stage_handle']);
        $logData['cc_push_sub_stage_id'] = $ccSubStageData->id ?? null;
        $logData['cc_push_sub_stage_priority'] = $ccSubStageData->priority ?? null;
        $logData['cc_push_block_for_calling'] = $ccSubStageData->block_for_calling ?? null;
        $logData['api_request_type'] = config('constants/apiType.REQUEST');

        $ccLog = new FieldTrackingRepository();
        $ccLog->save($logData);
    }
    /**
     * create new quote id
     *
     * @param 
     * @return mixed
     */
    public function createNewQuoteID()
    {
        $randomNumber = random_int(100000, 999999);
        $length = 6;
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, $length);
        return $randomString . $randomNumber;
    }
    /**
     * create web site auth token
     *
     * @param $leadId, $mobileNumber, $quoteId
     * @return mixed
     */
    private function createWebsiteAuthToken($leadId, $mobileNumber, $quoteId)
    {
        $combination = $leadId . '-' . $mobileNumber . '_' . $quoteId;
        $token = Crypt::encrypt($combination);
        $expirationTime = 60 * 60; // 3600 sec (1 hour)
        $redis = Redis::connection();
        $redis->set(
            $leadId,
            $token,
            'EX',
            $expirationTime
        );
        return Redis::get($leadId);
    }
}
