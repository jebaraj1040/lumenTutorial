<?php

namespace App\Services\HousingJourney;

use App\Entities\HousingJourney\HjApplication;
use App\Repositories\HousingJourney\ApplicationRepository;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use Illuminate\Support\Facades\Validator;
use App\Repositories\HousingJourney\CoreRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\PanApplicantMappingRepository;
use App\Repositories\ApiLogRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\AddressRepository;
use App\Repositories\HousingJourney\OtpLogRepository;
use App\Repositories\HousingJourney\CibilLogRepository;
use Carbon\Carbon;
use App\Utils\CoreTrait;
use App\Utils\CommonTrait;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;
use App\Utils\JourneyTrait;
use App\Jobs\KarzaPanQueue;

define('API_STATUS_SUCCESS_CODE', config('journey/http-status.success.code'));
define('API_STATUS_SUCCESS_MESSAGE', config('journey/http-status.success.message'));
define('API_STATUS_SUCCESS', config('journey/http-status.success.status'));
define('API_STATUS_FAILURE_CODE', config('journey/http-status.failure.code'));
define('API_STATUS_FAILURE_MESSAGE', config('journey/http-status.failure.message'));
define('API_STATUS_FAILURE', config('journey/http-status.failure.status'));
define('API_STATUS_ERROR_CODE',  config('journey/http-status.error.code'));
define('API_STATUS_ERROR_MESSAGE',  config('journey/http-status.error.message'));
define('API_STATUS_ERROR',  config('journey/http-status.error.status'));
define('API_SOURCE_CORE', config('constants/apiSource.CORE'));
define('API_SOURCE_PERSONAL', config('constants/apiSourcePage.PERSONAL_DETAIL_PAGE'));
define('API_TYPE_RESPONSE', config('constants/apiType.RESPONSE'));
define('API_STATUS_INVALID', config('journey/http-status.invalid-mobile.status'));
define('API_STATUS_INVALID_CODE', config('journey/http-status.invalid-mobile.code'));
define('API_STATUS_INVALID_MESSAGE', 'No data found');
define('API_TYPE_KARZA', config('constants/apiType.KARZA_PAN_DATA'));
define('API_STATUS_TIMEOUT_CODE', config('journey/http-status.timeout.code'));
define('API_STATUS_TIMEOUT_MESSAGE', config('journey/http-status.timeout.message'));
define('API_STATUS_TIMEOUT', config('journey/http-status.timeout.status'));
class PersonalDetailService extends Service
{
  use CrmTrait;
  use CommonTrait;
  /**
   * Create a new Service instance.
   *
   */
  use CoreTrait;
  use JourneyTrait;
  private $personalDetailRepo;
  public function __construct(PersonalDetailRepository $personalDetailRepo)
  {
    $this->personalDetailRepo = $personalDetailRepo;
  }
  public function save(
    Request $request,
    ImpressionRepository $impressionRepo,
    ApplicationRepository $applicationRepo,
    PanApplicantMappingRepository $panApplicantMappingRepo,
    ApiLogRepository $apiLogRepo
  ) {
    try {
      $rules = [
        "full_name" => "required | regex:/^[a-zA-Z]+(?: [a-zA-Z]+)*(?:(?!  )[a-zA-Z ])*$/",
        "dob" => "required | regex:/^\d{2}-\d{2}-\d{4}$/",
        "pan" => "required | regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/",
        "gender" => "required | regex:/^[a-zA-Z\s]+$/",
        "email" => "nullable|email"
      ];
      $validator = Validator::make($request->all(), $rules);
      if ($validator->fails()) {
        return $validator->errors();
      }
      // prepare log data.
      $logData['api_source'] = $request->header('X-Api-Source');
      $logData['api_source_page'] = $request->header('X-Api-Source-Page');
      $logData['api_type'] = $request->header('X-Api-Type');
      $logData['api_header'] = $request->header();
      $logData['api_url'] = $request->url();
      $logData['api_request_type'] = API_TYPE_REQUEST;
      $logData['api_data'] = $request->all();
      $logData['api_status_code'] = API_STATUS_SUCCESS_CODE;
      $logData['api_status_message'] = API_STATUS_SUCCESS_MESSAGE;
      $apiLogRepo->save($logData);
      // save into personalDetail Table
      $request['email'] = strtolower($request->email);
      $leadDob = Carbon::createFromFormat('d-m-Y', $request['dob'])->format('Y-m-d');
      $request['dob'] = $leadDob;
      $save = $this->personalDetailRepo->save($request->all());
      if ($save) {
        $request['personal_detail_id'] = $save->id;
        $panApplicantMappingRepo->save($request->all());
        // save into impression Table
        $request['next_stage'] = config('constants/productStepHandle.employment-details');
        $request['master_product_step_id'] = $this->getCurrentStepId($request);
        $impressionSave = $impressionRepo->save($request->all());
        if ($impressionSave->id) {
          $previousImpression = $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
          $request['previous_impression_id'] = $previousImpression->id ?? $impressionSave->id;
          $request['current_impression_id'] = $impressionSave->id;
          $request['api_source_page'] = $request->header('X-Api-Source-Page');
          $cibilScore = $this->getCibilScore($request->all());
          if ($cibilScore == 'Connection timeout') {
            return $this->responseJson(
              API_STATUS_TIMEOUT,
              API_STATUS_TIMEOUT_MESSAGE,
              API_STATUS_TIMEOUT_CODE
            );
          }
          $request['cibil_score'] = $cibilScore;
          if (isset($request['full_name'])) {
            $request['name'] = $request['full_name'];
          }
          $applicationRepo->save($request->all());
        }
        $logPushData = $request;
        $logPushData['cc_stage_handle'] = 'employment-applicant';
        $logPushData['cc_sub_stage_handle'] = 'employment-applicant-pending';
        $this->pushDataFieldTrackingLog($logPushData);
        return $this->responseJson(
          API_STATUS_SUCCESS,
          API_STATUS_SUCCESS_MESSAGE,
          API_STATUS_SUCCESS_CODE,
          []
        );
      } else {
        // prepare log data.
        $logData['api_source'] = $request->header('X-Api-Source');
        $logData['api_source_page'] = $request->header('X-Api-Source-Page');
        $logData['api_type'] = $request->header('X-Api-Type');
        $logData['api_header'] = $request->header();
        $logData['api_url'] = $request->url();
        $logData['api_request_type'] = API_TYPE_REQUEST;
        $logData['api_data'] = $request->all();
        $logData['api_status_code'] = API_STATUS_FAILURE_CODE;
        $logData['api_status_message'] = API_STATUS_FAILURE_MESSAGE;
        $apiLogRepo->save($logData);
        return $this->responseJson(
          API_STATUS_FAILURE,
          API_STATUS_FAILURE_MESSAGE,
          API_STATUS_FAILURE_CODE,
          []
        );
      }
    } catch (Throwable  | HttpClientException $throwable) {
      // prepare log data.
      $logData['api_source'] = $request->header('X-Api-Source');
      $logData['api_source_page'] = $request->header('X-Api-Source-Page');
      $logData['api_type'] = $request->header('X-Api-Type');
      $logData['api_header'] = $request->header();
      $logData['api_url'] = $request->url();
      $logData['api_request_type'] = API_TYPE_REQUEST;
      $logData['api_data'] = $request->all();
      $logData['api_status_code'] = API_STATUS_ERROR_CODE;
      $logData['api_status_message'] = API_STATUS_ERROR_MESSAGE;
      $apiLogRepo->save($logData);
      Log::info("PersonalDetailService -  save " . $throwable);
      return $this->responseJson(
        API_STATUS_ERROR,
        API_STATUS_ERROR_MESSAGE,
        API_STATUS_ERROR_CODE,
        []
      );
    }
  }
  public function view(Request $request)
  {
    try {
      $personalDetail = $this->personalDetailRepo->view($request);
      return $this->responseJson(
        API_STATUS_SUCCESS,
        API_STATUS_SUCCESS_MESSAGE,
        API_STATUS_SUCCESS_CODE,
        $personalDetail
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  view " . $throwable);
    }
  }
  /**
   * Fetch Pan Details
   *
   */
  public function fetchPanDetail(Request $request, LeadRepository $leadRepo)
  {
    try {
      $getMobileNumber = $leadRepo->getLeadDetailsById($request['lead_id']);
      $request['mobile_number'] = $getMobileNumber->mobile_number;
      $customerData =  $this->personalDetailRepo->getPanData($request->all());
      if (!empty($customerData) && isset($customerData[0])) {
        $currentDate = Carbon::now()->format('Y-m-d');
        $carbonDate = Carbon::createFromTimestampMs($customerData[0]->created_at);
        $createdDate = $carbonDate->format('Y-m-d');
        $to = Carbon::parse($currentDate);
        $from = Carbon::parse($createdDate);
        $days = $to->diffInDays($from);
        if ($days < 30) {
          $reqCustomerData = $customerData[0]->api_data;
          unset($customerData['created_at']);
          unset($reqCustomerData['Table']);
          return $this->responseJson(
            API_STATUS_SUCCESS,
            API_STATUS_SUCCESS_MESSAGE,
            API_STATUS_SUCCESS_CODE,
            $reqCustomerData
          );
        } else {
          $requestData['lead_id'] = $request['lead_id'];
          $requestData['quote_id'] = $request['quote_id'];
          $requestData['api_source'] = API_SOURCE_CORE;
          $requestData['api_type'] = config('constants/apiType.CUST_DEDUPE');
          $apiReqData['Action'] = 'Pin_Dedupe';
          $apiReqData['SearchBy'] = $getMobileNumber->mobile_number;
          $response = $this->customerFetchApiForPanHistory(
            $apiReqData,
            API_SOURCE_PERSONAL
          );
          $panData['mobile_number'] = $getMobileNumber->mobile_number;
          $panData['lead_id'] =  $request['lead_id'];
          $panData['quote_id'] =  $request['quote_id'];
          $panData['api_source'] = API_SOURCE_CORE;
          $panData['api_source_page'] = $request->header('X-Api-Source-Page');
          $panData['api_type'] = config('constants/apiType.CUST_DEDUPE');
          $panData['url'] = env('CORE_API_URL') . 'Getcustdedupe';
          $panData['api_request_type'] = API_TYPE_RESPONSE;
          $panData['api_data'] = $response;
          $panData['api_status_code'] = API_STATUS_SUCCESS_CODE;
          $coreRepo = new CoreRepository();
          if (isset($response['Table'])) {
            $panData['api_status_code'] = config('journey/http-status.success.code');
            $panData['api_status_message'] = config('journey/http-status.success.message');
            $coreRepo->savePanHistory($panData);
          } elseif ($response == config('journey/http-status.timeout.message')) {
            $panData['api_status_code'] = config('journey/http-status.timeout.code');
            $panData['api_status_message'] = config('journey/http-status.timeout.message');
            $coreRepo->savePanHistory($panData);
            return $response;
          } else {
            $panData['api_status_code'] = config('journey/http-status.oops.code');
            $panData['api_status_message'] = config('journey/http-status.oops.message');
            $coreRepo->savePanHistory($panData);
          }
          $updatedCustomerData =  $this->personalDetailRepo->getPanData($request);
          $reqCustomerData = $updatedCustomerData[0]->api_data;
          unset($updatedCustomerData['created_at']);
          unset($reqCustomerData['Table']);
          if ($updatedCustomerData) {
            return $this->responseJson(
              API_STATUS_SUCCESS,
              API_STATUS_SUCCESS_MESSAGE,
              API_STATUS_SUCCESS_CODE,
              $reqCustomerData
            );
          }
        }
      }
      return $this->responseJson(
        API_STATUS_FAILURE,
        API_STATUS_FAILURE_MESSAGE,
        API_STATUS_FAILURE_CODE,
        []
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  fetchPanDetail " . $throwable);
      return $this->responseJson(
        API_STATUS_ERROR,
        API_STATUS_ERROR_MESSAGE,
        API_STATUS_ERROR_CODE,
        []
      );
    }
  }

  /**
   * Get Personal Details
   *
   */
  public function getPersonalDetails(Request $request)
  {
    try {
      $reqData['quote_id'] = $request->quote_id;
      $appRepo = new ApplicationRepository();
      $appData = $appRepo->getQuoteIdDetails($reqData);
      if (!$appData) {
        return $this->responseJson(
          API_STATUS_FAILURE,
          API_STATUS_FAILURE_MESSAGE,
          API_STATUS_FAILURE_CODE,
          []
        );
      }
      $reqData['lead_id'] = $appData['lead_id'];
      $personalData =  $this->personalDetailRepo->getPersonalData($reqData);
      if (!$personalData) {
        return $this->responseJson(
          API_STATUS_FAILURE,
          API_STATUS_FAILURE_MESSAGE,
          API_STATUS_FAILURE_CODE,
          []
        );
      }
      $personalData['is_paid'] = $appData->is_paid;
      return $this->responseJson(
        API_STATUS_SUCCESS,
        API_STATUS_SUCCESS_MESSAGE,
        API_STATUS_SUCCESS_CODE,
        $personalData
      );
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  getPersonalDetails " . $throwable);
      return $this->responseJson(
        API_STATUS_ERROR,
        API_STATUS_ERROR_MESSAGE,
        API_STATUS_ERROR_CODE,
        []
      );
    }
  }

  public function getCibilScore($request)
  {
    try {
      $score = null;
      $leadRepo = new LeadRepository();
      $appRepo = new ApplicationRepository();
      $coreRepo = new CoreRepository();
      $cibilLog = new CibilLogRepository();
      $leadDetail = $leadRepo->view($request['lead_id']);
      $appData = $appRepo->getQuoteIdDetails($request);
      $reqData['LeadId'] = $request['lead_id'];
      $reqData['Data']['FName'] = $request['full_name'];
      $reqData['Data']['Gender'] = $request['gender'] == 'Male' ? 1 : 2;
      $reqData['Data']['BIRTHDT'] = $request['dob'];
      $reqData['Data']['cbl_PANId'] = $request['pan'];
      $reqData['Data']['cbl_PhoneNo'] = $leadDetail->mobile_number;
      $reqData['Data']['cbl_LnAmt'] = $appData['loan_amount'];

      $viewPanHistroy = $coreRepo->viewCibilHistroy(strtoupper($request['pan']));
      $logData['lead_id'] =  $request['lead_id'];
      $logData['quote_id'] = $request['quote_id'];
      $logData['pan'] = strtoupper($request['pan']);
      $logData['mobile_number'] = $leadDetail->mobile_number;
      $logData['master_product_id'] = $appData['master_product_id'];
      $logData['api_source'] = config('constants/apiSource.CORE');
      $logData['api_source_page'] = $request['api_source_page'] ?? null;
      $logData['api_type'] = config('constants/apiType.FETCH_CIBIL_DATA');
      $logData['api_header'] = $request['header'] ?? null;
      $logData['api_url'] = env('CORE_API_URL') . 'FetchCibilDetails';
      $logData['api_request_type'] = config('constants/apiType.REQUEST');
      $logData['api_request'] = $reqData;
      $logData['api_status_code'] = config('journey/http-status.success.code');
      $logData['api_status_message'] = config('journey/http-status.success.message');
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
          if ($viewPanHistroy->api_response) {
            $response['score'] = json_decode($viewPanHistroy->api_response['result_1'], true);
            $score = $response['score']['FINISHED'][0]['JSON-RESPONSE-OBJECT']['scoreList'][0]['score'] ?? null;
            $logData['cibil_from'] = 'Self';
            $cibilLog->save($logData);
          } else {
            $cibilData = $this->getCibilData($reqData);
            $logData['cibil_from'] = 'Core';
            $logData['api_response'] = $cibilData;
            $response['score'] = json_decode($cibilData['result_1'], true);
            $score = $response['score']['FINISHED'][0]['JSON-RESPONSE-OBJECT']['scoreList'][0]['score'] ?? null;
            $logData['api_request_type'] = config('constants/apiType.RESPONSE');
            $cibilLog->save($logData);
          }
        } else {
          $cibilData = $this->getCibilData($reqData);
          $response['score'] = json_decode($cibilData['result_1'], true);
          $score = $response['score']['FINISHED'][0]['JSON-RESPONSE-OBJECT']['scoreList'][0]['score'] ?? null;
          $logData['cibil_from'] = 'Core';
          $logData['api_response'] = $cibilData;
          $logData['api_request_type'] = config('constants/apiType.RESPONSE');
          $cibilLog->save($logData);
        }
      } else {
        $cibilData = $this->getCibilData($reqData);
        $response['score'] = json_decode($cibilData['result_1'], true);
        $score = $response['score']['FINISHED'][0]['JSON-RESPONSE-OBJECT']['scoreList'][0]['score'] ?? null;
        $logData['cibil_from'] = 'Core';
        $logData['api_response'] = $cibilData;
        $logData['api_request_type'] = config('constants/apiType.RESPONSE');
        $cibilLog->save($logData);
      }
      return $score;
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  getCibilScore " . $throwable);
    }
  }

