<?php

namespace App\Services\HousingJourney;

use Throwable;
use App\Services\Service;
use App\Utils\CoreTrait;
use App\Utils\CommonTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\CoreRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Repositories\HousingJourney\CibilLogRepository;
use App\Repositories\HousingJourney\WebSubmissionRepository;
use App\Repositories\HousingJourney\MasterPincodeRepository;

define('API_TYPE_OOPS_MESSAGE', config('journey/http-status.oops.message'));
define('API_TYPE_OOPS_CODE', config('journey/http-status.oops.code'));
define('API_TYPE_OOPS_STATUS', config('journey/http-status.oops.status'));
define('API_TYPE_SUCCESS_STATUS', config('journey/http-status.success.status'));
define('API_TYPE_SUCCESS_CODE', config('journey/http-status.success.code'));
define('API_TYPE_SUCCESS_MESSAGE', config('journey/http-status.success.message'));
class CoreService extends Service
{
    use CoreTrait;
    use CommonTrait;
    public function save(Request $request, ApplicationRepository $apRepo, ImpressionRepository $impRepo)
    {
        try {
            $request['api_source_page'] = config('constants/apiSourcePage.SANCTION_LETTER_DOWNLOAD');
            $finalSubmit = $this->prepareFinalSubmitData($request->all());
            $logPushData = $request;
            $logPushData['cc_stage_handle'] = 'sanction';
            $logPushData['cc_sub_stage_handle'] = 'sanction-not-generated';
            if ($finalSubmit && isset($finalSubmit['WebSiteID'])) {
                // update into application
                $ccRepo = new CoreRepository();
                $ccPushStatus = $ccRepo->getQuoteCount($request['quote_id']);
                $application['is_purchased'] = 1;
                $application['is_stp'] = $ccPushStatus == 0 ? 1 : 0;
                $applicationData = $apRepo->getApplication($request['quote_id']);
                $application['lead_id'] = $applicationData['lead_id'];
                $application['quote_id'] = $request['quote_id'];
                $application['master_product_step_id'] = 15;
                $apRepo->save($application);
                // save into impression
                $impression['lead_id'] = $applicationData['lead_id'];
                $impression['quote_id'] = $applicationData['quote_id'];
                $impression['master_product_id'] = $applicationData['master_product_id'];
                $impression['master_product_step_id'] = 15;
                $impRepo->save($impression);
                $logPushData = $request;
                $logPushData['cc_stage_handle'] = 'sanction';
                $logPushData['cc_sub_stage_handle'] = 'sanction-generated';
                $this->pushDataFieldTrackingLog($logPushData);
                return $this->responseJson(
                    API_TYPE_SUCCESS_STATUS,
                    API_TYPE_SUCCESS_MESSAGE,
                    API_TYPE_SUCCESS_CODE,
                    $finalSubmit
                );
            } elseif ($finalSubmit && $finalSubmit == config('journey/http-status.timeout.message')) {
                $this->pushDataFieldTrackingLog($logPushData);
                return $this->responseJson(
                    config('journey/http-status.timeout.status'),
                    config('journey/http-status.timeout.message'),
                    config('journey/http-status.timeout.code')
                );
            } else {
                $this->pushDataFieldTrackingLog($logPushData);
                $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    $finalSubmit
                );
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CoreService save " . $throwable->__toString());
        }
    }

