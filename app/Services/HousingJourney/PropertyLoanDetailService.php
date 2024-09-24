<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\MasterProductRepository;
use App\Repositories\HousingJourney\MasterPropertyCurrentStateRepository;
use App\Services\Service;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Validator;
use App\Utils\CoreTrait;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;
use Carbon\Carbon;
use App\Utils\CommonTrait;

// Define constant
define('PRODUCT_CODE_LAPRESI', config('constants/productCode.LAPResi'));
define('PRODUCT_CODE_LAPBT',  config('constants/productCode.LAPBT'));
define('PRODUCT_CODE_LAPBT_TOPUP', config('constants/productCode.LAPBTTopup'));
define('PRODUCT_STEP_HANDLE_DOCUMENT_UPLOAD', config('constants/productStepHandle.document-upload'));
define('PRODUCT_CODE_LAPCOM', config('constants/productCode.LAPCom'));
define('PROP_API_STATUS_SUCCESS', config('journey/http-status.success.status'));
define('PROP_API_STATUS_SUCCESS_MESSAGE', config('journey/http-status.success.message'));
define('PROP_API_STATUS_SUCCESS_CODE', config('journey/http-status.success.code'));
define('PROP_API_STATUS_ERROR', config('journey/http-status.error.status'));
define('PROP_API_STATUS_ERROR_MESSAGE', config('journey/http-status.error.message'));
define('PROP_API_STATUS_ERROR_CODE', config('journey/http-status.error.code'));
define('PROP_API_STATUS_CONNECTION_ERROR', config('journey/http-status.connection-error.status'));
define('PROP_API_STATUS_CONNECTION_ERROR_MESSAGE', config('journey/http-status.connection-error.message'));
define('PROP_API_STATUS_CONNECTION_ERROR_CODE', config('journey/http-status.connection-error.code'));