  public function resumeJourneyCheck(
    Request $request,
    ApplicationRepository $apRepo
  ) {
    try {
      $request['quote_id'] = HjApplication::where('auth_token', $request->bearerToken())->value('quote_id');
      $appData = $apRepo->getAppDataByQuoteId($request['quote_id']);
      $resumeflag = true;
      if (empty($appData)) {
        return $this->responseJson(
          config('journey/http-status.bad-request.status'),
          'Invalid Quote Id',
          config('journey/http-status.bad-request.code'),
          []
        );
      }
      if ($request->pageType && ($request->pageType == 'track_application' || $request->pageType == 'track_application_id')) {
        return $this->getMobileNumberBasedApplications($appData, $request->pageType);
      }
      $apRepo->getAllProducts($appData, 'inactive-application');
      $completedApplication = $apRepo->getAllProducts($appData, 'completed-application');
      if ($completedApplication != 0) {
        $resumeflag = false;
        return $this->responseJson(
          API_STATUS_SUCCESS,
          API_STATUS_SUCCESS_MESSAGE,
          API_STATUS_SUCCESS_CODE,
          ['resume_flag' => $resumeflag]
        );
      }
      $getAllQuotes = $apRepo->getAllProducts($appData, 'active-application');
      if ($getAllQuotes) {
        $data = collect(json_decode($getAllQuotes, true));
        $conditions = function ($item) {
          return collect($item['masterproductstep'])->contains(function ($step) {
            return in_array($step['handle'], ['sanction-letter', 'congratulation']);
          }) && ($item['is_paid'] == 0) && ($item['is_purchased'] == 1);
        };

        $hasCondition = $data->contains($conditions);
        if ($hasCondition) {
          $resumeflag = false;
          return $this->responseJson(
            API_STATUS_SUCCESS,
            API_STATUS_SUCCESS_MESSAGE,
            API_STATUS_SUCCESS_CODE,
            ['resume_flag' => $resumeflag]
          );
        } else {
          return $this->responseJson(
            API_STATUS_SUCCESS,
            API_STATUS_SUCCESS_MESSAGE,
            API_STATUS_SUCCESS_CODE,
            ['product_list' => $getAllQuotes, 'count' => count($getAllQuotes), 'resume_flag' => $resumeflag]
          );
        }
      }
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  resumeJourneyCheck " . $throwable);
    }
  }
  /**
   * create New Application
   *
   */
  public function createApplication(Request $request)
  {
    try {
      // create quote
      $quoteId = $this->createNewQuoteID();
      $applicationRepo = new ApplicationRepository();
      $otpRepo = new OtpLogRepository();
      $leadRepo = new LeadRepository();
      $applicationData = $applicationRepo->getAuthTokenByToken($request->bearerToken());
      if ($applicationData) {
        $leadData = $leadRepo->getLeadDetailsById($applicationData->lead_id);
        $getUpdatedLoanAmount = $otpRepo->getLatestLoanAmount($applicationData);
        // enter data into impression
        $impression['quote_id'] = $quoteId;
        $impression['lead_id'] = $applicationData->lead_id;
        $impression['master_product_step_id'] = 2;
        $impression['master_product_id'] = $applicationData->master_origin_product_id;
        $impressionRepo = new ImpressionRepository();
        $impressionData = $impressionRepo->save($impression);

        // enter data into application
        $application['lead_id'] = $applicationData->lead_id;
        $application['quote_id'] = $quoteId;
        $application['cc_quote_id'] = 'CC' . $this->createQuoteID();
        $application['digital_transaction_no'] = $this->generateRandomString("digitalTransactionID");
        $application['mobile_number'] = $applicationData->mobile_number;
        $application['master_product_id'] = $applicationData->master_origin_product_id;
        $application['master_origin_product_id'] = $applicationData->master_origin_product_id;
        $application['master_product_step_id'] = 2;
        $application['current_impression_id'] = $impressionData['id'];
        $application['previous_impression_id'] = $impressionData['id'];
        $application['cibil_score'] = $applicationData->cibil_score;
        $application['loan_amount'] = $getUpdatedLoanAmount->loan_amount;
        $application['bre_version_date'] = $applicationData->bre_version_date;
        $application['name'] = $leadData ? $leadData->name : $applicationData->name;
        $application['session_auth_token'] = $request->header('X-Session-Token') ? $request->header('X-Session-Token') : $applicationData->session_auth_token;
        $authToken = $this->createWebsiteAuthToken(
          $applicationData['lead_id'],
          $applicationData->mobile_number,
          $quoteId
        );
        $application['auth_token'] = $authToken;
        $applicationRepo->save($application);
        $logPushData = $request;
        $logPushData['cc_stage_handle'] = 'personal-details-applicant';
        $logPushData['cc_sub_stage_handle'] = 'personal-details-applicant-pending';
        $logPushData['lead_id'] = $applicationData->lead_id;
        $logPushData['quote_id'] = $quoteId;
        $this->pushDataFieldTrackingLog($logPushData);
        return $this->responseJson(
          API_STATUS_SUCCESS,
          API_STATUS_SUCCESS_MESSAGE,
          API_STATUS_SUCCESS_CODE,
          $authToken
        );
      }
      return $this->responseJson(
        API_STATUS_FAILURE,
        API_STATUS_FAILURE_MESSAGE,
        API_STATUS_FAILURE_CODE
      );
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  createApplication " . $throwable);
    }
  }
  /**
   * resume journey
   *
   */
  public function resumeApplication(Request $request)
  {
    try {
      $applicationRepo = new ApplicationRepository();
      $applicationData = $applicationRepo->getAuthTokenByToken($request->token);
      if ($applicationData && !empty($request->token)) {
        $authToken = $this->createWebsiteAuthToken(
          $applicationData->lead_id,
          $applicationData->mobile_number,
          $applicationData->quote_id
        );
        $application['lead_id'] = $applicationData->lead_id;
        $application['quote_id'] = $applicationData->quote_id;
        $application['auth_token'] = $authToken;
        $applicationRepo->save($application);

        $response['auth_token'] = $authToken;
        $response['step_handle'] = $applicationData->masterproductstep[0]->handle ?? null;

        // bre version date check
        $applicationUpatedDate = $applicationData->updated_at;
        $breVersionUpdatedDate = $applicationData->bre_version_date;
        if ($breVersionUpdatedDate > $applicationUpatedDate) {
          // enter data into impression
          $impression['quote_id'] = $applicationData->quote_id;
          $impression['lead_id'] = $applicationData->lead_id;
          $impression['master_product_step_id'] = 2;
          $impression['master_product_id'] = $applicationData->master_product_id;
          $impressionRepo = new ImpressionRepository();
          $impressionRepo->save($impression);

          $application['lead_id'] = $applicationData->lead_id;
          $application['quote_id'] = $applicationData->quote_id;
          $application['master_product_step_id'] = 2;
          $applicationRepo->save($application);

          $response['step_handle'] = config('constants/productStepHandle.personal-details') ?? null;
        }

        $ccStageId = $applicationRepo->getCCStageId($applicationData->masterproductstep[0]->id);
        $logPushData = $request;
        if ($ccStageId) {
          $ccSubStageId = $applicationRepo->getCCSubStageId($ccStageId->master_cc_stage_id);
          $ccSubStageHandle =  $ccSubStageId['ccSubStage']['handle'] ?? null;
          $ccStageHandle = $ccSubStageId['ccStage']['handle'] ?? null;
          $logPushData['cc_stage_handle'] = $ccStageHandle;
          $logPushData['cc_sub_stage_handle'] = $ccSubStageHandle;
        }
        $this->pushDataFieldTrackingLog($logPushData);
        return $this->responseJson(
          API_STATUS_SUCCESS,
          API_STATUS_SUCCESS_MESSAGE,
          API_STATUS_SUCCESS_CODE,
          $response
        );
      }
      return $this->responseJson(
        API_STATUS_FAILURE,
        API_STATUS_FAILURE_MESSAGE,
        API_STATUS_FAILURE_CODE
      );
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  resumeApplication " . $throwable);
    }
  }
  /**
   * get products based on mobilenumber
   *
   */
  public function getMobileNumberBasedApplications($appData, $pageType)
  {
    try {
      $apRepo = new ApplicationRepository();
      $apRepo->getProductBasedMobile($appData, 'inactive-application', $pageType);
      $completedApplication = $apRepo->getProductBasedMobile($appData, 'completed-application', $pageType);
      if ($completedApplication != 0) {
        $resumeflag = false;
        return $this->responseJson(
          API_STATUS_SUCCESS,
          API_STATUS_SUCCESS_MESSAGE,
          API_STATUS_SUCCESS_CODE,
          ['resume_flag' => $resumeflag]
        );
      }
      $getAllQuotes = $apRepo->getProductBasedMobile($appData, 'active-application', $pageType);
      if ($getAllQuotes) {
        $data = collect(json_decode($getAllQuotes, true));
        $conditions = function ($item) {
          return collect($item['masterproductstep'])->contains(function ($step) {
            return in_array($step['handle'], ['sanction-letter', 'congratulation']);
          }) && ($item['is_paid'] == 0) && ($item['is_purchased'] == 1);
        };

        $hasCondition = $data->contains($conditions);
        if ($hasCondition) {
          $resumeflag = false;
          return $this->responseJson(
            API_STATUS_SUCCESS,
            API_STATUS_SUCCESS_MESSAGE,
            API_STATUS_SUCCESS_CODE,
            ['resume_flag' => $resumeflag]
          );
        } else {
          $resumeflag = true;
          return $this->responseJson(
            API_STATUS_SUCCESS,
            API_STATUS_SUCCESS_MESSAGE,
            API_STATUS_SUCCESS_CODE,
            ['product_list' => $getAllQuotes, 'count' => count($getAllQuotes), 'resume_flag' => $resumeflag]
          );
        }
      }
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  getMobileNumberBasedApplications " . $throwable);
    }
  }
  /**
   * Fetch address details from karza
   *
   */
  public function fetchAddress(
    Request $request
  ) {
    try {
      $this->dispatch(new KarzaPanQueue($request->toArray()));
      return $this->responseJson(
        API_STATUS_SUCCESS,
        API_STATUS_SUCCESS_MESSAGE,
        API_STATUS_SUCCESS_CODE,
        []
      );
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  fetchAddress " . $throwable);
    }
  }
  /**
   * Karza Api for lead pan number
   */
  public function karzaApicall(
    Request $request,
    ApplicationRepository $applicationRepo,
    PersonalDetailRepository $personalDtRepo,
    AddressRepository $addressRepo
  ) {
    try {
      $masterProductId = $applicationRepo->getMasterProductId($request['quote_id']);
      $reqData['pan'] = (string)strtoupper($request['pan']);
      $reqData['lead_id'] = $request['lead_id'];
      $reqData['quote_id'] = (string)$request['quote_id'];
      if (array_key_exists('pan', $reqData)) {
        $leadRepo = new LeadRepository();
        $panDt['quote_id'] = $request['quote_id'];
        $panDt['pan']  = strtoupper($request['pan']);
        if ($request['page'] == 'co-applicant-personal-detail') {
          $coApplicantData = $leadRepo->getCoApplicantData($request->all());
          if ($coApplicantData && $coApplicantData->co_applicant_id) {
            $panDt['lead_id'] = $coApplicantData->co_applicant_id;
          }
        } else {
          $panDt['lead_id']  = $request['lead_id'];
        }
        $panDupeCheck = $personalDtRepo->panDuplicateCheck($panDt);
        if ($panDupeCheck != 0) {
          return $this->responseJson(
            config('journey/http-status.oops.status'),
            'Pan Number Already Exist',
            config('journey/http-status.oops.code'),
            []
          );
        } else {
          $karzaLog['lead_id'] = $request['lead_id'];
          $karzaLog['quote_id'] = $request['quote_id'];
          $karzaLog['master_product_id'] = $masterProductId;
          $karzaLog['api_source'] = API_SOURCE_CORE;
          $karzaLog['api_source_page'] = API_SOURCE_PERSONAL;
          $karzaLog['api_type'] = API_TYPE_KARZA;
          $karzaLog['api_header'] = $request['header'] ?? null;
          $karzaLog['api_url'] = env('CORE_API_URL') . 'karzaPan';
          $karzaLog['api_request_type'] = API_TYPE_RESPONSE;
          $viewKarzaHistroy = $addressRepo->viewKarzaHistroy($reqData);
          if ($viewKarzaHistroy) {
            $currentDate = Carbon::now()->format('Y-m-d');
            $carbonDate = Carbon::createFromTimestampMs($viewKarzaHistroy->created_at);
            $createdDate = $carbonDate->format('Y-m-d');
            $to = Carbon::parse($currentDate);
            $from = Carbon::parse($createdDate);
            $days = $to->diffInDays($from);
            if ($days < 30) {
              if (isset($viewKarzaHistroy->api_data['pandob'])) {
                $checkDobFlag = $this->checkDobForKarzaDisplay($request->dob, $viewKarzaHistroy->api_data['pandob']);
                if ($checkDobFlag) {
                  $reqKarzaData = $viewKarzaHistroy->api_data;
                  $reqKarzaData = $this->removeColumnsFromPan($reqKarzaData);
                  return $this->responseJson(
                    API_STATUS_SUCCESS,
                    API_STATUS_SUCCESS_MESSAGE,
                    API_STATUS_SUCCESS_CODE,
                    $reqKarzaData
                  );
                }
              }
              return $this->responseJson(
                API_STATUS_INVALID,
                API_STATUS_INVALID_MESSAGE,
                API_STATUS_INVALID_CODE,
                []
              );
            } else {
              $panReq['PanNo'] = strtoupper($request['pan']);
              $address = $this->fetchAddressFromKarza($panReq);
              if ($address && $address ==  API_STATUS_TIMEOUT_MESSAGE) {
                return $this->responseJson(
                  API_STATUS_TIMEOUT,
                  API_STATUS_TIMEOUT_MESSAGE,
                  API_STATUS_TIMEOUT_CODE
                );
              } else {
                $decodedAddress  =  json_decode($address, true);
                $karzaLog['pan'] = $request['pan'];
                $karzaLog['api_data'] = $decodedAddress;
                $karzaLog['api_status_code'] = API_STATUS_ERROR_CODE;
                $karzaLog['api_status_message'] = API_STATUS_ERROR_MESSAGE;
                $addressRepo = new AddressRepository();
                $addressRepo->saveKarzaHistroy($karzaLog);
                if (isset($decodedAddress['pandob'])) {
                  $checkDobFlag = $this->checkDobForKarzaDisplay($request->dob, $decodedAddress['pandob']);
                  $karzaLog['api_status_code'] = API_STATUS_SUCCESS_CODE;
                  $karzaLog['api_status_message'] = API_STATUS_SUCCESS_MESSAGE;
                  if ($checkDobFlag) {
                    $decodedAddress = $this->removeColumnsFromPan($decodedAddress);
                    return $this->responseJson(
                      API_STATUS_SUCCESS,
                      API_STATUS_SUCCESS_MESSAGE,
                      API_STATUS_SUCCESS_CODE,
                      $decodedAddress
                    );
                  }
                }
              }
              return $this->responseJson(
                API_STATUS_INVALID,
                API_STATUS_INVALID_MESSAGE,
                API_STATUS_INVALID_CODE,
                []
              );
            }
          } else {
            $panReq['PanNo'] = strtoupper($request['pan']);
            $address = $this->fetchAddressFromKarza($panReq);
            if ($address && $address == API_STATUS_TIMEOUT_MESSAGE) {
              return $this->responseJson(
                API_STATUS_TIMEOUT,
                API_STATUS_TIMEOUT_MESSAGE,
                API_STATUS_TIMEOUT_CODE
              );
            } else {
              $decodedAddress  =  json_decode($address, true);
              if ($decodedAddress != null && array_key_exists('panNo', ($decodedAddress))) {
                $karzaLog['pan'] = $decodedAddress['panNo'];
                $karzaLog['api_data'] = $decodedAddress;
                $karzaLog['api_status_code'] = API_STATUS_SUCCESS_CODE;
                $karzaLog['api_status_message'] = API_STATUS_SUCCESS_MESSAGE;
                $addressRepo = new AddressRepository();
                $addressRepo->saveKarzaHistroy($karzaLog);
                if (isset($decodedAddress['pandob'])) {
                  $checkDobFlag = $this->checkDobForKarzaDisplay($request->dob, $decodedAddress['pandob']);
                  if ($checkDobFlag) {
                    $decodedAddress = $this->removeColumnsFromPan($decodedAddress);
                    return $this->responseJson(
                      API_STATUS_SUCCESS,
                      API_STATUS_SUCCESS_MESSAGE,
                      API_STATUS_SUCCESS_CODE,
                      $decodedAddress
                    );
                  } else {
                    return $this->responseJson(
                      API_STATUS_INVALID,
                      API_STATUS_INVALID_MESSAGE,
                      API_STATUS_INVALID_CODE,
                      []
                    );
                  }
                } else {
                  return $this->responseJson(
                    API_STATUS_INVALID,
                    API_STATUS_INVALID_MESSAGE,
                    API_STATUS_INVALID_CODE,
                    []
                  );
                }
              } else {
                $karzaLog['pan'] = $request['pan'];
                $karzaLog['api_data'] = $decodedAddress;
                $karzaLog['api_status_code'] = 402;
                $karzaLog['api_status_message'] = API_STATUS_ERROR_MESSAGE;
                $addressRepo = new AddressRepository();
                $addressRepo->saveKarzaHistroy($karzaLog);
                return $this->responseJson(
                  API_STATUS_INVALID,
                  API_STATUS_INVALID_MESSAGE,
                  API_STATUS_INVALID_CODE,
                  []
                );
              }
            }
          }
        }
      }
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  karzaApicall " . $throwable);
      return $this->responseJson(
        API_STATUS_ERROR,
        API_STATUS_ERROR_MESSAGE,
        API_STATUS_ERROR_CODE,
        []
      );
    }
  }

