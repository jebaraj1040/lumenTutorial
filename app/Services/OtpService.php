<?php

namespace App\Services;

use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\HttpClientException;
use App\Utils\JourneyTrait;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\OtpLogRepository;
use App\Constants\WebSite\LeadSubmission;
use App\Repositories\HousingJourney\CoreRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\MasterProductRepository;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use App\Entities\MongoLog\LeadAcquisitionLog;
use App\Repositories\HousingJourney\BreLogRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use App\Repositories\HousingJourney\WebsubmissionRepository;
use Illuminate\Support\Facades\Validator;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use App\Utils\CommonTrait;
use App\Entities\HousingJourney\HjMasterProduct;


// Define constant
define('OTP_INVALID_MESSAGE', config('constants/otpMessage.OTP_INVALID'));
define('API_TYPE_RESPONSE_CONFIG', config('constants/apiType.RESPONSE'));
define('MAX_ATTEMPTS_MESSAGE', "You've reached maximum attempts. Please try again after sometimes.");
define('OTP_RESET_MESSAGE',  config('constants/otpMessage.OTP_RESET'));
define('OTP_VERIFIED_MESSAGE', config('constants/otpMessage.OTP_VERIFIED'));
define('THIRD_PARTY_SMS_SOURCE', config('constants/apiSource.THIRD_PARTY_SMS'));