class PropertyLoanDetailService extends Service
{
      use CrmTrait;
      use CoreTrait;
      use CommonTrait;
      /**
       * save property loan detail.
       *
       */
      public function save(Request $request)
      {
            try {
                  $propertyDetailRepo = new PropertyLoanDetailRepository();
                  $impressionRepo = new ImpressionRepository();
                  $applicationRepo = new ApplicationRepository();
                  $empDtRepo = new EmploymentDetailRepository();
                  $eligibleRepo = new EligibilityRepository();
                  $masterProductRepo = new MasterProductRepository();

                  $productCode = $masterProductRepo->getProductCode($request->master_product_id);
                  $rules = [];
                  $stringRegex = 'regex:/^[a-zA-Z\s]+$/';
                  $alphanumRegex = 'regex:/^[a-zA-Z0-9]+$/';
                  $alphaSpaceRegex = 'regex:/^[A-Za-z \.]+$/';
                  $propertyRegex = 'regex:/^[A-Za-z \.-]+$/';
                  $areaRegex = 'regex:/^[A-Za-z0-9.\,\- ]+$/';
                  $rules['cost'] = ['required', 'numeric'];
                  $rules['pincode_id'] = ['required'];
                  $rules['area'] = ['required',  $areaRegex];
                  $rules['city'] = ['required', $stringRegex];
                  $rules['state'] = ['required', $stringRegex];


                  if ($productCode == 'HLNew') {
                        $rules['is_property_identified'] = ['required'];
                        $rules['property_type_id'] = ['required', 'numeric'];
                        $rules['age'] = ['required', 'numeric'];

                        if ($request->is_property_identified) {
                              $rules['property_purchase_from'] = ['required', $alphanumRegex];
                              $rules['property_current_state_id'] = ['required', $alphanumRegex];
                              $rules['project'] = ['required',  $alphaSpaceRegex];
                        }
                  } elseif ($productCode == 'HLPltConst') {
                        $rules['is_property_identified'] = ['required'];
                        $rules['property_purpose_id'] = ['required', $alphanumRegex];

                        if ($request->is_property_identified) {
                              $rules['project'] = ['required', $alphaSpaceRegex];
                              $rules['plot_cost'] = ['required', 'numeric'];
                              $rules['construction_cost'] = ['required', 'numeric'];
                        }
                  } else {
                        $rules['property_type_id'] = ['required',  'numeric'];
                        $rules['age'] = ['required', 'numeric'];
                        $rules['is_property_loan_free'] = ['required'];
                        $rules['property_current_state_id'] = ['required', 'numeric'];

                        if ($request->is_property_loan_free) {
                              $rules['existing_loan_provider'] = ['required', $propertyRegex];
                              $rules['original_loan_amount'] = ['required', 'numeric'];
                              $rules['original_loan_tenure'] = ['required', 'numeric'];
                              $rules['outstanding_loan_amount'] = ['required', 'numeric'];
                              $rules['outstanding_loan_tenure'] = ['required', 'numeric'];
                              $rules['monthly_installment_amount'] = ['required', 'numeric'];
                        }
                  }
                  $validator = Validator::make($request->all(), $rules);
                  if ($validator->fails()) {
                        return $validator->errors();
                  }
                  // Check property convertion status
                  $updatedProductCode =  $this->checkPropertyConvertionStatus($request->all());
                  // save into propertyloan detail Table
                  if ($updatedProductCode) {
                        $request['master_product_id'] = $updatedProductCode;
                  }
                  $request['project_name'] = $request['project'] ?? null;
                  $request['existing_loan_provider_name'] = $request['existing_loan_provider'] ?? null;
                  $request['existing_loan_provider'] = $request['existing_loan_provider_id'] ?? null;
                  $propertyDetailSave = $propertyDetailRepo->save($request->all());
                  $employmentData = $empDtRepo->view($request->all());
                  if ($propertyDetailSave->id) {
                        // save into impression Table
                        $request['next_stage'] = config('constants/productStepHandle.offer-details');
                        $request['master_product_step_id'] = $this->getCurrentStepId($request);
                        $impressionSave = $impressionRepo->save($request->all());
                        $previousImpression  = $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
                        $breFlag = true;
                        if ($impressionSave->id) {
                              $request['previous_impression_id'] = $previousImpression->id ?? $impressionSave->id;
                              $request['current_impression_id'] = $impressionSave->id;
                              // save into application Table
                              $applicationRepo->save($request->all());
                              // Prepare BRE Level two Data
                              $breFlag = true;
                              if ($employmentData['is_income_proof_document_available'] != 0 && $employmentData['salary_mode_id'] != 2 && $employmentData['total_experience'] != 1) {
                                    $request['is_bre_execute'] = true;
                                    $request['bre_type'] = config('constants/apiType.BRE_LEVEL_TWO');
                                    $request['stage'] = config('constants/apiSourcePage.PROPERTY_LOAN_DETAIL_PAGE');
                                    $breTwoData = $this->prepareBREData($request);
                                    if ($breTwoData || gettype($breTwoData) == "string") {
                                          $request['is_bre_execute'] = true;
                                          $breData = json_decode($breTwoData, true);
                                          if (isset($breData['Table1']) && $breData['Table1'][0]['IsDev'] == 'N') {
                                                $request['is_bre_execute'] = true;
                                                $applicationRepo->save($request->all());
                                                $logPushData = $request;
                                                $logPushData['cc_stage_handle'] = 'offer-details';
                                                $logPushData['cc_sub_stage_handle']
                                                      = 'offer-details-final-voucher-page';
                                                $this->pushDataFieldTrackingLog($logPushData);
                                                // TODO need to enable once stage SMS works
                                                // $this->sendVoucherSms($request);
                                                $breFlag = true;
                                                $loanArray = array();
                                                $finalBreData = array();
                                                if ($breData) {
                                                      foreach ($breData['Table'] as $bre) {
                                                            if ($bre['LnAmount'] != 0 && $bre['LnAmount'] > 750000) {
                                                                  $loanArray[$bre['Tenure']] = $bre['LnAmount'];
                                                                  $finalBreData[] = $bre;
                                                            }
                                                      }
                                                }
                                                $breResponse['actual_data'] = $finalBreData;
                                                if (count($breResponse['actual_data']) == 0) {
                                                      $breFlag = false;
                                                      $request['next_stage'] = PRODUCT_STEP_HANDLE_DOCUMENT_UPLOAD;
                                                      $request['master_product_step_id'] = $this->getCurrentStepId($request);
                                                      $impressionSave = $impressionRepo->save($request->all());
                                                      $request['bre2_loan_amount'] = 0;
                                                      $request['is_bre_execute'] = false;
                                                      $applicationRepo->save($request->all());
                                                      $reData['lead_id'] = $request->lead_id;
                                                      $reData['quote_id'] = $request->quote_id;
                                                      $reData['type'] = 'BRE2';
                                                      $existEligibile = $eligibleRepo->getBre2Eligibile($reData);
                                                      if ($existEligibile) {
                                                            $eligibleRepo->removeExistData($reData);
                                                      }
                                                      $logPushData = $request;
                                                      $logPushData['cc_stage_handle'] = 'document-upload';
                                                      $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                                                      $this->pushDataFieldTrackingLog($logPushData);
                                                }
                                          } elseif ($breTwoData == 'Connection timeout' || $breTwoData == 'Error:Contact Administator' || $breTwoData == 'Internal Server Error.') {
                                                $request['next_stage'] =  config('constants/productStepHandle.property-loan-details');
                                                $request['master_product_step_id'] = $this->getCurrentStepId($request);
                                                $impressionRepo->save($request->all());
                                                $breFlag = false;
                                                $request['is_bre_execute'] = false;
                                                $request['bre2_loan_amount'] = 0;
                                                $applicationRepo->save($request->all());
                                                return $this->responseJson(
                                                      PROP_API_STATUS_CONNECTION_ERROR,
                                                      PROP_API_STATUS_CONNECTION_ERROR_MESSAGE,
                                                      PROP_API_STATUS_CONNECTION_ERROR_CODE,
                                                      []
                                                );
                                          } else {
                                                $request['next_stage'] = PRODUCT_STEP_HANDLE_DOCUMENT_UPLOAD;
                                                $request['master_product_step_id'] = $this->getCurrentStepId($request);
                                                $impressionSave = $impressionRepo->save($request->all());
                                                $request['bre2_loan_amount'] = 0;
                                                $request['is_bre_execute'] = false;
                                                $applicationRepo->save($request->all());
                                                $breFlag = false;
                                                $reData['lead_id'] = $request->lead_id;
                                                $reData['quote_id'] = $request->quote_id;
                                                $reData['type'] = 'BRE2';
                                                $existEligibile =  $eligibleRepo->getBre2Eligibile($reData);
                                                if ($existEligibile) {
                                                      $eligibleRepo->removeExistData($reData);
                                                }
                                                $logPushData = $request;
                                                $logPushData['cc_stage_handle'] = 'document-upload';
                                                $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                                                $this->pushDataFieldTrackingLog($logPushData);
                                          }
                                    } elseif ($breTwoData == 'Connection timeout' || $breTwoData == 'Error:Contact Administator' || $breTwoData == 'Internal Server Error.') {
                                          $request['next_stage'] =  config('constants/productStepHandle.property-loan-details');
                                          $request['master_product_step_id'] = $this->getCurrentStepId($request);
                                          $impressionRepo->save($request->all());
                                          $breFlag = false;
                                          $request['is_bre_execute'] = false;
                                          $request['bre2_loan_amount'] = 0;
                                          $applicationRepo->save($request->all());
                                          return $this->responseJson(
                                                PROP_API_STATUS_CONNECTION_ERROR,
                                                PROP_API_STATUS_CONNECTION_ERROR_MESSAGE,
                                                PROP_API_STATUS_CONNECTION_ERROR_CODE,
                                                []
                                          );
                                    }
                              } else {
                                    $breFlag = false;
                                    $reData['lead_id'] = $request->lead_id;
                                    $reData['quote_id'] = $request->quote_id;
                                    $reData['type'] = 'BRE2';
                                    $existEligibile = $eligibleRepo->getBre2Eligibile($reData);
                                    if ($existEligibile) {
                                          $eligibleRepo->removeExistData($reData);
                                    }
                                    $request['next_stage'] = PRODUCT_STEP_HANDLE_DOCUMENT_UPLOAD;
                                    $request['master_product_step_id'] = $this->getCurrentStepId($request);
                                    $impressionSave = $impressionRepo->save($request->all());
                                    $request['bre2_loan_amount'] = 0;
                                    $request['is_bre_execute'] = false;
                                    $applicationRepo->save($request->all());
                                    $logPushData = $request;
                                    $logPushData['cc_stage_handle'] = 'document-upload';
                                    $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                                    $this->pushDataFieldTrackingLog($logPushData);
                              }
                              return $this->responseJson(
                                    PROP_API_STATUS_SUCCESS,
                                    PROP_API_STATUS_SUCCESS_MESSAGE,
                                    PROP_API_STATUS_SUCCESS_CODE,
                                    ['bre_flag' => $breFlag]
                              );
                        }
                        return $this->responseJson(
                              PROP_API_STATUS_SUCCESS,
                              PROP_API_STATUS_SUCCESS_MESSAGE,
                              PROP_API_STATUS_SUCCESS_CODE,
                              ["bre_flag" => $breFlag]
                        );
                  } else {
                        return $this->responseJson(PROP_API_STATUS_ERROR, PROP_API_STATUS_ERROR_MESSAGE, PROP_API_STATUS_ERROR_CODE, []);
                  }
            } catch (Throwable  | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailService -  save " . $throwable);
                  return $this->responseJson(
                        PROP_API_STATUS_ERROR,
                        PROP_API_STATUS_ERROR_MESSAGE,
                        PROP_API_STATUS_ERROR_CODE,
                        []
                  );
            }
      }

