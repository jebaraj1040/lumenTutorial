<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use App\Repositories\HousingJourney\PaymentTransactionRepository;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\BreLogRepository;
use App\Services\Service;
use Throwable;
use Exception;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Predis\ClientException;
use Illuminate\Support\Facades\Log;
use App\Utils\CoreTrait;
use App\Utils\CommonTrait;
use App\Repositories\HousingJourney\CoreRepository;
use Carbon\Carbon;
use App\Utils\CrmTrait;

class LeadService extends Service
{
     use CrmTrait;
     use CoreTrait;
     use CommonTrait;
     /**
      * Create a new Service instance.
      *
      */
     private $leadRepo;
     public function __construct(
          LeadRepository $leadRepo,
     ) {
          $this->leadRepo = $leadRepo;
     }
     /**
      * Save Lead Detail
      *
      */
     public function save(Request $request)
     {
          try {
               $rules = [
                    "name" => "required",
                    "mobile_number" => "required|numeric|digits:10",
                    "pincode" => "required|numeric|digits:6",
               ];
               $validator = Validator::make($request->all(), $rules);
               if ($validator->fails()) {
                    return $validator->errors();
               }
               // save into lead Table
               $request['is_being_assisted'] = $request['is_assited'];
               $leadSave = $this->leadRepo->save($request->all());
               if ($leadSave->id) {
                    return $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         []
                    );
               } else {
                    return $this->responseJson(
                         config('journey/http-status.error.status'),
                         config('journey/http-status.error.message'),
                         config('journey/http-status.error.code'),
                         []
                    );
               }
          } catch (Throwable  | HttpClientException $throwable) {
               Log::info(
                    "Service : LeadService , Method : save : %s",
                    $throwable->__toString()
               );
          }
     }
     /**
      * Retrieve Lead Detail
      *
      */
     public function view(Request $request)
     {
          try {
               $leadDetail = $this->leadRepo->view($request['lead_id']);
               return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $leadDetail
               );
          } catch (Throwable | HttpClientException $throwable) {
               Log::info(
                    "Service : LeadService , Method : edit : %s",
                    $throwable->__toString()
               );
          }
     }
     public function detail(Request $request)
     {
          try {
               $leadDetail = $this->leadRepo->detail($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $leadDetail
               );
          } catch (Throwable | Exception | ClientException $throwable) {
               Log::info("LeadService -  detail " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
     /**
      * Relationship master Data
      *
      */
     public function getRelationshipMasterData(LeadRepository $apDetRepo)
     {
          try {
               $relationshipMaster = $apDetRepo->getMasterRelationshipData();
               return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $relationshipMaster
               );
          } catch (Throwable | HttpClientException $throwable) {
               Log::info(
                    "Service : LeadService , Method : getRelationshipMasterData  : %s",
                    $throwable->__toString()
               );
          }
     }
     /**
      * list appliccation  Data
      *
      */
     public function list(Request $request)
     {
          try {
               $leadList = $this->leadRepo->list($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $leadList
               );
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("LeadService list : %s", $throwable->__toString());
          }
     }
     /**
      * save coApplicant Detail
      *
      */
     public function coApplicantSave(
          Request $request,
          LeadRepository $apDtRepo,
          PersonalDetailRepository $perDtRepo,
          ImpressionRepository $impRepo,
          ApplicationRepository $apRepo,
          EmploymentDetailRepository $empDtRepo,
          EligibilityRepository $eligibleRepo
     ) {
          try {
               if ($request->stage == 'personal-detail') {
                    // validation
                    $rules = [
                         "full_name" => "required | regex:/^[a-zA-Z]+(?: [a-zA-Z]+)*(?:(?!  )[a-zA-Z ])*$/",
                         "dob" => "required | regex:/^\d{2}-\d{2}-\d{4}$/",
                         "pan" => "required | regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/",
                         "gender" => "required | regex:/^[a-zA-Z\s]+$/",
                         "email" => "nullable|email",
                         "relationship_id" => "required | numeric"
                    ];
                    $validator = Validator::make($request->all(), $rules);
                    if ($validator->fails()) {
                         return $validator->errors();
                    }
                    // co-applicant personal detail
                    $leadId = $request['lead_id'];
                    $getLead = $apDtRepo->view($request['lead_id']);
                    $request['mobile_number'] = $getLead->mobile_number;
                    $request['name'] = $request['full_name'];
                    $request['pincode_id'] = $getLead->pincode_id;
                    $request['is_being_assisted'] = $getLead->is_being_assisted;
                    $request['partner_code'] = $getLead->partner_code;
                    $request['home_extension'] = $getLead->home_extension;
                    $request['sub_partner_code'] = $getLead->sub_partner_code;
                    $request['is_agreed'] = $getLead->is_agreed;
                    // save into lead table
                    $leadSave = $apDtRepo->save($request->all());
                    if ($leadSave) {
                         $request['lead_id'] = $leadSave->id;
                         // save relationship field to mapping applicant relation table
                         $appData =  $apRepo->getQuoteIdDetails($request->all());
                         $coApplicantRelation['lead_id'] = $request['lead_id'];
                         $coApplicantRelation['relationship_id'] = $request->relationship_id;
                         $coApplicantRelation['quote_id'] = $appData['quote_id'];
                         $coApplicantRelation['application_id'] = $appData['id'];
                         $apDtRepo->relationshipSave($coApplicantRelation);
                         $request['lead_id'] = $leadSave->id;
                         // save other details to personal detail table
                         $leadDob = Carbon::createFromFormat('d-m-Y', $request['dob'])->format('Y-m-d');
                         $request['dob'] = $leadDob;
                         $perDtRepo->save($request->all());
                         $coAppSave['lead_id'] = $leadId;
                         $coAppSave['co_applicant_id'] = $leadSave->id;
                         $coAppSave['quote_id'] = $request['quote_id'];
                         // save applicant co applicant mapping
                         $apDtRepo->saveCoApplicant($coAppSave);
                         // save into Impression Table
                         $request['lead_id'] = $leadId;
                         $request['next_stage'] = config('constants/productStepHandle.co-applicant-employment-details');
                         $logPushData = $request;
                         $logPushData['cc_stage_handle'] = 'co-applicant-employment-details';
                         $logPushData['cc_sub_stage_handle'] = 'co-applicant-employment-details-pending';
                         $this->pushDataFieldTrackingLog($logPushData);
                         $request['master_product_step_id'] = $this->getCurrentStepId($request);
                         $impressionSave = $impRepo->save($request->all());
                         if ($impressionSave->id) {
                              $previousImpressionId = $impRepo->getPreviousImpressionId($impressionSave->id, $request);
                              $request['previous_impression_id'] = $previousImpressionId->id ?? $impressionSave->id;
                              $request['current_impression_id'] = $impressionSave->id;
                              // save into Application Table
                              $reqData['lead_id'] = $leadId;
                              $requestData['lead_id'] = $request->lead_id;
                              $requestData['quote_id'] = $request->quote_id;
                              $requestData['master_product_step_id'] =  $this->getCurrentStepId($request);
                              $apRepo->save($requestData);
                         }
                         return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              []
                         );
                    } else {
                         return $this->responseJson(
                              config('journey/http-status.error.status'),
                              config('journey/http-status.error.message'),
                              config('journey/http-status.error.code'),
                              []
                         );
                    }
               } elseif ($request->stage == 'employment-detail') {
                    $employmentType = $empDtRepo->getEmploymentTypeHandle($request['employment_type']);
                    if ($employmentType == 'salaried') {
                         $rules = [
                              "employment_type" => "required",
                              "company_name" => ['required', 'regex:/^[A-Za-z0-9&\'\|\:\+\-\.\+\(\)\, ]+$/'],
                              "current_experience" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "total_experience" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "net_monthly_salary" => "required | numeric",
                              "is_income_proof_document_available" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "mode_of_salary" => "required | regex:/^[a-zA-Z0-9]+$/"
                         ];
                    } elseif ($employmentType == 'self-employed-non-professional') {
                         $rules = [
                              "employment_type" => "required",
                              "company_name" => ['required', 'regex:/^[A-Za-z0-9&\'\|\:\+\-\.\+\(\)\, ]+$/'],
                              "business_vintage" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "net_monthly_sales" => "required | numeric",
                              "net_monthly_profit" => "required | numeric",
                              "is_income_proof_document_available" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "constitution_type" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "industry_segment" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "industry_type" => "required | regex:/^[a-zA-Z0-9]+$/"
                         ];
                    } elseif ($employmentType == 'self-employed-professional') {
                         $rules = [
                              "employment_type" => "required",
                              "professional_type" => "required | regex:/^[a-zA-Z0-9]+$/",
                              "gross_receipt" => "required | numeric",
                              "is_income_proof_document_available" => "required | regex:/^[a-zA-Z0-9]+$/",
                         ];
                    }
                    $validator = Validator::make($request->all(), $rules);
                    if ($validator->fails()) {
                         return $validator->errors();
                    }
                    // co-applicant employment detail
                    $leadId = $request['lead_id'];
                    $coApplicantId = $apDtRepo->getCoApplicantId($request);
                    $request['lead_id'] = $coApplicantId;
                    // save co-applicant into employmentDetail Table
                    $request['employment_type_id'] = $request['employment_type'];
                    $request['salary_mode_id'] = $request['mode_of_salary'];
                    $request['constitution_type_id'] = $request['constitution_type'];
                    $request['industry_type_id'] = $request['industry_type'];
                    $request['industry_segment_id'] = $request['industry_segment'];
                    $request['professional_type_id'] = $request['professional_type'];
                    $coApplicantEmpDtSave = $empDtRepo->employmentDetailsSave($request->all());
                    if ($coApplicantEmpDtSave) {
                         // save into Impression Table
                         $request['lead_id'] = $leadId;
                         $request['next_stage'] = config('constants/productStepHandle.co-applicant-eligibility');
                         $request['master_product_step_id'] = $this->getCurrentStepId($request);
                         $impressionSave = $impRepo->save($request->all());
                         if ($impressionSave->id) {
                              $previousImpressionId = $impRepo->getPreviousImpressionId($impressionSave->id, $request);
                              $request['previous_impression_id'] = $previousImpressionId->id ?? $impressionSave->id;
                              $request['current_impression_id'] = $impressionSave->id;
                              // save into Application Table
                              $request['is_bre_execute'] = true;
                              $apRepo->save($request->all());
                              $breFlag = true;
                              if ($request['is_income_proof_document_available'] != 0 && $request['salary_mode_id'] != 2 && $request['total_experience'] != 1) {
                                   $request['bre_type'] = config('constants/apiType.BRE_LEVEL_ONE');
                                   $request['stage'] = config('constants/apiSourcePage.CO_APPLICANT_PAGE');
                                   $resData = $this->prepareBREData($request);
                                   if (
                                        $resData && $resData != null
                                        && $resData != '' && gettype($resData) == 'string'
                                   ) {
                                        $breData = json_decode($resData, true);
                                        if (isset($breData['Table1']) && $breData['Table1'][0]['IsDev'] == 'N') {
                                             $breFlag = true;
                                             $lnAmounts = array_column($breData['Table'], 'LnAmount');
                                             $allLnAmountsZero = array_sum($lnAmounts) == 0;
                                             $logPushData = $request;
                                             $logPushData['cc_stage_handle'] = 'eligibility-co-applicant';
                                             $logPushData['cc_sub_stage_handle']
                                                  = 'eligibility-co-applicant-bre-success';
                                             $this->pushDataFieldTrackingLog($logPushData);
                                             $request['is_bre_execute'] = true;
                                             if ($allLnAmountsZero) {
                                                  $breFlag = false;
                                                  $reData['lead_id'] = $coApplicantId;
                                                  $reData['quote_id'] = $request->quote_id;
                                                  $reData['type'] = 'BRE1';
                                                  $existEligibile = $eligibleRepo->view($reData);
                                                  if (
                                                       $existEligibile['eligibility_data'] &&
                                                       $existEligibile['eligibility_data']['type'] == 'BRE1'
                                                  ) {
                                                       $eligibleRepo->removeExistData($reData);
                                                  }
                                                  $request['next_stage'] =
                                                       config('constants/productStepHandle.address-details');
                                                  $request['master_product_step_id'] =
                                                       $this->getCurrentStepId($request);
                                                  $impRepo->save($request->all());
                                                  $request['is_bre_execute'] = false;
                                                  $apRepo->save($request->all());
                                                  $logPushData = $request;
                                                  $logPushData['cc_stage_handle'] = 'address-details';
                                                  $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                                                  $this->pushDataFieldTrackingLog($logPushData);
                                             }
                                        } else {
                                             $reData['lead_id'] = $coApplicantId;
                                             $reData['quote_id'] = $request->quote_id;
                                             $reData['type'] = 'BRE1';
                                             $existEligibile = $eligibleRepo->view($reData);
                                             if (
                                                  $existEligibile['eligibility_data'] &&
                                                  $existEligibile['eligibility_data']['type'] == 'BRE1'
                                             ) {
                                                  $eligibleRepo->removeExistData($reData);
                                             }
                                             $breFlag = false;

                                             $req['lead_id'] = $leadId;
                                             $req['next_stage'] =
                                                  config('constants/productStepHandle.address-details');
                                             $requestData['lead_id'] = $request->lead_id;
                                             $requestData['quote_id'] = $request->quote_id;
                                             $requestData['master_product_id'] = $request->master_product_id;
                                             $requestData['master_product_step_id'] =  $this->getCurrentStepId($req);
                                             $requestData['is_bre_execute'] = false;
                                             $impRepo->save($requestData);
                                             $apRepo->save($requestData);
                                             $logPushData = $request;
                                             $logPushData['cc_stage_handle'] = 'address-details';
                                             $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                                             $this->pushDataFieldTrackingLog($logPushData);
                                        }
                                        return $this->responseJson(
                                             config('journey/http-status.success.status'),
                                             config('journey/http-status.success.message'),
                                             config('journey/http-status.success.code'),
                                             ['bre_flag' => $breFlag]
                                        );
                                   } else {
                                        $req['lead_id'] = $leadId;
                                        $req['next_stage'] =
                                             config('constants/productStepHandle.address-details');
                                        $requestData['lead_id'] = $request->lead_id;
                                        $requestData['quote_id'] = $request->quote_id;
                                        $requestData['master_product_id'] = $request->master_product_id;
                                        $requestData['master_product_step_id'] =  $this->getCurrentStepId($req);
                                        $requestData['is_bre_execute'] = false;
                                        $impRepo->save($requestData);
                                        $apRepo->save($requestData);
                                        $logPushData = $request;
                                        $logPushData['cc_stage_handle'] = 'address-details';
                                        $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                                        $this->pushDataFieldTrackingLog($logPushData);
                                        return $this->responseJson(
                                             config('journey/http-status.error.status'),
                                             config('journey/http-status.error.message'),
                                             config('journey/http-status.error.code'),
                                             ['bre_flag' => $breFlag]
                                        );
                                   }
                              } else {
                                   $breFlag = false;
                                   $reData['lead_id'] = $coApplicantId;
                                   $reData['quote_id'] = $request->quote_id;
                                   $reData['type'] = 'BRE1';
                                   $existEligibile = $eligibleRepo->view($reData);
                                   if (
                                        $existEligibile['eligibility_data'] &&
                                        $existEligibile['eligibility_data']['type'] == 'BRE1'
                                   ) {
                                        $eligibleRepo->removeExistData($reData);
                                   }
                                   $req['lead_id'] = $leadId;
                                   $req['next_stage'] =
                                        config('constants/productStepHandle.address-details');
                                   $requestData['lead_id'] = $request->lead_id;
                                   $requestData['quote_id'] = $request->quote_id;
                                   $requestData['master_product_id'] = $request->master_product_id;
                                   $requestData['master_product_step_id'] =  $this->getCurrentStepId($req);
                                   $requestData['is_bre_execute'] = false;
                                   $impRepo->save($requestData);
                                   $apRepo->save($requestData);
                                   $logPushData = $request;
                                   $logPushData['cc_stage_handle'] = 'address-details';
                                   $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                                   $this->pushDataFieldTrackingLog($logPushData);
                                   return $this->responseJson(
                                        config('journey/http-status.success.status'),
                                        config('journey/http-status.success.message'),
                                        config('journey/http-status.success.code'),
                                        ['bre_flag' => $breFlag]
                                   );
                              }
                         } else {
                              return $this->responseJson(
                                   config('journey/http-status.error.status'),
                                   config('journey/http-status.error.message'),
                                   config('journey/http-status.error.code'),
                                   []
                              );
                         }
                    }
               } else {
                    // co-applicant eligibility detail
                    $rules = [
                         "loan_amount" => "required | numeric",
                         "tenure" => "required | numeric",
                    ];
                    $validator = Validator::make($request->all(), $rules);
                    if ($validator->fails()) {
                         return $validator->errors();
                    }
                    $leadId = $request['lead_id'];
                    $coApplicantId = $apDtRepo->getCoApplicantId($request);
                    $coAppEligible['lead_id'] = $coApplicantId;
                    $coAppEligible['quote_id'] = $request->quote_id;
                    $coAppEligible['type'] = 'BRE1';
                    $coAppEligible['loan_amount'] = $request->loan_amount;
                    $coAppEligible['tenure'] = $request->tenure;
                    $coAppEligible['is_deviation'] = $request->is_deviation;
                    $coAppEligible['is_co_applicant'] = 0;
                    $eligibleSave = $eligibleRepo->save($coAppEligible);
                    if ($eligibleSave) {
                         // save into Impression Table
                         $request['lead_id'] = $leadId;
                         $request['next_stage'] = config('constants/productStepHandle.address-details');
                         $request['master_product_step_id'] = $this->getCurrentStepId($request);
                         $impressionSave = $impRepo->save($request->all());
                         if ($impressionSave->id) {
                              $previousImpressionId = $impRepo->getPreviousImpressionId($impressionSave->id, $request);
                              $request['previous_impression_id'] = $previousImpressionId->id ?? $impressionSave->id;
                              $request['current_impression_id'] = $impressionSave->id;
                              $reqData['bre1_loan_amount'] = $request->bre1_loan_amount;
                              $reqData['bre1_updated_loan_amount'] = $request->bre1_updated_loan_amount;
                              $reqData['lead_id'] = $request->lead_id;
                              $reqData['quote_id'] = $request->quote_id;
                              $reqData['master_product_step_id'] = $this->getCurrentStepId($request);
                              $apRepo->save($reqData);
                              $logPushData = $request;
                              $logPushData['cc_stage_handle'] = 'address-details';
                              $logPushData['cc_sub_stage_handle']
                                   = 'address-details-pending';
                              $this->pushDataFieldTrackingLog($logPushData);
                              return $this->responseJson(
                                   config('journey/http-status.success.status'),
                                   config('journey/http-status.success.message'),
                                   config('journey/http-status.success.code'),
                                   []
                              );
                         } else {
                              return $this->responseJson(
                                   config('journey/http-status.error.status'),
                                   config('journey/http-status.error.message'),
                                   config('journey/http-status.error.code'),
                                   []
                              );
                         }
                    } else {
                         return $this->responseJson(
                              config('journey/http-status.error.status'),
                              config('journey/http-status.error.message'),
                              config('journey/http-status.error.code'),
                              []
                         );
                    }
               }
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("coApplicantSave " . $throwable->__toString());
          }
     }
     public function exportLead(Request $request)
     {
          try {
               $repository = new LeadRepository();
               $data['methodName'] = 'list';
               $data['fileName'] = 'Lead-Report-';
               $data['moduleName'] = 'Lead';
               return $this->exportData($request, $repository, $data);
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("LeadService list : %s", $throwable->__toString());
          }
     }
     /**
      * Get Sanction Letter  Data.
      *
      */
     public function getSLData(
          Request $request,
          ApplicationRepository $applicationRepo,
          PersonalDetailRepository $personalDtRepo,
          PaymentTransactionRepository $paymenttransactionRepository
     ) {
          try {
               $impRepo = new ImpressionRepository();


               $getLeadData = $applicationRepo->getApplication($request['quote_id']);
               $initialProductID = $impRepo->fetchInitalImpProductId($request['quote_id']);
               $initProductType  = "";
               if ($initialProductID) {
                    $initProductName =   $impRepo->getProductName($initialProductID);
                    $initProductTypeData =   $impRepo->getProductType($initialProductID);
                    $initProductType = $initProductTypeData['productType']['name'];
               } else {
                    $initProductName =  $impRepo->getProductName($getLeadData->master_product_id);
                    $initProductTypeData =   $impRepo->getProductType($getLeadData->master_product_id);
                    $initProductType = $initProductTypeData['productType']['name'];
               }
               $previousImpression['base_product_name'] = $initProductName;
               $previousImpression['base_product_type'] = $initProductType;
               $previousImpression['lead_id'] = $getLeadData->lead_id;
               $sanctionLetterData = $this->leadRepo->getSLData($request['quote_id']);
               $sanctionLetterData['product_type'] = $applicationRepo->getProductDetails($getLeadData->master_product_id);
               $sanctionLetterData['co_applicant_id'] = $applicationRepo->getCoApplicantId($request);
               $sanctionLetterData['previous_impression'] = $previousImpression;
               $reqData['lead_id'] = $getLeadData->lead_id;
               $reqData['quote_id'] = $request['quote_id'];
               $coApplicantData = $this->leadRepo->getCoApplicantData($reqData);
               $sanctionLetterData['app_data'] = $getLeadData;
               $sanctionLetterData['app_data']['payment_transaction'] = null;
               $paymentData['payment_transaction_id'] =  $sanctionLetterData['payment_transaction_id'];
               $paymentData['quote_id'] =  $reqData['quote_id'];
               $sanctionLetterData['payment_transaction'] = null;
               if (!empty($paymentData['payment_transaction_id'])) {
                    $transactionData = $paymenttransactionRepository->fetchTransactionData($paymentData);
                    $sanctionLetterData['app_data']['payment_transaction'] =  $sanctionLetterData['payment_transaction'] = $transactionData;
               }

               if ($coApplicantData) {
                    $coApplicantId = $coApplicantData->co_applicant_id;
                    $requestData['lead_id'] = $coApplicantId;
                    $requestData['quote_id'] = $request['quote_id'];
                    $sanctionLetterData['co_applicant'] = $personalDtRepo->view($requestData);
                    unset($sanctionLetterData['co_applicant']['quote_id']);
               }
               if (empty($sanctionLetterData)) {
                    return $this->responseJson(
                         config('journey/http-status.failure.status'),
                         config('journey/http-status.failure.message'),
                         config('journey/http-status.failure.code'),
                         []
                    );
               }
               unset($sanctionLetterData['app_data']['cc_quote_id']);
               unset($sanctionLetterData['app_data']['quote_id']);
               unset($sanctionLetterData['app_data']['auth_token']);
               unset($sanctionLetterData['app_data']['session_auth_token']);
               unset($sanctionLetterData['personaldetail']['quote_id']);
               unset($sanctionLetterData['lead']['created_at']);
               unset($sanctionLetterData['lead']['updated_at']);
               unset($sanctionLetterData['lead']['id']);
               unset($sanctionLetterData['cc_quote_id']);
               unset($sanctionLetterData['auth_token']);
               unset($sanctionLetterData['session_auth_token']);
               unset($sanctionLetterData['product_type']['productDetails']['id']);
               unset($sanctionLetterData['product_type']['productType']['id']);
               unset($sanctionLetterData['product_type']['id']);

               if (count($sanctionLetterData['eligibilityData']) == 2) {
                    unset($sanctionLetterData['eligibilityData'][0]['quote_id']);
                    unset($sanctionLetterData['eligibilityData'][1]['quote_id']);
               } elseif (count($sanctionLetterData['eligibilityData']) == 1) {
                    unset($sanctionLetterData['eligibilityData'][0]['quote_id']);
               }
               $sanctionLetterData['custom_string'] = $sanctionLetterData['quote_id'];
               unset($sanctionLetterData['quote_id']);
               return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $sanctionLetterData
               );
          } catch (Throwable  | ClientException $throwable) {
               Log::info("LeadService -  getSLData " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
     /**
      * Final Sumbit Api call
      */
     public function finalSumbitApiCall(Request $request)
     {
          try {
               $apRepo = new ApplicationRepository();
               $appData = $apRepo->getApplication($request['quote_id']);
               if (($appData->is_paid == 1) || ($appData->is_paid == 0 && $appData->is_bre_execute == 0)) {
                    $application['is_stp'] = 0;
                    $finalSumbitData['quote_id'] = $request->quote_id;
                    if ($appData->is_paid == 1 && $appData->is_bre_execute == 1 && $appData->is_traversed == 0) {
                         $finalSumbitData['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                    } elseif ($appData->is_paid == 1 && $appData->is_bre_execute == 1 && $appData->is_traversed == 1) {
                         $finalSumbitData['api_source_page'] = config('constants/apiSourcePage.DOCUMENT_PAGE');
                    } elseif ($appData->is_paid == 0 && $appData->is_bre_execute == 0 && $appData->is_traversed == 0) {
                         $ccRepo = new CoreRepository();
                         $ccPushStatus = $ccRepo->getQuoteCount($request->quote_id);
                         $application['is_stp'] = $ccPushStatus == 0 ? 1 : 0;
                         $finalSumbitData['api_source_page'] = config('constants/apiSourcePage.DOCUMENT_PAGE');
                    } else {
                         $finalSumbitData['api_source_page'] = config('constants/apiSourcePage.DOCUMENT_PAGE');
                    }
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
               Log::info("LeadService -  finalSumbitApiCall " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     /* co-applicant eligibility view */
     public function viewCoApplicantEligibility(
          Request $request,
          ApplicationRepository $apRepo,
          EligibilityRepository $eligibilityRepo
     ) {
          try {
               $getLeadId = $apRepo->getAppDataByQuoteId($request['quote_id']);
               if ($getLeadId) {
                    $reqData['lead_id'] = $getLeadId->lead_id;
                    $reqData['quote_id'] = $request['quote_id'];
                    $coApplicantData = $this->leadRepo->getCoApplicantData($reqData);
                    if ($coApplicantData) {
                         $reqCoAppData['lead_id'] =  $coApplicantData->co_applicant_id;
                         $reqCoAppData['quote_id'] = $request['quote_id'];
                         $eligibilityDetail['eligibility_data'] = $eligibilityRepo->getBre1Eligibile($reqCoAppData);
                         $eligibilityDetail['app_data'] = $apRepo->getEligibilityAppData($request['quote_id']);
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
               }
          } catch (Throwable | Exception | ClientException $throwable) {
               Log::info("LeadService -  viewCoApplicantEligibility " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     /* co-applicant personal view */
     public function viewCoApplicantPersonalData(
          Request $request,
          ApplicationRepository $apRepo,
          PersonalDetailRepository $perDtRepo,
          EligibilityRepository $eligibilityRepo,
          BreLogRepository $breLog
     ) {
          try {
               $getLeadId = $apRepo->getAppDataByQuoteId($request['quote_id']);
               if ($getLeadId) {
                    $reqData['quote_id'] = $request['quote_id'];
                    $reqData['lead_id'] = $getLeadId->lead_id;
                    $coApplicantData = $this->leadRepo->getCoApplicantData($reqData);
                    if ($coApplicantData) {
                         $reqCoAppData['lead_id'] =  $coApplicantData->co_applicant_id;
                         $reqCoAppData['quote_id'] = $request['quote_id'];
                         $personalDetail['personal'] = $perDtRepo->view($reqCoAppData);
                         $personalDetail['relationship'] =
                              $perDtRepo->getRelationshipData($coApplicantData->co_applicant_id);
                         $personalDetail['eligibility'] = $eligibilityRepo->view($reqCoAppData);
                         if (!empty($personalDetail['eligibility']['eligibility_data'])) {
                              unset($personalDetail['eligibility']['eligibility_data']['loan_amount']);
                              unset($personalDetail['eligibility']['eligibility_data']['type']);
                              unset($personalDetail['eligibility']['eligibility_data']['tenure']);
                              unset($personalDetail['eligibility']['eligibility_data']['is_co_applicant']);
                         }
                         $reqData['lead_id'] = $getLeadId->lead_id;
                         $breData = $breLog->fetchBreOneData($reqData);
                         if ($breData && $breData != null && $breData != '' && isset($breData->api_data)) {
                              $breDataResponse = json_decode($breData->api_data, true);
                              $personalDetail['eligibility_check'] = $breDataResponse['Table1'] ?? null;
                         } else {
                              $personalDetail['eligibility_check'] = null;
                         }
                         return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              $personalDetail
                         );
                    } else {
                         $reqData['quote_id'] = $request['quote_id'];
                         $reqData['lead_id'] = $getLeadId->lead_id;
                         $personalDetail['eligibility'] = $eligibilityRepo->view($reqData);
                         if (!empty($personalDetail['eligibility']['eligibility_data'])) {
                              unset($personalDetail['eligibility']['eligibility_data']['loan_amount']);
                              unset($personalDetail['eligibility']['eligibility_data']['type']);
                              unset($personalDetail['eligibility']['eligibility_data']['tenure']);
                              unset($personalDetail['eligibility']['eligibility_data']['is_co_applicant']);
                         }
                         return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              $personalDetail
                         );
                    }
               }
          } catch (Throwable | Exception | ClientException $throwable) {
               Log::info("LeadService -  viewCoApplicantPersonalData " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     /* co-applicant personal view */
     public function viewCoApplicantEmploymentData(
          Request $request,
          ApplicationRepository $apRepo,
          EmploymentDetailRepository $empDtRepo,
          EligibilityRepository $eligibilityRepo,
          BreLogRepository $breLog
     ) {
          try {
               $getLeadId = $apRepo->getAppDataByQuoteId($request['quote_id']);
               if ($getLeadId) {
                    $reqData['quote_id'] = $request['quote_id'];
                    $reqData['lead_id'] = $getLeadId->lead_id;
                    $coApplicantData = $this->leadRepo->getCoApplicantData($reqData);
                    if ($coApplicantData) {
                         $reqCoAppData['lead_id'] =  $coApplicantData->co_applicant_id;
                         $reqCoAppData['quote_id'] = $request['quote_id'];
                         $employmentDetail['employment'] = $empDtRepo->view($reqCoAppData);
                         if ($empDtRepo->view($reqCoAppData) != null) {
                              $employmentDetail['employment']['industry_type_data']
                                   = $empDtRepo->getIndustryType($employmentDetail['employment']->industry_segment_id);
                         }
                         $employmentDetail['eligibility'] = $eligibilityRepo->view($reqCoAppData);
                         $reqData['lead_id'] = $getLeadId->lead_id;
                         $breData = $breLog->fetchBreCoApplicantOneData($reqData);
                         if ($breData && $breData != null && $breData != '' && isset($breData->api_data)) {
                              $breDataResponse = json_decode($breData->api_data, true);
                              $employmentDetail['eligibility_check'] = $breDataResponse['Table1'] ?? null;
                         } else {
                              $employmentDetail['eligibility_check'] = null;
                         }
                         if (!empty($employmentDetail['eligibility']['eligibility_data'])) {
                              unset($employmentDetail['eligibility']['eligibility_data']['quote_id']);
                         }
                         return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              $employmentDetail
                         );
                    } else {
                         $reqData['quote_id'] = $request['quote_id'];
                         $reqData['lead_id'] = $getLeadId->lead_id;
                         $employmentDetail['eligibility'] = $eligibilityRepo->view($reqData);
                         return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              $employmentDetail
                         );
                    }
               }
          } catch (Throwable  | ClientException $throwable) {
               Log::info("LeadService -  viewCoApplicantEmploymentData " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
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
          } catch (Throwable | Exception | HttpClientException $throwable) {
               Log::info("LeadService -  checkDobForKarzaDisplay " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
}