  public function removeColumnsFromPan($reqRemoveData)
  {
    try {
      unset($reqRemoveData['panNo']);
      unset($reqRemoveData['panAadhaarLinked']);
      unset($reqRemoveData['firstName']);
      unset($reqRemoveData['pandob']);
      unset($reqRemoveData['lastName']);
      unset($reqRemoveData['lastName']);
      unset($reqRemoveData['buildingName']);
      unset($reqRemoveData['locality']);
      unset($reqRemoveData['streetName']);
      unset($reqRemoveData['pinCode']);
      unset($reqRemoveData['city']);
      unset($reqRemoveData['state']);
      unset($reqRemoveData['caseId']);
      unset($reqRemoveData['source']);
      return $reqRemoveData;
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  removeColumnsFromPan " . $throwable);
      return $this->responseJson(
        API_STATUS_ERROR,
        API_STATUS_ERROR_MESSAGE,
        API_STATUS_ERROR_CODE,
        []
      );
    }
  }

  public function list(Request $request)
  {
    try {
      $personalDetailList = $this->personalDetailRepo->list($request);
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $personalDetailList
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService list : %s", $throwable->__toString());
    }
  }
  public function exportPersonalDetails(Request $request)
  {
    try {
      $repository = new PersonalDetailRepository();
      $data['methodName'] = 'list';
      $data['fileName'] = 'Personal-Detail-Report-';
      $data['moduleName'] = 'PersonalDetail';
      return $this->exportData($request, $repository, $data);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService exportPersonalDetails : %s", $throwable->__toString());
    }
  }

  public function checkDobForKarzaDisplay($reqDob, $karzaDob)
  {
    try {
      $givenDob = Carbon::createFromFormat('Y-m-d', $reqDob);
      $karzaFetchDob = Carbon::createFromFormat('Y-m-d', $karzaDob);
      if ($givenDob == $karzaFetchDob) {
        return true;
      }
      return false;
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailService -  checkDobForKarzaDisplay " . $throwable);
      return $this->responseJson(
        API_STATUS_ERROR,
        API_STATUS_ERROR_MESSAGE,
        API_STATUS_ERROR_CODE,
        []
      );
    }
  }
}