      /**
       * check property convertion status
       *
       * @param $request
       */
      public function checkPropertyConvertionStatus($request)
      {
            try {
                  $applicationRepo = new ApplicationRepository();
                  $propertyRepo = new PropertyLoanDetailRepository();
                  $proRepo = new MasterProductRepository();
                  $leadRepo = new LeadRepository();
                  $appData = $applicationRepo->getAppData($request['quote_id']);
                  $leadData = $leadRepo->view($request['lead_id']);
                  $existingCustomer = false;
                  if ($leadData && isset($leadData['customer_type']) && $leadData['customer_type'] == 'ETB') {
                        $existingCustomer = true;
                  }
                  $propertySelected = false;
                  if (isset($request['property_type'])) {
                        $propertyData = $propertyRepo->getPropertyType($request['property_type']);
                        $proHandle = $propertyData['handle'];
                  }
                  if (isset($request['is_property_loan_free'])) {
                        $loanFree = $request['is_property_loan_free'];
                  }
                  $breOneUpdatedLoanAmount = $appData['bre1_updated_loan_amount'];
                  $outstandingLoanAmount = $request['outstanding_loan_amount'];

                  switch ($appData['originMasterProductData']['code']) {
                              //HLBT
                        case config('constants/productCode.HLBT'):
                              if ($loanFree === true &&  $proHandle == "residential" && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLBTTopup'));
                              } elseif ($loanFree === true &&  ($proHandle == "industrial" || $proHandle == "commercial") && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT_TOPUP);
                              } elseif ($loanFree === true && ($proHandle == "industrial" || $proHandle == "commercial") && $breOneUpdatedLoanAmount <= $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              } elseif ($loanFree === false &&  ($proHandle == "industrial" || $proHandle == "commercial")) {
                                    //SheetID : 6
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              }
                              break;
                              //HLNew
                        case config('constants/productCode.HLNew'):
                              if ($request['property_purchase_from'] == "Seller") {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLResale'));
                              }
                              break;

                              // Home extension
                        case config('constants/productCode.HLExt'):

                              if ($loanFree === false || ($existingCustomer === true && $propertySelected === true)) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLExt'));
                              } elseif ($loanFree === true) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLBTExt'));
                              }
                              break;