class OtpService extends Service
{
    /**
     * Call trait helper function
     *
     * @param
     * @return mixed
     */
    use JourneyTrait;
    use CommonTrait;
    /**
     * send otp
     *
     * @param  Request $request, $leadRepo, $otpRepo
     */
    public function sendOtp(
        Request $request,
        LeadRepository $leadRepo,
        OtpLogRepository $otpRepo,
        WebsubmissionRepository $webRepo
    ) {
        try {
            $request['source_page'] = $request->header('X-Api-Source-Page');
            if ($request['source_page'] == "TRACK_APPLICATION") {
                $rules = [
                    'custom_string' => 'required|regex:/^[a-zA-Z]{6}[0-9]{6}$/',
                    "mobile_number" => "numeric|digits:10|required"
                ];
                $customString = $this->checkCustomString($request->custom_string);
                if (!$customString) {
                    return $this->responseJson(
                        config('journey/http-status.invalid-mobile.status'),
                        'Invalid application ID',
                        config('journey/http-status.invalid-mobile.code'),
                        []
                    );
                }
            } else {
                $rules = [
                    "mobile_number" => "numeric|digits:10|required"
                ];
            }
            $this->validationCheck($request, $rules);
            $isSpam = $this->isSpam($request->mobile_number);

            if ($isSpam) {
                return $this->responseJson(
                    config('journey/http-status.invalid-mobile.status'),
                    config('journey/http-status.invalid-mobile.message'),
                    config('journey/http-status.invalid-mobile.code'),
                    []
                );
            }


            if (env('APP_ENV') !== 'live' && env('APP_ENV') !== 'uat') {
                $randOtp = "333333";
            } else {
                $randOtp = random_int(100000, 999999);
            }
            $record = $request;
            $request['randOtp'] = $randOtp;
            $apiType = config('constants/apiType.OTP_SENT');

            $apiLogData['source_page'] = $request->header('X-Api-Source-Page');
            $request['master_product_id'] = '';
            if (
                $request->header('X-Api-Source-Page') == 'HOME_PAGE' ||
                $request->header('X-Api-Source-Page') == 'APPLY_ONLINE_PAGE'
            ) {
                $productId = $this->getProductIdByCode($request->product_code);
                $request['master_product_id'] = $productId;
            }
            $type =  config('constants/apiSource.THIRD_PARTY_SMS');
            $payLoad['api_type'] = $apiType;
            $payLoad['api_source'] = $request->header('X-Api-Source');
            $payLoad['api_log_data'] = $apiLogData;
            $payLoad['type'] =  $type;
            $payLoad['api_data'] =  $request;
            $otpFlag =  config('constants/otpMessage.OTP_SENT');
            if (empty($request->header('X-Api-Source-Page')) === false) {
                if (
                    empty($request->header('X-Api-Source-Page')) === false
                    && in_array($request->header('X-Api-Source-Page'), LeadSubmission::PAGE_SOURCE)
                ) {
                    try {
                        $productPageFlag = false;
                        $otpData['master_product_id'] = '';
                        // save utm datas.
                        $this->saveUtmDatas($request);
                        if (
                            $request->header('X-Api-Source-Page') == 'HOME_PAGE' ||
                            $request->header('X-Api-Source-Page') == 'APPLY_ONLINE_PAGE'
                        ) {
                            $productId = $this->getProductIdByCode($request->product_code);
                            if (empty($productId)) {
                                return $this->responseJson(
                                    config('journey/http-status.bad-request.status'),
                                    'Invalid Product Id',
                                    config('journey/http-status.bad-request.code'),
                                    []
                                );
                            }
                            $otpData['master_product_id'] = $productId;
                            $otpData['loan_amount'] = $request->loan_amount;
                            $productPageFlag = true;
                        }
                        // otp log insert.
                        $otpData['is_otp_sent'] = 1;
                        $otpData['mobile_number'] = (string) $request->mobile_number;
                        $otpData['otp_value'] = $randOtp;
                        $otpData['otp_flag'] = $otpFlag;
                        $otpData['otp_expiry'] = date('Y-m-d H:i:s');
                        $otpData['is_otp_verified'] = 0;
                        $otpData['is_otp_resent'] = 0;
                        $otpData['api_source'] = $request->header('X-Api-Source');
                        $otpData['api_source_page'] = $request->header('X-Api-Source-Page');
                        $otpData['api_type'] = $request->header('X-Api-Type');
                        $otpData['api_header'] = $request->header();
                        $otpData['api_url'] = "";
                        $otpData['api_request_type'] = config('constants/apiType.RESPONSE');
                        $otpData['api_data'] = "";
                        $otpData['api_status_code'] = config('journey/http-status.success.code');
                        $otpData['api_status_message'] = config('journey/http-status.success.message');
                        $saveOtp = $otpRepo->save($otpData);
                        $apiResponse = $this->sendSms($request, $payLoad);
                        if ($productPageFlag) {
                            $rules = [
                                "pincode_id" => "required"
                            ];
                            $validator = Validator::make($request->all(), $rules);
                            if ($validator->fails()) {
                                return $this->responseJson(
                                    config('journey/http-status.bad-request.status'),
                                    'Pincode is required',
                                    config('journey/http-status.bad-request.code'),
                                    []
                                );
                            }
                            $leadData['is_applicant'] = 1;
                            $leadData['mobile_number'] = (string)$request->mobile_number;
                            $leadData['name'] = ucwords($request['name']);
                            $leadData['pincode_id'] = $request->pincode_id;
                            $leadData['is_being_assisted'] = isset($request->is_assited) ? $request->is_assited : false;
                            $leadData['partner_code'] = $leadData['is_being_assisted'] ? $request->partner_code : null;
                            $leadData['partner_name'] = $leadData['is_being_assisted'] ? $request->partner_name : null;
                            $leadData['home_extension'] = $request->home_extension;
                            $leadData['sub_partner_code'] = $request->sub_partner_code;
                            $leadData['is_agreed'] = $request->is_agreed;
                            if ($request->email) {
                                $leadData['email'] = $request->email ?? null;
                            }
                            $leadRepo->save($leadData);
                        }
                        $requestData['request'] = $request;
                        $data['mobile_number'] = $request->mobile_number;
                        $webRepo->save($record);
                        if ($saveOtp) {
                            return $this->responseJson(
                                config('journey/http-status.success.status'),
                                config('journey/http-status.success.message'),
                                config('journey/http-status.success.code'),
                                $data
                            );
                        }
                    } catch (Throwable  | HttpClientException $throwable) {
                        Log::info("OtpService -  sendOtp " . $throwable);
                        return $this->responseJson(
                            config('journey/http-status.error.status'),
                            config('journey/http-status.error.message'),
                            config('journey/http-status.error.code'),
                            []
                        );
                    }
                } elseif ($request['source_page'] == "TRACK_APPLICATION") {
                    // Track a Application Send OTP
                    $addonParams['randOtp'] = $randOtp;
                    $addonParams['otpFlag'] = $otpFlag;
                    $addonParams['payLoad'] = $payLoad;
                    $addonParams['record'] = $record;
                    return $this->trackApplicationSendSMS($request, $addonParams);
                } else {
                    return $this->responseJson(
                        config('journey/http-status.bad-request.status'),
                        config('journey/http-status.bad-request.message'),
                        config('journey/http-status.bad-request.code'),
                        []
                    );
                }
            }
            // }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("OtpService -  sendOtp " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.failure.code'),
                []
            );
        }
    }


    public function trackApplicationSendSMS($request, $addonParams)
    {
        try {
            $otpRepo = new OtpLogRepository();
            $webRepo = new WebsubmissionRepository();
            $applicationRepo = new ApplicationRepository();
            // otp log insert.
            $existingApplication = $applicationRepo->checkApplicationExisting($request);
            if (!$existingApplication) {
                return $this->responseJson(
                    config('journey/http-status.no-data-found.status'),
                    config('journey/http-status.no-data-found.message'),
                    config('journey/http-status.no-data-found.code'),
                    []
                );
            }
            $otpData['is_otp_sent'] = 1;
            $otpData['mobile_number'] = (string) $request->mobile_number;
            $otpData['otp_value'] = $addonParams['randOtp'];
            $otpData['otp_flag'] = $addonParams['otpFlag'];
            $payLoad = $addonParams['payLoad'];
            $otpData['otp_expiry'] = date('Y-m-d H:i:s');
            $otpData['is_otp_verified'] = 0;
            $otpData['is_otp_resent'] = 0;
            $otpData['api_source'] = $request->header('X-Api-Source');
            $otpData['api_source_page'] = $request->header('X-Api-Source-Page');
            $otpData['api_type'] = $request->header('X-Api-Type');
            $otpData['api_header'] = $request->header();
            $otpData['api_url'] = "";
            $otpData['api_request_type'] = config('constants/apiType.RESPONSE');
            $otpData['api_data'] = "";
            $otpData['api_status_code'] = config('journey/http-status.success.code');
            $otpData['api_status_message'] = config('journey/http-status.success.message');
            $saveOtp = $otpRepo->save($otpData);
            $apiResponse = $this->sendSms($request, $payLoad);

            $data['mobile_number'] = $request->mobile_number;
            $data['custom_string'] = $request->custom_string;
            $record = $addonParams['record'];
            $webRepo->save($record);
            if ($saveOtp) {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $data
                );
            }
            return false;
        } catch (\Throwable $th) {
            Log::info("trackApplicationSendSMS -  sendOtp " . $th);
        }
    }


    public function checkValidation($request)
    {
        $alphaRegex = 'regex:/^[a-zA-Z\s]+$/';
        $alphaSpaceRegex = 'regex:/^[A-Za-z \.]+$/';
        $alphaNumRegex = 'regex:/^[a-zA-Z0-9]+$/';
        $emailRegex = 'regex:/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[A-Za-z]{2,3})$/';
        $pincodeRegex = 'regex:/^\d{6}$/';
        $maxRegex = 'max:100000000';

        $rules['mobile_number'] = ['numeric', 'digits:10', 'required'];
        if (
            $request->header('X-Api-Source-Page') == "HOME_PAGE" ||
            $request->header('X-Api-Source-Page') == 'APPLY_ONLINE_PAGE'
        ) {
            if (isset($request['is_assited'])) {
                if ($request['is_assited'] === true) {
                    $rules['partner_code'] = ['required', $alphaNumRegex];
                    $rules['partner_name'] = ['required', $alphaSpaceRegex];
                } else {
                    $rules['is_assited'] = ['required'];
                }
            }
            $rules['product_code'] = ['required', $alphaRegex];
            $rules['name'] = ['required', $alphaSpaceRegex];
            $rules['pincode_id'] = ['required', 'numeric'];
            $rules['loan_amount'] = ['required', 'numeric', 'min:750000', $maxRegex];
            $rules['is_agreed'] = ['required'];
        } elseif (in_array($request->header('X-Api-Source-Page'), LeadSubmission::RULE_TWO_SOURCE_PAGES)) {
            $rules['name'] = ['required', $alphaSpaceRegex];
            $rules['product_code'] = ['required', $alphaRegex];
            $rules['pincode_id'] = ['required', 'numeric'];
            $rules['pin_code'] = ['required', 'numeric', 'min:6', 'max:6'];
            $rules['loan_amount'] = ['required', 'numeric', 'min:750000', $maxRegex];
            $rules['is_agreed'] = ['required'];
            $rules['email'] = ['required', $emailRegex];
        }
        if (in_array($request->header('X-Api-Source-Page'), LeadSubmission::RULE_THREE_SOURCE_PAGES)) {
            $rules['name'] = ['required', $alphaSpaceRegex];
            $rules['loan_amount'] = ['required', 'numeric', 'min:100000', $maxRegex];
            $rules['pin_code'] = ['required', $pincodeRegex];
            if ($request->header('X-Api-Source-Page') == "APPROVED_PROPERTIES" || $request->header('X-Api-Source-Page') == "E_AUCTION") {
                $rules['submitCheckBox'] = ['required'];
                $rules['email'] = ['nullable', $emailRegex];
            }
            if ($request->header('X-Api-Source-Page') == "APPROVED_PROPERTIES_DREAM_HOME") {
                $rules['email'] = ['required', $emailRegex];
                $rules['loan_amount'] = ['required', 'numeric', 'min:1000', $maxRegex];
            }
            if ($request->header('X-Api-Source-Page') == "APPROVED_PROPERTIES_GET_CALLBACK") {
                $rules['address'] = ['required', $alphaSpaceRegex];
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }

    /**
     * verify otp.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function verifyOtp(
        Request $request,
        OtpLogRepository $otpRepo,
        ImpressionRepository $impressionRepo,
        ApplicationRepository $applicationRepo,
        BreLogRepository $breLogRepo,
        WebsubmissionRepository $webRepo,
        LeadRepository $leadRepo
    ) {
        try {
            $rules = [
                "otp" => "required|numeric|digits:4",
                "mobile_number" => "required|numeric|digits:10"
            ];
            // Validation Check
            $this->validationCheck($request, $rules);
            $isSpam = $this->isSpam($request->mobile_number);
            if ($isSpam) {
                return $this->responseJson(
                    config('journey/http-status.invalid-mobile.status'),
                    config('journey/http-status.invalid-mobile.message'),
                    config('journey/http-status.invalid-mobile.code'),
                    []
                );
            }
            if (Str::length($request->otp) != 6) {
                $msg = config('constants/otpMessage.OTP_INVALID_MSG');
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    $msg,
                    config('journey/http-status.failure.code'),
                    []
                );
            }
            $apiSourcePage = $request->header('X-Api-Source-Page');
            $reqData['otp'] = $this->otpEmptyCheck($request->otp);
            $reqData['contactNumber'] = $this->contactNumberEmptyCheck($request->mobile_number);
            $reqData['source_page'] = config('constants/apiSource.CAMPAIGN_LANDING_PAGE');
            $reqData['Source'] = config('constants/apiSource.CAMPAIGN_LANDING_PAGE');

            $method = 'GET';
            $apiType = config('constants/apiType.OTP_VERIFIED');
            $apiLogData['source_page'] = $apiSourcePage;
            $type =  THIRD_PARTY_SMS_SOURCE;
            $payLoad['data'] = $reqData;
            $payLoad['api_type'] = $apiType;
            $payLoad['api_log_data'] = $apiLogData;
            $payLoad['method'] = $method;
            $payLoad['type'] =  $type;
            $payLoad['api_data'] =  $request;
            $productData = $otpRepo->getProductId($request->mobile_number);
            $request['source_page'] = $apiSourcePage;
            $request['master_product_id'] = '';
            $otpData['master_product_id'] = '';
            if (
                $apiSourcePage == 'HOME_PAGE' ||
                $apiSourcePage == 'APPLY_ONLINE_PAGE'
            ) {
                $productPageFlag = true;
                $otpData['master_product_id'] = $productData['master_product_id'];
                $request['master_product_id'] = $productData['master_product_id'];
                $otpData['loan_amount'] = $productData['loan_amount'];
            }

            $getOtpData = $otpRepo->checkInvalidOtp($request);

            $sendOtpErr  = false;
            $isOtpVerify = false;
            $count = 1;
            if (empty($getOtpData) === false) {
                foreach ($getOtpData as $otp) {
                    if (
                        $otp['otp_flag'] != OTP_INVALID_MESSAGE
                        && $otp['otp_flag'] != OTP_RESET_MESSAGE
                    ) {
                        $isOtpVerify = true;
                    }
                    // if ($otp['otp_flag'] == OTP_RESET_MESSAGE) {
                    //     $sendOtpErr = true;
                    // }
                    if ($otp['otp_flag'] == OTP_INVALID_MESSAGE) {
                        $count = $count + 1;
                    }
                }

                $getLast = current($getOtpData);
                if ($getLast[0]['otp_flag'] == OTP_INVALID_MESSAGE) {
                    $otpData['max_attempt'] =  $getLast[0]['max_attempt'] - 1;
                } else {
                    $otpData['max_attempt'] = 4;
                }

                if (!$isOtpVerify && $count >= 4) {
                    $otpFlag = OTP_RESET_MESSAGE;
                } else {
                    $otpFlag = OTP_INVALID_MESSAGE;
                }
                if ($count >= 5) {
                    $sendOtpErr = true;
                }
            } else {
                $otpData['max_attempt'] = 4;
            }

            $getOtpData = $otpRepo->verifyOtp($request);
            if (empty($getOtpData) === false && $getOtpData['otp_value'] == $request->otp) {
                $datetimeFormat = Carbon::now()->format('d-m-Y H:i.s.u');
                $toTime = strtotime($datetimeFormat);
                $fromTime = strtotime($getOtpData['otp_expiry']);
                $otpExpiryTime = ceil(abs($toTime - $fromTime) / 60);
                if ($otpExpiryTime <=  config('journey/sms.otp_expiry')) {
                    $otpData['is_otp_verified'] = 1;
                    $otpData['updated_at'] = Carbon::now();
                    $otpData['mobile_number'] = (string) $request->mobile_number;
                    // otp log insert
                    $otpData['is_otp_sent'] = 1;
                    $otpData['is_otp_resent'] = 0;
                    $otpData['otp_value'] = $productData['otp_value'];
                    $otpData['otp_flag'] = OTP_VERIFIED_MESSAGE;
                    $otpData['otp_expiry'] = date('Y-m-d H:i:s');
                    $otpData['api_source'] = $request->header('X-Api-Source');
                    $otpData['api_source_page'] = $apiSourcePage;
                    $otpData['api_type'] = $request->header('X-Api-Type');
                    $otpData['api_header'] = $request->header();
                    $otpData['api_url'] = null;
                    $otpData['api_request_type'] = API_TYPE_RESPONSE_CONFIG;
                    $otpData['api_data'] = null;
                    $otpData['api_status_code'] = config('journey/http-status.success.code');
                    $otpData['api_status_message'] = config('journey/http-status.success.message');
                    $productPageFlag = false;
                    $otpData['master_product_id'] = '';
                    $webRecord = $request;
                    $webRecord['source_page'] = $apiSourcePage;
                    $webRepo->save($webRecord);
                    $leadReqData['is_otp_verified'] = 1;
                    $leadReqData['mobile_number'] = (string) $request->mobile_number;
                    $leadRepo->updateOtpStatus($leadReqData);
                    if (
                        $apiSourcePage == 'HOME_PAGE'  ||
                        $apiSourcePage == 'APPLY_ONLINE_PAGE'
                    ) {
                        $productPageFlag = true;
                        $otpData['master_product_id'] = $productData['master_product_id'];
                        $otpData['loan_amount'] = $productData['loan_amount'];
                        $this->updateUnsubscribeUsers($request->mobile_number, $productData['master_product_id']);
                    }
                    $otpRepo->save($otpData);
                    $otpAttemptLog = $otpRepo->getOtpAttempts($request);
                    if (isset($otpAttemptLog->master_product_id)) {
                        $otpData['product_code'] = $otpAttemptLog->master_product_id;
                        $otpRepo->removeOtpAttempts($otpData);
                    }
                    //Remove OTP Token from redis after otp verified
                    $this->removeOTPTokenFromRedis($request);
                    if ($productPageFlag) {
                        $existingApplication = $applicationRepo->checkProductExisting($productData['master_product_id'], $request->mobile_number);
                        if ($existingApplication && $apiLogData['source_page'] != "TRACK_APPLICATION") {
                            // customer fetch API call for PAN core call
                            $requestData['lead_id'] = $this->getLeadFromMobile($request->mobile_number);
                            $requestData['quote_id'] = $existingApplication->quote_id;
                            $requestData['api_source'] = config('constants/apiSource.CORE');
                            $requestData['api_type'] = config('constants/apiType.CUST_DEDUPE');
                            $requestData['api_source_page'] = $apiSourcePage;
                            $customerStatus = $this->customerDedupeApiCall($requestData, $request->mobile_number);
                            if ($customerStatus == 'Connection timeout') {
                                return $this->responseJson(
                                    config('journey/http-status.timeout.status'),
                                    config('journey/http-status.timeout.message'),
                                    config('journey/http-status.timeout.code')
                                );
                            }
                            $authToken = $this->createWebsiteAuthToken(
                                $existingApplication['lead_id'],
                                $request->mobile_number,
                                $existingApplication['quote_id']
                            );
                            $application['lead_id'] =  $existingApplication['lead_id'];
                            $application['quote_id'] = $existingApplication['quote_id'];
                            $application['auth_token'] = $authToken;
                            $applicationRepo->save($application);
                            return $this->responseJson(
                                config('journey/http-status.success.status'),
                                config('journey/http-status.success.message'),
                                config('journey/http-status.success.code'),
                                [
                                    'auth_token' => $authToken,
                                ]
                            );
                        }

                        $quoteId = $this->createQuoteID();
                        $leadData = $this->getLeadFromMobile($request->mobile_number);
                        $application['lead_id'] = $impression['lead_id'] = $leadData['id'];
                        $application['name'] = $leadData['name'];
                        $application['quote_id'] = $impression['quote_id'] = $quoteId;
                        $application['master_product_id'] = $productData['master_product_id'];
                        $application['master_origin_product_id'] = $productData['master_product_id'];
                        $application['loan_amount'] = $productData['loan_amount'];
                        $impression['master_product_id'] = $productData['master_product_id'];
                        $application['master_product_step_id'] = $impression['master_product_step_id'] = 2;
                        $impressionData = $impressionRepo->save($impression);
                        $application['current_impression_id'] = $impressionData['id'];
                        $application['previous_impression_id'] = $impressionData['id'];
                        $application['mobile_number'] = $request->mobile_number;
                        $application['digital_transaction_no'] = $this->generateRandomString("digitalTransactionID");
                        $breVersionDate = $breLogRepo->getBreVersionDate();
                        if ($breVersionDate) {
                            $application['bre_version_date'] = $breVersionDate->created_at;
                        }
                        $application['session_auth_token'] = $request->header('X-Session-Token');
                        $application['auth_token'] = $this->createWebsiteAuthToken($application['lead_id'], $request->mobile_number, $application['quote_id']);
                        $application['cc_quote_id'] = 'CC' . $this->createQuoteID();
                        $applicationRepo->save($application);
                        // customer fetch API call for PAN core call
                        $requestData['lead_id'] = $this->getLeadFromMobile($request->mobile_number);
                        $requestData['quote_id'] = $quoteId;
                        $requestData['api_source'] = config('constants/apiSource.CORE');
                        $requestData['api_type'] = config('constants/apiType.CUST_DEDUPE');
                        $requestData['api_source_page'] = $apiSourcePage;
                        // insert into field tracking log
                        $logPushData = $request;
                        $logPushData['cc_stage_handle'] = 'personal-details-applicant';
                        $logPushData['cc_sub_stage_handle'] = 'personal-details-applicant-pending';
                        $logPushData['lead_id'] = $requestData['lead_id']->id;
                        $logPushData['quote_id'] = $quoteId;
                        $this->pushDataFieldTrackingLog($logPushData);
                        $customerStatus = $this->customerDedupeApiCall($requestData, $request->mobile_number);
                        return $this->responseJson(
                            config('journey/http-status.success.status'),
                            config('journey/http-status.success.message'),
                            config('journey/http-status.success.code'),
                            [
                                'auth_token' => $application['auth_token']
                            ]
                        );
                    }

                    if ($apiSourcePage == 'CREDIT_SCORE_PAGE') {
                        $apiReqData['Action'] = 'Pin_Dedupe';
                        $apiReqData['SearchBy'] = $request->mobile_number;
                        $response = $this->customerFetchApi($apiReqData);
                        return $this->responseJson(
                            config('journey/http-status.success.status'),
                            config('journey/http-status.success.message'),
                            config('journey/http-status.success.code'),
                            $response
                        );
                    } elseif ($apiSourcePage == "TRACK_APPLICATION") {
                        $existingApplication = $applicationRepo->checkApplicationExisting($request);
                        if ($existingApplication) {
                            $appData['lead_id'] =    $existingApplication['lead_id'];
                            $appMobileNumber = $request->mobile_number;
                            $appData['quote_id'] =   $existingApplication['quote_id'];
                            $appData['auth_token'] = $this->createWebsiteAuthToken($appData['lead_id'], $appMobileNumber, $appData['quote_id']);
                            $applicationRepo->save($appData);
                            $customString = $this->checkCustomString($request->custom_string);
                            if ($customString && empty($request->custom_string) === false) {
                                $currentStepData = $impressionRepo->fetchCurrentStepHandle($request);
                                $returnData['auth_token'] = $appData['auth_token'];
                                $returnData['path'] = $currentStepData['stepName']['handle'];
                                return $this->responseJson(
                                    config('journey/http-status.success.status'),
                                    config('journey/http-status.success.message'),
                                    config('journey/http-status.success.code'),
                                    $returnData
                                );
                            }

                            return $this->responseJson(
                                config('journey/http-status.success.status'),
                                config('journey/http-status.success.message'),
                                config('journey/http-status.success.code'),
                                [
                                    'auth_token' => $appData['auth_token'],
                                ]
                            );
                        }
                    } elseif ($apiSourcePage == "ARTICLES_PAGE" || $apiSourcePage == "KNOWLEDGE_CENTER_PAGE") {
                        return $this->responseJson(
                            config('journey/http-status.success.status'),
                            config('journey/http-status.success.message'),
                            config('journey/http-status.success.code'),
                            []
                        );
                    }
                } else {
                    // otp log insert
                    $otpData['is_otp_verified'] = 0;
                    $otpData['updated_at'] = Carbon::now();
                    $otpData['mobile_number'] = (string)$request->mobile_number;
                    $otpData['is_otp_sent'] = 1;
                    $otpData['is_otp_resent'] = 0;
                    $otpData['otp_value'] = $productData['otp_value'];
                    $otpData['otp_flag'] = $otpFlag;
                    $otpData['otp_expiry'] = date('Y-m-d H:i:s');
                    $otpData['api_source'] = $request->header('X-Api-Source');
                    $otpData['api_source_page'] = $request->header('X-Api-Source-Page');
                    $otpData['api_type'] = $request->header('X-Api-Type');
                    $otpData['api_header'] = $request->header();
                    $otpData['api_url'] = null;
                    $otpData['api_request_type'] = API_TYPE_RESPONSE_CONFIG;
                    $otpData['api_data'] = null;
                    $otpData['api_status_code'] = config('journey/http-status.success.code');
                    $otpData['api_status_message'] = config('journey/http-status.success.message');
                    $productPageFlag = false;
                    $otpData['master_product_id'] = '';
                    if (
                        $request->header('X-Api-Source-Page') == 'HOME_PAGE'  ||
                        $request->header('X-Api-Source-Page') == 'APPLY_ONLINE_PAGE'
                    ) {
                        $productPageFlag = true;
                        $otpData['master_product_id'] = $productData['master_product_id'];
                        $otpData['loan_amount'] = $productData['loan_amount'];
                    }
                    $otpRepo->save($otpData);
                    $data['max_attempt'] = $otpData['max_attempt'];
                    return $this->responseJson(
                        config('journey/http-status.otp-expired.status'),
                        config('journey/http-status.otp-expired.message'),
                        config('journey/http-status.otp-expired.code'),
                        []
                    );
                }
            } else {
                // otp log insert
                $otpData['is_otp_verified'] = 0;
                $otpData['updated_at'] = Carbon::now();
                $otpData['mobile_number'] = (string) $request->mobile_number;
                $otpData['is_otp_sent'] = 1;
                $otpData['is_otp_resent'] = 0;
                $otpData['otp_value'] = $productData['otp_value'];
                $otpData['otp_flag'] = $otpFlag;
                $otpData['otp_expiry'] = date('Y-m-d H:i:s');
                $otpData['api_source'] = $request->header('X-Api-Source');
                $otpData['api_source_page'] = $request->header('X-Api-Source-Page');
                $otpData['api_type'] = $request->header('X-Api-Type');
                $otpData['api_header'] = $request->header();
                $otpData['api_url'] = null;
                $otpData['api_request_type'] = API_TYPE_RESPONSE_CONFIG;
                $otpData['api_data'] = null;
                $otpData['api_status_code'] = config('journey/http-status.success.code');
                $otpData['api_status_message'] = config('journey/http-status.success.message');
                $productPageFlag = false;

                $otpRepo->save($otpData);
                $data['max_attempt'] = $otpData['max_attempt'];
                if ($sendOtpErr) {
                    $msg = MAX_ATTEMPTS_MESSAGE;
                    return $this->responseJson(
                        config('journey/http-status.failure.status'),
                        $msg,
                        202,
                        []
                    );
                }
                $msg = config('constants/otpMessage.OTP_INVALID_MSG') . ". You've remaining " . $otpData['max_attempt'] . " attempts";
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    $msg,
                    config('journey/http-status.failure.code'),
                    []
                );
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("OtpService -  verifyOtp " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.failure.code'),
                []
            );
        }
    }


    /**
     * Remove OTP Token From Redis otp.
     *
     * @param  Request $request
     */
    public function removeOTPTokenFromRedis($OtpData)
    {
        $redis = Redis::connection();
        $authTokenType = Crypt::decrypt($OtpData->header('X-Session-Token'));
        if ($authTokenType  && $OtpData->header('X-Api-Source-Page') == 'HOME_PAGE') {
            $redis->del($authTokenType);
        }
        return true;
    }

    /**
     * resend otp.
     *
     * @param  Request $request
     */
    public function reSendOTP(Request $request, OtpLogRepository $otpRepo)
    {
        try {
            $rules = [
                "mobile_number" => "required|numeric|digits:10"
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            if (($this->isSpam($request->mobile_number) === true)) {
                return $this->responseJson(
                    config('journey/http-status.invalid-mobile.status'),
                    config('journey/http-status.invalid-mobile.message'),
                    config('journey/http-status.invalid-mobile.code'),
                    []
                );
            }

            $apiType = config('constants/apiType.OTP_RESENT');
            $apiLogData['source_page'] = $request->header('X-Api-Source-Page');
            $type =  THIRD_PARTY_SMS_SOURCE;
            $payLoad['api_type'] = $apiType;
            $payLoad['api_source'] = $request->header('X-Api-Source');
            $payLoad['api_log_data'] = $apiLogData;
            $payLoad['type'] =  $type;
            $payLoad['api_data'] =  $request;
            if (env('APP_ENV') !== 'live' && env('APP_ENV') !== 'uat') {
                $randOtp = "333333";
            } else {
                $randOtp = random_int(100000, 999999);
            }
            $request['randOtp'] = $randOtp;
            $productData = $otpRepo->getProductId($request->mobile_number);
            $request['source_page'] = $request->header('X-Api-Source-Page');
            if ($request['source_page'] !== "TRACK_APPLICATION") {
                $request['master_product_id'] =  $productData['master_product_id'];
            }
            $getOtpData = $otpRepo->getResendOtp($request);
            $sendOtpErr  = false;
            $maxTime = 0;
            $isOtpVerify = false;
            if (empty($getOtpData) === false) {
                foreach ($getOtpData as $otp) {
                    if ($otp['otp_flag'] == OTP_VERIFIED_MESSAGE) {
                        $isOtpVerify = true;
                    }
                    if ($otp['otp_flag'] == OTP_RESET_MESSAGE) {
                        $sendOtpErr = true;
                        $maxTime = $otp['otp_expiry'];
                    }
                }
                if (!$isOtpVerify && count($getOtpData) >= 4) {
                    $otpFlag = OTP_RESET_MESSAGE;
                } else {
                    $otpFlag =  config('constants/otpMessage.OTP_RESENT');
                }
            }
            if ($sendOtpErr) {
                $datetimeFormat = Carbon::now()->format('d-m-Y H:i.s.u');
                $toTime = strtotime($datetimeFormat);
                $fromTime = strtotime($maxTime);
                $otp_max_limit_time = 31 - (ceil(abs($toTime - $fromTime) / 60));
                $msg = MAX_ATTEMPTS_MESSAGE;
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    $msg,
                    202,
                    []
                );
            }
            $apiResponse = $this->sendSms($request, $payLoad);
            if (env('APP_ENV') !== 'live' && env('APP_ENV') !== 'uat') {
                $apiResponse['apiUrl'] = env('APP_URL');
            }

            if (empty($apiResponse) === false) {
                $otpData['is_otp_resent'] = 1;
                $otpData['mobile_number'] = (string) $request->mobile_number;
                // otp log insert
                $otpData['is_otp_sent'] = 1;
                $otpData['is_otp_verified'] = 0;
                $otpData['otp_value'] = $randOtp;
                $otpData['otp_flag'] = $otpFlag;
                $otpData['master_product_id'] = $productData['master_product_id'];
                $otpData['loan_amount'] = $productData['loan_amount'];
                $otpData['otp_expiry'] = date('Y-m-d H:i:s');
                $otpData['api_source'] = $request->header('X-Api-Source');
                $otpData['api_source_page'] = $request->header('X-Api-Source-Page');
                $otpData['api_type'] = $request->header('X-Api-Type');
                $otpData['api_header'] = $request->header();
                $otpData['api_url'] = $apiResponse['apiUrl'];
                $otpData['api_request_type'] = API_TYPE_RESPONSE_CONFIG;
                $otpData['api_data'] = $apiResponse;
                $otpData['api_status_code'] = config('journey/http-status.success.code');
                $otpData['api_status_message'] = config('journey/http-status.success.message');
                $otpRepo->save($otpData);
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $request->mobile_number
                );
            } elseif (empty($apiResponse) === false && empty($apiResponse['response']) === false && $apiResponse['response']['type'] == "error" && in_array($apiResponse['response']['message'], config('constants/otpMessage'))) {
                return $this->responseJson(
                    config('journey/http-status.otp-expired.status'),
                    $apiResponse['response']['message'],
                    config('journey/http-status.otp-expired.code'),
                    []
                );
            } elseif (empty($apiResponse) === false && empty($apiResponse['response']) === false && $apiResponse['response']['type'] == "error") {
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    $apiResponse['response']['message'],
                    config('journey/http-status.failure.code'),
                    []
                );
            } elseif ($apiResponse['code'] == 500 && $apiResponse['status'] === false && $apiResponse['message'] == "Exception") {
                return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
                );
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("OtpService -  reSendOtp " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.failure.code'),
                []
            );
        }
    }

    /**
     * customer dedupe API call
     *
     * @param  $request
     */
    public function customerDedupeApiCall($requestData, $mobileNumber)
    {
        try {
            $personalRepo = new PersonalDetailRepository();
            $requestData['mobile_number'] = $mobileNumber;
            $existData = $personalRepo->getPanData($requestData);
            if ($existData->isEmpty()) {
                $apiReqData['Action'] = 'Pin_Dedupe';
                $apiReqData['SearchBy'] = $mobileNumber;
                $response = $this->customerFetchApi($apiReqData);
                $leadRepo = new LeadRepository();
                $leadData['lead_id'] = $requestData['lead_id']->id;
                $panData['mobile_number'] = $mobileNumber;
                $panData['lead_id'] = $requestData['lead_id']->id;
                $panData['quote_id'] =  $requestData['quote_id'];
                $panData['api_source'] = config('constants/apiSource.CORE');
                $panData['api_source_page'] = $requestData['api_source_page'];
                $panData['api_type'] = config('constants/apiType.CUST_DEDUPE');
                $panData['url'] = env('CORE_API_URL') . 'Getcustdedupe';
                $panData['api_request_type'] = config('constants/apiType.RESPONSE');
                $panData['api_data'] = $response;
                if (isset($response['Table'])) {
                    $leadData['customer_type'] = $response['Table'][0]['relationship'] ?? 'NTB';
                    $leadRepo->updateCustomerType($leadData);
                    $panData['api_status_code'] = config('journey/http-status.success.code');
                    $panData['api_status_message'] = config('journey/http-status.success.message');
                    $coreRepo = new CoreRepository();
                    $coreRepo->savePanHistory($panData);
                } elseif ($response == config('journey/http-status.timeout.message')) {
                    $panData['api_status_code'] = config('journey/http-status.timeout.code');
                    $panData['api_status_message'] = config('journey/http-status.timeout.message');
                    $coreRepo = new CoreRepository();
                    $coreRepo->savePanHistory($panData);
                    return $response;
                } else {
                    $panData['api_status_code'] = config('journey/http-status.oops.code');
                    $panData['api_status_message'] = config('journey/http-status.oops.message');
                    $coreRepo = new CoreRepository();
                    $coreRepo->savePanHistory($panData);
                }
            }
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                []
            );
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("OtpService -  customerDedupeApiCall " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.failure.code'),
                []
            );
        }
    }

    /**
     * save utm datas.
     *
     * @param $request
     */
    private function saveUtmDatas($request)
    {
        LeadAcquisitionLog::create([
            "mobile_number" => $request->mobile_number,
            "utm_source" => $request->utm_source,
            "utm_medium" => $request->utm_medium,
            "utm_campaign" => $request->utm_campaign,
            "utm_term" => $request->utm_term,
            "utm_content" => $request->utm_content,
            "referrer" =>  $request->referrer,
            "query_string" => $request->query(),
            "api_source" => $request->header('X-Api-Source'),
            "api_source_page" => $request->header('X-Api-Source-Page'),
            "api_type" => $request->header('X-Api-Type'),
            "api_header" => $request->header(),
            "api_url" => $request->url(),
            "api_request_type" => config('constants/apiType.REQUEST'),
            "api_data" => $request,
            "api_status_code" => config('journey/http-status.success.code'),
            "api_status_message" => config('journey/http-status.success.message')
        ]);
    }

    /**
     * send sms.
     *
     * @param $request
     */
    private function sendSms($request, $payLoad)
    {
        $reqData['username'] = config('journey/sms.username');
        $reqData['password'] = config('journey/sms.password');
        if ($request->header('X-Api-Type') == 'OTP_SENT' || $request->header('X-Api-Type') == 'OTP_RESENT') {
            $reqData['message'] = 'Dear Customer, The OTP to authenticate your identity is ' . $request['randOtp'] . '. We thank you for your interest in choosing SHFL. Do not share OTP for security reasons.';
        } else {
            $reqData['message'] = '';
        }
        $reqData['contactNumber'] = $this->contactNumberEmptyCheck($request->mobile_number);
        $apiUrl = sprintf(
            "%susername=%s&password=%s&to=%s&message=%s",
            config('journey/sms.request_otp_url'),
            $reqData['username'],
            $reqData['password'],
            $reqData['contactNumber'],
            $reqData['message'],
        );
        $method = 'GET';
        $payLoad['data'] = $reqData;
        $payLoad['api_url'] = $apiUrl;
        $payLoad['method'] = $method;
        if (env('APP_ENV') !== 'local' && env('APP_ENV') !== 'sit') {
            $apiResponse = $this->clientApiCall($payLoad, [
                'Content-Type' => 'application/json',
                'X-Api-Source' => $payLoad['type'],
                'X-Api-Source-Page' => $request->header('X-Api-Source-Page'),
                'X-Api-Type' => $request->header('X-Api-Type')
            ]);
        }
        $apiResponse['apiUrl'] = $apiUrl;
        return $apiResponse;
    }
    /**
     * Get Product Id by Product Code
     *
     * @param $request
     */
    public function getProductIdByCode($productCode)
    {
        $masterProductRepo = new MasterProductRepository();
        return $masterProductRepo->masterProductIdFetch($productCode);
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
    public function sendOtpCcLog(
        Request $request,
        ApplicationRepository $applicationRepo,
        ApplicationRepository $appRepo
    ) {
        try {
            $appData = $applicationRepo->getMobileNumberByCCQuoteId($request->lead_id);
            $masterProductId = $appData['master_origin_product_id'];
            $masterProduct = HjMasterProduct::find($masterProductId);
            $code = $masterProduct['code'];
            $url = $this->getProductNameUrl($code);
            if ($appData['mobile_number']) {
                $isSpam = $this->isSpam($appData['mobile_number']);
                if ($isSpam) {
                    return $this->responseJson(
                        config('journey/http-status.invalid-mobile.status'),
                        config('journey/http-status.invalid-mobile.message'),
                        config('journey/http-status.invalid-mobile.code'),
                        []
                    );
                }
                $apiType = config('constants/apiType.CC_MESSAGE');
                $apiSourcePage = config('constants/apiSourcePage.CC_QUOTE_INFO_PAGE');
                $type =  config('constants/apiSource.WEB');
                $userEmail = $appRepo->getEmailID($appData['quote_id']);
                $isEmailRequired = true;
                if (empty($userEmail)) {
                    $isEmailRequired = false;
                }
                if ($appData) {
                    if ($appData->bre2_updated_loan_amount != '' && $appData->bre2_updated_loan_amount != 0) {
                        $loanAmount = array(
                            (int)$appData->bre1_loan_amount,
                            (int)$appData->bre1_updated_loan_amount,
                            (int)$appData->bre2_updated_loan_amount
                        );
                        $appData['offer_amount'] = min($loanAmount);
                    } else {
                        $loanAmount = array(
                            (int)$appData->bre1_loan_amount,
                            (int)$appData->bre1_updated_loan_amount
                        );
                        $appData['offer_amount'] = min($loanAmount);
                    }
                }
                $payLoad['api_type'] = $apiType;
                $payLoad['api_source'] = $apiSourcePage;
                $payLoad['type'] =  $type;
                $payLoad['api_data'] =  $request;
                $payLoad['url'] =  $url;
                $payLoad['sms_template_handle'] = $request->stage;
                $payLoad['user_name'] =  env('COMMON_SMS_USERNAME');
                $payLoad['password'] =  env('COMMON_SMS_PASSWORD');
                $payLoad['payment_amount'] = $masterProduct['processing_fee'];
                $payLoad['payment_refence'] = $appData['digital_transaction_no'];
                $payLoad['app_data'] =  $appData;
                $payLoad['mobile_number'] =  $appData['mobile_number'];
                $payLoad['is_short_url_required'] = true;
                $payLoad['is_email_required'] = $isEmailRequired;
                $payLoad['email'] = $userEmail;
                $payLoad['email_template_handle'] =  $request->stage;
                $apiResponse = $this->sendEmailWithSMS($payLoad);
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    []
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.invalid-lead.status'),
                    config('journey/http-status.invalid-lead.message'),
                    config('journey/http-status.invalid-lead.code'),
                    []
                );
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("OtpService -  sendOtp " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.failure.code'),
                []
            );
        }
    }
    /**
     * send sms
     *
     * @param  $appData, $payLoad, $templateHandle
     */
    public function sendCcSms($appData, $payLoad, $templateHandle)
    {
        $baseURL = env('WEBSITE_URL');
        $fullURL = $baseURL . $payLoad['url'];
        $shortenedUrl = $this->shortURL($fullURL,  $payLoad);
        if (!isset($shortenedUrl['response']) && empty($shortenedUrl['response'])) {
            return $this->responseJson(
                config('journey/http-status.bad-request.status'),
                config('journey/http-status.bad-request.message'),
                config('journey/http-status.bad-request.code'),
                []
            );
        }
        $reqData['username'] = config('journey/sms.cc_username');
        $reqData['password'] = config('journey/sms.cc_password');
        $reqData['contactNumber'] = $this->contactNumberEmptyCheck($appData['mobile_number']);
        $reqData['message'] = $this->getTemplateMessage($templateHandle, $appData, $shortenedUrl, $payLoad);
        $apiUrl = sprintf(
            "%susername=%s&password=%s&to=%s&message=%s",
            config('journey/sms.request_otp_url'),
            $reqData['username'],
            $reqData['password'],
            $reqData['contactNumber'],
            $reqData['message'],
        );
        $method = 'GET';
        $payLoad['data'] = $appData;
        $payLoad['api_url'] = $apiUrl;
        $payLoad['method'] = $method;
        $apiResponse = $this->sendEmailWithSMS($payLoad);
        $apiResponse['apiUrl'] = $apiUrl;
        return $apiResponse;
    }

    /**
     * Check Custom Stringregex check
     *
     * @param  $mobile
     * @return bool
     */
    public function checkCustomString($phone): bool
    {
        if ($phone) {
            $regex = '/^[a-zA-Z]{6}[0-9]{6}$/';
            return (preg_match($regex, $phone)) ? true : false;
        }
        return true;
    }

    public function updateUnsubscribeUsers($mobileNumber, $productId)
    {
        try {
            $apRepo = new ApplicationRepository();
            $personalRepo = new PersonalDetailRepository();
            $appData = $apRepo->getQuoteByMobileProduct($mobileNumber, $productId);
            if ($appData && $appData->isNotEmpty()) {
                foreach ($appData as $application) {
                    $reqData['lead_id'] = $application['lead_id'];
                    $reqData['quote_id'] = $application['quote_id'];
                    $reqData['unsubscribe'] = 0;
                    $personalExist = $personalRepo->view($reqData);
                    if ($personalExist) {
                        $personalRepo->save($reqData);
                    }
                }
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("OtpService -  updateUnsubscribeUsers " . $throwable);
        }
    }
}
