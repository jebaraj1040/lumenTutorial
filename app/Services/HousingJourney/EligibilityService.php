<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\BreLogRepository;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use App\Utils\CommonTrait;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;

class EligibilityService extends Service
{
     use CrmTrait;
     use CommonTrait;
     /**
      * Save Lead Detail
      *
      */
     public function save(Request $request, EligibilityRepository $eligibilityRepo, LeadRepository $leadRepository, ImpressionRepository $impressionRepo, ApplicationRepository $applicationRepo)
     {
          try {
               $rules = [
                    "loan_amount" => "required | numeric",
                    "tenure" => "required | numeric",
               ];
               $validator = Validator::make($request->all(), $rules);
               if ($validator->fails()) {
                    return $validator->errors();
               }
               $request['type'] = 'BRE1';
               $eligibilityId = $eligibilityRepo->save($request->all());
               if ($eligibilityId) {
                    if ($request->is_co_applicant) {
                         $request['next_stage'] = config('constants/productStepHandle.co-applicant-personal-details');
                         $request['master_product_step_id'] = $this->getCurrentStepId($request);
                         $logPushData = $request;
                         $logPushData['cc_stage_handle'] = 'personal-details-co-applicant';
                         $logPushData['cc_sub_stage_handle'] = 'personal-details-co-applicant-pending';
                         $this->pushDataFieldTrackingLog($logPushData);
                    } else {
                         $request['next_stage'] = config('constants/productStepHandle.address-details');
                         $request['master_product_step_id'] = $this->getCurrentStepId($request);
                         $logPushData = $request;
                         $logPushData['cc_stage_handle'] = 'address-details';
                         $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                         $this->pushDataFieldTrackingLog($logPushData);
                    }
                    // save into Impression Table
                    $impressionSave = $impressionRepo->save($request->all());
                    if ($impressionSave->id) {
                         $previousImpression = $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
                         $request['previous_impression_id'] = $previousImpression->id ?? $impressionSave->id;
                         $request['current_impression_id'] = $impressionSave->id;
                         // save into Application Table 
                         $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
                         $reqData['bre1_loan_amount'] = $request->bre1_loan_amount;
                         $reqData['bre1_updated_loan_amount'] = $request->bre1_updated_loan_amount;
                         $reqData['lead_id'] = $request->lead_id;
                         $reqData['quote_id'] = $request->quote_id;
                         $reqData['master_product_step_id'] = $request['master_product_step_id'];
                         $applicationRepo->save($reqData);
                    }
                    if ($request->lead_detail) {
                         foreach ($request->lead_detail as $key => $lead) {
                              $leadDetails['name'] = $lead['name'];
                              $leadDetails['mobile_number'] = $lead['mobile_number'];
                              $leadSave = $leadRepository->save($leadDetails);
                              if ($leadSave) {
                                   $coApplicantDetails['lead_id'] = $request->lead_id;
                                   $coApplicantDetails['co_applicant_id'] = $leadSave->id;
                                   $coApplicantDetails['quote_id'] = $request->quote_id;
                                   $coApplicantSave = $leadRepository->saveCoApplicant($coApplicantDetails);
                              }
                         }
                    }
                    return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), []);
               } else {
                    return $this->responseJson(config('journey/http-status.error.status'), config('journey/http-status.error.message'), config('journey/http-status.error.code'), []);
               }
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("EligibilityService -  save " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     /**
      * Retrieve Eligibility Detail
      *
      */
     public function view(Request $request, EligibilityRepository $eligibilityRepo, ApplicationRepository $apRepo)
     {
          try {
               $getLeadId = $apRepo->getAppDataByQuoteId($request['quote_id']);
               if ($getLeadId) {
                    $reqData['lead_id'] = $getLeadId->lead_id;
                    $reqData['quote_id'] = $getLeadId->quote_id;
                    $eligibilityDetail['eligibility_data'] = $eligibilityRepo->getBre1Eligibile($reqData);
                    $eligibilityDetail['app_data'] = $apRepo->getEligibilityAppData($reqData['quote_id']);
                    unset($eligibilityDetail['app_data']['bre2_loan_amount']);
                    unset($eligibilityDetail['app_data']['is_paid']);
                    unset($eligibilityDetail['app_data']['is_purchased']);
                    unset($eligibilityDetail['app_data']['master_product_id']);
                    unset($eligibilityDetail['app_data']['master_product_step_id']);
                    unset($eligibilityDetail['app_data']['loan_amount']);
                    return $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         $eligibilityDetail
                    );
               } else {
                    return $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         []
                    );
               }
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("EligibilityService -  view " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     /**
      * get BRE one Data
      *
      */
     public function getBreOneData(Request $request, BreLogRepository $breLogRepo)
     {
          try {
               if ($request->header('X-Api-Source-Page') == 'CO_APPLICANT_PAGE') {
                    $breData = $breLogRepo->fetchBreCoApplicantOneData($request->all());
               } else {
                    $breData = $breLogRepo->fetchBreOneData($request->all());
               }
               if ($breData  && $breData != null && $breData != '' && isset($breData->api_data)) {
                    $breDataResponse = json_decode($breData->api_data, true);
                    $loanArray = array();
                    $finalBreData = array();
                    if ($breDataResponse) {
                         foreach ($breDataResponse['Table'] as $bre) {
                              if ($bre['LnAmount'] != 0 && $bre['LnAmount'] > 750000) {
                                   $loanArray[$bre['Tenure']] = $bre['LnAmount'];
                                   $breAPiData['LnAmount'] = $bre['LnAmount'];
                                   $breAPiData['Tenure'] = $bre['Tenure'];
                                   $finalBreData[] =  $breAPiData;
                              }
                         }
                    }
                    $breResponse['actual_data'] = $finalBreData;
                    if (count($breResponse['actual_data']) == 0) {
                         $requestData['quote_id'] = $request->quote_id;
                         $requestData['lead_id'] = $request->lead_id;
                         $requestData['bre1_loan_amount'] = 0;
                         $requestData['bre1_updated_loan_amount'] = 0;
                         $apRepo = new ApplicationRepository();
                         $apRepo->save($requestData);
                    }
                    if (
                         $breDataResponse && isset($breDataResponse['Table1'])
                         && count($breDataResponse['Table1']) == 1 || count($breResponse['actual_data']) == 0
                    ) {
                         $isDeviation = $breDataResponse['Table1'][0]['IsDev'] ?? null;
                         if ($isDeviation == "Y") {
                              $stage['next_stage'] = config('constants/productStepHandle.address-details');
                              $reqData['master_product_step_id'] = $this->getCurrentStepId($stage);
                              $impressionRepo = new ImpressionRepository();
                              $apRepo = new ApplicationRepository();
                              $requestData['quote_id'] = $request->quote_id;
                              $appData = $apRepo->getQuoteIdDetails($requestData);
                              $reqData['quote_id'] =  $request->quote_id;
                              $reqData['lead_id'] = $request->lead_id;
                              $reqData['master_product_id'] = $appData['master_product_id'];
                              $apRepo->save($reqData);
                              $impressionRepo->save($reqData);
                         }
                    }
                    $breResponse['converted_data'] =  $loanArray;
                    $breResponse['is_deviation'] = $breDataResponse['Table1'] ??  null;
                    return $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         $breResponse
                    );
               } else {
                    return $this->responseJson(
                         config('journey/http-status.failure.status'),
                         config('journey/http-status.failure.message'),
                         config('journey/http-status.failure.code'),
                         []
                    );
               }
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("EligibilityService -  getBreOneData " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     /**
      * get BRE two Data
      *
      */
     public function getBreTwoData(
          Request $request,
          BreLogRepository $breLogRepo,
          PropertyLoanDetailRepository $propRepo,
          PersonalDetailRepository $perRepo
     ) {
          try {
               $eligibilityRepo = new EligibilityRepository;
               $apRepo = new ApplicationRepository();
               if (!isset($request['lead_id'])) {
                    $appData = $apRepo->getAppDataByQuoteId($request['quote_id']);
                    $request['lead_id'] = $appData->lead_id;
               }
               $breData = $breLogRepo->fetchBreTwoData($request->all());
               if ($breData && $breData != null && $breData != '' && isset($breData->api_data)) {
                    $breResponseData = json_decode($breData->api_data, true);
                    $finalBreData = array();
                    if ($breResponseData) {
                         foreach ($breResponseData['Table'] as $bre) {
                              if ($bre['LnAmount'] != 0 && $bre['LnAmount'] > config('journey/api.minimumLoanAmount')) {
                                   $loanArray[$bre['Tenure']] = $bre['LnAmount'];
                                   $finalBreData[] = $bre;
                              }
                         }
                    }
                    $breResponse['actual_data'] = $finalBreData;
                    if (count($breResponse['actual_data']) == 0) {
                         $requestData['quote_id'] = $request->quote_id;
                         $requestData['lead_id'] = $request->lead_id;
                         $requestData['bre2_loan_amount'] = 0;
                         $apRepo->save($requestData);
                    }
                    $breResponse['lap_data'] = $propRepo->getPropertyExistingLoanData($request->all());
                    $breResponse['applicant_data'] = $perRepo->getApplicantName($request->all());
                    $breResponse['employment_type'] = $perRepo->getEmplymentType($request->all());
                    $breResponse['eligibility_data'] = $eligibilityRepo->getLoanAmountTenure($request['quote_id']);
                    $breResponse['is_deviation'] = $breResponseData['Table1'] ?? null;
                    $breResponse['application_data'] = $apRepo->getBREAmount($request->all());
                    unset($breResponse['applicant_data']['quote_id']);
                    unset($breResponse['eligibility_data']['quote_id']);
                    unset($breResponse['application_data']['quote_id']);
                    return $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         $breResponse
                    );
               } else {
                    return $this->responseJson(
                         config('journey/http-status.failure.status'),
                         config('journey/http-status.failure.message'),
                         config('journey/http-status.failure.code'),
                         []
                    );
               }
          } catch (Throwable  | HttpClientException $throwable) {
               Log::info("EligibilityService -  getBreTwoData " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
     public function list(Request $request, EligibilityRepository $eligibilityRepo,)
     {
          try {
               $eligibilityList = $eligibilityRepo->list($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $eligibilityList
               );
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("AddressService list : %s", $throwable->__toString());
          }
     }
     public function exportEligibility(Request $request)
     {
          try {
               $repository = new EligibilityRepository();
               $data['methodName'] = 'list';
               $data['fileName'] = 'Eligibility-Report-';
               $data['moduleName'] = 'Eligibility';
               return $this->exportData($request, $repository, $data);
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("EligibilityService exportEligibility : %s", $throwable->__toString());
          }
     }
}