                              // Home improvement
                        case config('constants/productCode.HLImp'):

                              if ($loanFree === false || ($existingCustomer === true && $propertySelected === true)) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLImp'));
                              } elseif ($loanFree === true) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLBTImp'));
                              }
                              break;

                              //LAPCom
                        case PRODUCT_CODE_LAPCOM:

                              if (($proHandle == "industrial" || $proHandle == "commercial") && $loanFree === false) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPCOM);
                              } elseif (($proHandle == "industrial" || $proHandle == "commercial") && $loanFree === true && $breOneUpdatedLoanAmount <= $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              } elseif (($proHandle == "industrial" || $proHandle == "commercial") && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT_TOPUP);
                              } elseif ($proHandle == "residential"  && $loanFree === false) {
                                    //SheetID : 11
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPRESI);
                              } elseif ($proHandle == "residential" && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    //SheetID : 13
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT_TOPUP);
                              } elseif ($proHandle == "residential" && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                                    //SheetID : 14
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              }
                              break;

                              //LAPResi
                        case PRODUCT_CODE_LAPRESI:
                              if ($proHandle == "residential"   && $loanFree === false) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPRESI);
                              } elseif ($proHandle == "residential" && $loanFree === true && $breOneUpdatedLoanAmount <= $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              } elseif ($proHandle == "residential" && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT_TOPUP);
                              } elseif (($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === false) {
                                    //SheetID : 18
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPCOM);
                              } elseif (($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    //SheetID : 21
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT_TOPUP);
                              } elseif (($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                                    //SheetID : 22
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              }

                              break;

                              //   LAPTopup or HLTopup
                        case config('constants/productCode.HLTopup'):
                        case config('constants/productCode.LAPTopup'):
                              if ($existingCustomer === false && $proHandle == "residential" && $loanFree === false) {
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPRESI);
                              } elseif ($existingCustomer === false && $proHandle == "residential" && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    //SheetID : 31
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLBTTopup'));
                              } elseif ($existingCustomer === false && $proHandle == "residential" && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                                    //SheetID : 32
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLBT'));
                              } elseif ($existingCustomer === false && ($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === false) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.LAPTopup'));
                              } elseif ($existingCustomer === false && ($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                                    //SheetID : 33
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT_TOPUP);
                              } elseif ($existingCustomer === false && ($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                                    //SheetID : 34
                                    return $proRepo->masterProductIdFetch(PRODUCT_CODE_LAPBT);
                              } elseif ($existingCustomer === true && $propertySelected === true && ($proHandle == "commercial" || $proHandle == "industrial")) {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.LAPTopup'));
                              } elseif ($existingCustomer === true && $propertySelected === true &&  $proHandle == "residential") {
                                    return $proRepo->masterProductIdFetch(config('constants/productCode.HLTopup'));
                              }
                              break;
                  }
            } catch (Throwable  | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailService -  checkPropertyConvertionStatus " . $throwable);
            }
      }

      /**
       * view property loan detail.
       *
       */
      public function view(Request $request, PropertyLoanDetailRepository $propertyDetailRepo)
      {
            try {
                  $propertyDetail = $propertyDetailRepo->view($request);
                  return $this->responseJson(PROP_API_STATUS_SUCCESS, PROP_API_STATUS_SUCCESS_MESSAGE, PROP_API_STATUS_SUCCESS_CODE, $propertyDetail);
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailService -  view " . $throwable);
                  return $this->responseJson(
                        PROP_API_STATUS_ERROR,
                        PROP_API_STATUS_ERROR_MESSAGE,
                        PROP_API_STATUS_ERROR_CODE,
                        []
                  );
            }
      }

      /**
       * view property loan detail.
       *
       */
      public function fetchPropertyData(Request $request, PropertyLoanDetailRepository $propertyDetailRepo)
      {
            try {
                  $propertyDetail = $propertyDetailRepo->fetchPropertyData($request);
                  unset($propertyDetail['quote_id']);
                  unset($propertyDetail['updated_at']);
                  unset($propertyDetail['id']);
                  unset($propertyDetail['lead_id']);
                  unset($propertyDetail['created_at']);
                  return $this->responseJson(PROP_API_STATUS_SUCCESS, PROP_API_STATUS_SUCCESS_MESSAGE, PROP_API_STATUS_SUCCESS_CODE, $propertyDetail);
            } catch (Throwable  | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailService -  fetchPropertyData " . $throwable);
                  return $this->responseJson(
                        PROP_API_STATUS_ERROR,
                        PROP_API_STATUS_ERROR_MESSAGE,
                        PROP_API_STATUS_ERROR_CODE,
                        []
                  );
            }
      }

      /**
       * save offer step into impression
       *
       */
      public function saveOffer(
            Request $request,
            ImpressionRepository $impRepo,
            ApplicationRepository $applicationRepo,
            EligibilityRepository $eligRepo
      ) {
            try {
                  $reqData['quote_id'] = $request['quote_id'];
                  $reqData['lead_id'] = $request['lead_id'];
                  $reqData['loan_amount'] = $request['loan_amount'];
                  $reqData['tenure'] = $request['tenure'];
                  $reqData['type'] = 'BRE2';
                  $eligRepo->save($reqData);
                  $request['next_stage'] = PRODUCT_STEP_HANDLE_DOCUMENT_UPLOAD;
                  $request['master_product_step_id'] = $this->getCurrentStepId($request);
                  $impData = $impRepo->save($request->all());
                  $previousImpression  = $impRepo->getPreviousImpressionId($impData->id, $request);
                  if ($impData->id) {
                        $request['previous_impression_id'] = $previousImpression->id ?? $impData->id;
                        $request['current_impression_id'] = $impData->id;
                        $requestData['bre2_loan_amount'] = $request->bre2_loan_amount;
                        $requestData['offer_amount'] = $request->loan_amount;
                        $requestData['lead_id'] = $request->lead_id;
                        $requestData['quote_id'] = $request->quote_id;
                        $requestData['master_product_step_id'] = $this->getCurrentStepId($request);
                        $applicationRepo->save($requestData);
                  }
                  $logPushData = $request;
                  $logPushData['cc_stage_handle'] = 'document-upload';
                  $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                  $this->pushDataFieldTrackingLog($logPushData);
                  return $this->responseJson(
                        PROP_API_STATUS_SUCCESS,
                        PROP_API_STATUS_SUCCESS_MESSAGE,
                        PROP_API_STATUS_SUCCESS_CODE,
                        []
                  );
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailService -  saveOffer " . $throwable);
                  return $this->responseJson(
                        PROP_API_STATUS_ERROR,
                        PROP_API_STATUS_ERROR_MESSAGE,
                        PROP_API_STATUS_ERROR_CODE,
                        []
                  );
            }
      }
      public function list(Request $request, PropertyLoanDetailRepository $propertyDetailRepo)
      {
            try {
                  $propertyLoanDetailList = $propertyDetailRepo->list($request);
                  return $this->responseJson(
                        config('crm/http-status.success.status'),
                        config('crm/http-status.success.message'),
                        config('crm/http-status.success.code'),
                        $propertyLoanDetailList
                  );
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailService list : %s", $throwable->__toString());
            }
      }
      public function exportPropertyLoanDetails(Request $request)
      {
            try {
                  $repository = new PropertyLoanDetailRepository();
                  $data['methodName'] = 'list';
                  $data['fileName'] = 'Property-Loan-Detail-Report-';
                  $data['moduleName'] = 'PropertyLoanDetail';
                  return $this->exportData($request, $repository, $data);
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailLoanService exportPropertyLoanDetails : %s", $throwable->__toString());
            }
      }
      /* search project */
      public function searchProject(Request $request, PropertyLoanDetailRepository $propRepo)
      {
            try {
                  $projectData = $propRepo->projectSearch($request['projectName']);
                  return $this->responseJson(
                        PROP_API_STATUS_SUCCESS,
                        PROP_API_STATUS_SUCCESS_MESSAGE,
                        PROP_API_STATUS_SUCCESS_CODE,
                        ['project_data' => $projectData]
                  );
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailLoanService searchProject : %s", $throwable->__toString());
            }
      }
      /* search loan provider */
      public function searchLoanProvider(Request $request, MasterPropertyCurrentStateRepository $masterPropertyRepo)
      {
            try {
                  $loanProviderData = $masterPropertyRepo->getLoanProvider($request['loanProvider']);
                  return $this->responseJson(
                        PROP_API_STATUS_SUCCESS,
                        PROP_API_STATUS_SUCCESS_MESSAGE,
                        PROP_API_STATUS_SUCCESS_CODE,
                        ['loan_provider_data' => $loanProviderData]
                  );
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailLoanService searchProject : %s", $throwable->__toString());
            }
      }
      /* exist property save */
      public function existPropertySave(Request $request, PropertyLoanDetailRepository $propRepo)
      {
            try {
                  if ($request->exist_prop_address != null) {
                        $propertyCurrentStateCode =
                              $request->exist_prop_address['Prpty']
                              == 'R' ? 'L' : $request->exist_prop_address['Prpty'];
                        $propertyArray['lead_id'] = $request->lead_id;
                        $propertyArray['quote_id'] = $request->quote_id;
                        $propertyArray['property_type_id'] =
                              $propRepo->getPropertyTypeId($request->exist_prop_address['PrpOcc']) ?? null;
                        $propertyArray['property_current_state_id'] =
                              $propRepo->getPropertyCurrentStateId($propertyCurrentStateCode) ?? null;
                        $propertyArray['pincode_id'] =
                              $propRepo->getPincodeId($request->exist_prop_address['Pincode']) ?? null;
                        $propertyArray['state'] = $request->exist_prop_address['State'] ?? null;
                        $propertyArray['area'] = $request->exist_prop_address['Area'] ?? null;
                        $propertyArray['city'] = $request->exist_prop_address['City'] ?? null;
                        $propertyArray['cost'] = $request->exist_prop_address['MarketValue'] ?? null;
                        $propertyArray['outstanding_loan_amount'] = $request->exist_prop_address['OutPrin'] ?? null;
                        $propertyArray['monthly_installment_amount'] = $request->exist_prop_address['EMI'] ?? null;
                        $propertyArray['outstanding_loan_tenure']
                              = $request->exist_prop_address['Remainingtenure'] ?? null;
                        $propertyArray['is_existing_property'] = 1;
                        $propertyArray['is_property_loan_free'] = 0;
                        if (
                              isset($request->exist_prop_address['OutPrin'])
                              && ($request->exist_prop_address['OutPrin']
                                    != "" || $request->exist_prop_address['OutPrin'] != 0)
                        ) {
                              $propertyArray['is_property_loan_free'] = 1;
                        }
                        $propertySave = $propRepo->save($propertyArray);
                        if ($propertySave) {
                              return $this->responseJson(
                                    PROP_API_STATUS_SUCCESS,
                                    PROP_API_STATUS_SUCCESS_MESSAGE,
                                    PROP_API_STATUS_SUCCESS_CODE,
                                    []
                              );
                        } else {
                              return $this->responseJson(
                                    config('journey/http-status.failure.status'),
                                    config('journey/http-status.failure.message'),
                                    config('journey/http-status.failure.code'),
                                    []
                              );
                        }
                  } else {
                        $reqData['lead_id'] = $request->lead_id;
                        $reqData['quote_id'] = $request->quote_id;
                        $existPropFetch = $propRepo->view($reqData);
                        if ($existPropFetch && $existPropFetch->is_existing_property == 1) {
                              $propRepo->removeExistPropData($reqData);
                        }
                        return $this->responseJson(
                              PROP_API_STATUS_SUCCESS,
                              PROP_API_STATUS_SUCCESS_MESSAGE,
                              PROP_API_STATUS_SUCCESS_CODE,
                              []
                        );
                  }
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailLoanService existPropertySave : %s", $throwable->__toString());
            }
      }
      /* send sms for offer generate */
      public function sendVoucherSms($request)
      {
            try {
                  $appRepo = new ApplicationRepository();
                  $appData = $appRepo->getAppDataByQuoteId($request->quote_id);
                  $productCode = null;
                  if ($appData && $appData->masterProductData) {
                        $productCode = $appData->masterProductData->code;
                        $appData['product_name'] = $appData->masterProductData->display_name;
                        $loanAmountArr = array(
                              (int)$appData->bre1_loan_amount,
                              (int)$appData->bre1_updated_loan_amount
                        );
                        $appData['offer_amount'] = min($loanAmountArr);
                  }
                  $payLoad['api_type'] = $request->header('X-Api-Type');
                  $payLoad['api_source'] = $request->header('X-Api-Source-Page');
                  $payLoad['type'] =  $request->header('X-Api-Source');
                  $payLoad['api_data'] =  $request->all();
                  $payLoad['url'] =  $this->getProductNameUrl($productCode);
                  $payLoad['sms_template_handle'] = config('constants/productStepHandle.offer-details');
                  $payLoad['user_name'] =  config('journey/sms.cc_username');
                  $payLoad['password'] =  config('journey/sms.cc_password');
                  $payLoad['app_data'] =  $appData;
                  $payLoad['mobile_number'] =  $appData['mobile_number'];
                  $payLoad['is_short_url_required'] = true;
                  $payLoad['is_email_required'] = false;
                  $payLoad['email'] = null;
                  $payLoad['email_template_handle'] =  null;
                  $this->sendEmailWithSMS($payLoad);
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("PropertyLoanDetailLoanService sendVoucherSms : %s", $throwable->__toString());
            }
      }
}