    public function partnerFetch(Request $request)
    {
        try {
            $reqData['Action'] = 'EmpCd';
            $reqData['SearchBy'] = $request->partner_code;
            $parnerData = $this->partnerFetchApi($reqData);
            if ($parnerData && $parnerData == config('journey/http-status.timeout.message')) {
                return $this->responseJson(
                    config('journey/http-status.timeout.status'),
                    config('journey/http-status.timeout.message'),
                    config('journey/http-status.timeout.code')
                );
            } else {
                return $this->responseJson(
                    API_TYPE_SUCCESS_STATUS,
                    API_TYPE_SUCCESS_MESSAGE,
                    API_TYPE_SUCCESS_CODE,
                    $parnerData
                );
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CoreService partnerFetch " . $throwable->__toString());
        }
    }

    public function panFetch(Request $request)
    {
        try {
            $reqData['PanNo'] = $request->pan;
            $panData = $this->fetchAddressFromKarza($reqData);
            return $this->responseJson(
                API_TYPE_SUCCESS_STATUS,
                API_TYPE_SUCCESS_MESSAGE,
                API_TYPE_SUCCESS_CODE,
                $panData
            );
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CoreService panFetch " . $throwable->__toString());
        }
    }

    public function cibilFetch(Request $request, CoreRepository $coreRepo)
    {
        try {
            $viewPanHistroy = $coreRepo->viewCibilHistroy(strtoupper($request['pan']));
            $reqData['Data']['FName'] = $request['full_name'];
            $reqData['Data']['Gender'] = $request['gender'] == 'Male' ? 1 : 2;
            $reqData['Data']['BIRTHDT'] = $request['dob'];
            $reqData['Data']['cbl_PANId'] = strtoupper($request['pan']);
            $reqData['Data']['cbl_PhoneNo'] = $request['mobile_number'];
            // insert into cibil log
            $logData['lead_id'] = null;
            $logData['quote_id'] = null;
            $logData['pan'] = strtoupper($request['pan']);
            $logData['mobile_number'] = $request['mobile_number'];
            $logData['master_product_id'] = null;
            $logData['api_source'] = config('constants/apiSource.CORE');
            $logData['api_source_page'] = $request->header('X-Api-Source-Page') ?? null;
            $logData['api_type'] = config('constants/apiType.FETCH_CIBIL_DATA');
            $logData['api_header'] = $request['header'] ?? null;
            $logData['api_url'] = env('CORE_API_URL') . 'FetchCibilDetails';
            $logData['api_request_type'] = config('constants/apiType.REQUEST');
            $logData['api_request'] = $reqData;
            $logData['api_status_code'] = config('journey/http-status.success.code');
            $logData['api_status_message'] = config('journey/http-status.success.message');
            $cibilLog = new CibilLogRepository();
            if (
                isset($viewPanHistroy)

            ) {
                $currentDate = Carbon::now()->format('Y-m-d');
                $carbonDate = Carbon::createFromTimestampMs($viewPanHistroy->created_at);
                $createdDate = $carbonDate->format('Y-m-d');
                $to = Carbon::parse($currentDate);
                $from = Carbon::parse($createdDate);
                $days = $to->diffInDays($from);
                if ($days < 30) {
                    $logData['cibil_from'] = 'Self';
                    $cibilLog->save($logData);
                    if ($viewPanHistroy->api_response) {
                        $response['score'] = json_decode($viewPanHistroy->api_response['result_1'], true);
                        $response['document'] = $viewPanHistroy->api_response['result_2'];
                    } else {
                        $cibilData = $this->getCibilData($reqData);
                        $response['score'] = json_decode($cibilData['result_1'], true);
                        $response['document'] = $cibilData['result_2'];
                    }

                    return $this->responseJson(
                        API_TYPE_SUCCESS_STATUS,
                        API_TYPE_SUCCESS_MESSAGE,
                        API_TYPE_SUCCESS_CODE,
                        $response
                    );
                } else {
                    $logData['cibil_from'] = 'Core';
                    $cibilLog->save($logData);
                    $cibilData = $this->getCibilData($reqData);
                }
            } else {
                $logData['cibil_from'] = 'Core';
                $cibilLog->save($logData);
                $cibilData = $this->getCibilData($reqData);
            }
            $response['score'] = json_decode($cibilData['result_1'], true);
            $response['document'] = $cibilData['result_2'];
            $logData['api_response'] = $cibilData;
            $logData['api_request_type'] = config('constants/apiType.RESPONSE');
            $logData['api_status_code'] = config('journey/http-status.success.code');
            $logData['api_status_message'] = config('journey/http-status.success.message');
            $cibilLog = new CibilLogRepository();
            $cibilLog->save($logData);
            return $this->responseJson(
                API_TYPE_SUCCESS_STATUS,
                API_TYPE_SUCCESS_MESSAGE,
                API_TYPE_SUCCESS_CODE,
                $response
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CoreService cibilFetch " . $throwable->__toString());
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }

    public function saveBvncalls(Request $request, CoreRepository $coreRepo)
    {
        try {
            $rules = [
                "store_code" => "min:2|max:100",
                "business_name" => "min:2|max:90",
                "customer_phone_number" => "numeric|digits_between:10,12",
                "bvn_call_status" => "min:2|max:15",
                "recording_url" => "max:150"
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $bvnCallsSave = $coreRepo->saveBvncalls($request->all());
            if ($bvnCallsSave) {
                return $this->responseJson(
                    API_TYPE_SUCCESS_STATUS,
                    API_TYPE_SUCCESS_MESSAGE,
                    API_TYPE_SUCCESS_CODE,
                    []
                );
            }
            return $this->responseJson(
                API_TYPE_OOPS_STATUS,
                API_TYPE_OOPS_MESSAGE,
                API_TYPE_OOPS_CODE,
                []
            );
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CoreService saveBvncalls " . $throwable->__toString());
        }
    }
    public function saveGoogleChat(Request $request, CoreRepository $coreRepo)
    {
        try {
            $rules = [
                "store_code" => "min:2|max:100",
                "business_name" => "min:2|max:90",
                "user_name" => "min:2|max:100",
                "phone_number" => "numeric|digits_between:10,12",
                "email_address" => "email|max:50",
                "chatbot_option" => "max:100",
                "tags" => "max:100"
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $googleChatSave = $coreRepo->saveGoogleChat($request->all());
            if ($googleChatSave) {
                return $this->responseJson(
                    API_TYPE_SUCCESS_STATUS,
                    API_TYPE_SUCCESS_MESSAGE,
                    API_TYPE_SUCCESS_CODE,
                    []
                );
            }
            return $this->responseJson(
                API_TYPE_OOPS_STATUS,
                API_TYPE_OOPS_MESSAGE,
                API_TYPE_OOPS_CODE,
                []
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CoreService saveGoogleChat " . $throwable->__toString());
        }
    }
    public function saveMicrosite(Request $request, CoreRepository $coreRepo)
    {
        try {
            $rules = [
                "store_code" => "min:2|max:100",
                "business_name" => "min:2|max:90",
                "customer_name" => "min:2|max:100",
                "mobile_number" => "numeric|digits_between:10,12",
                "pin_code" => "min:6|max:10",
                "loan_amount" => "max:50"
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $microSiteSave = $coreRepo->saveMicrosite($request->all());
            if ($microSiteSave) {
                return $this->responseJson(
                    API_TYPE_SUCCESS_STATUS,
                    API_TYPE_SUCCESS_MESSAGE,
                    API_TYPE_SUCCESS_CODE,
                    []
                );
            }
            return $this->responseJson(
                API_TYPE_OOPS_STATUS,
                API_TYPE_OOPS_MESSAGE,
                API_TYPE_OOPS_CODE,
                []
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CoreService saveMicrosite " . $throwable->__toString());
        }
    }
    public function unsubscribeUsers(Request $request)
    {
        try {
            $token  = $request->header('X-Auth-Token');
            if ($token) {
                $apRepo = new ApplicationRepository();
                $appData = $apRepo->getAuthTokenByToken($token);
                if ($appData) {
                    $personalRepo = new PersonalDetailRepository();
                    $reqData['lead_id'] = $appData->lead_id;
                    $reqData['quote_id'] = $appData->quote_id;
                    $personalData = $personalRepo->getPersonalData($reqData);
                    if ($personalData) {
                        $personalDt['lead_id'] = $appData->lead_id;
                        $personalDt['quote_id'] = $appData->quote_id;
                        $personalDt['unsubscribe'] = 1;
                        $personalRepo->save($personalDt);
                        return $this->responseJson(
                            API_TYPE_SUCCESS_STATUS,
                            API_TYPE_SUCCESS_MESSAGE,
                            API_TYPE_SUCCESS_CODE,
                            []
                        );
                    }
                }
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CoreService unsubscribeUsers " . $throwable->__toString());
        }
    }
}
