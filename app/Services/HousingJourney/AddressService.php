<?php

namespace App\Services\HousingJourney;

use Exception;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\AddressRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\BreLogRepository;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use App\Repositories\HousingJourney\MasterProductRepository;
use App\Utils\CoreTrait;
use Carbon\Carbon;
use App\Utils\CrmTrait;
use App\Utils\CommonTrait;

define('ADDRS_API_STATUS_CONNECTION_ERROR', config('journey/http-status.connection-error.status'));
define('ADDRS_API_STATUS_CONNECTION_ERROR_MESSAGE', config('journey/http-status.connection-error.message'));
define('ADDRS_API_STATUS_CONNECTION_ERROR_CODE', config('journey/http-status.connection-error.code'));
class AddressService extends Service
{
  /**
   * Save Address Detail
   *
   */
  use CommonTrait;
  use CrmTrait;
  use CoreTrait;
  public function save(
    Request $request,
    AddressRepository $addressRepo,
    ApplicationRepository $applicationRepo,
    ImpressionRepository $impressionRepo,
    EmploymentDetailRepository $empRepo,
    PropertyLoanDetailRepository $propRepo,
    EligibilityRepository $eligiRepo
  ) {
    try {
      $alphanumSpaceRegex = 'regex:/^[A-Za-z0-9&\:\-\.\(\)\,\!\/ ]+$/';
      $areaRegex = 'regex:/^[A-Za-z0-9.\,\- ]+$/';
      $cityRegex =  'regex:/^[a-zA-Z ]+$/';
      if ($request['is_address'] == 'current' || $request['is_address'] == 'permanent') {
        $rules = [
          "address1" =>  ['required', $alphanumSpaceRegex],
          "address2" =>  ['required', $alphanumSpaceRegex],
          "pincode_id" => "required | numeric",
          "area" => ['required', $areaRegex],
          "city" => ['required', $cityRegex],
          "state" => ['required', $cityRegex],
          "is_address" => "required | regex:/^[a-zA-Z0-9]+$/",
          "p_address_1" =>  ['required', $alphanumSpaceRegex],
          "p_address_2" =>  ['required', $alphanumSpaceRegex],
          "p_city" => ['required', $cityRegex],
          "p_area" => ['required', $areaRegex],
          "state" => ['required', $cityRegex],
          "p_pincode_id" => "required | numeric"
        ];
      } else {
        $rules = [
          "address1" => ['required', $alphanumSpaceRegex],
          "address2" => ['required', $alphanumSpaceRegex],
          "pincode_id" => "required | numeric",
          "area" => ['required', $areaRegex],
          "city" => ['required', $cityRegex],
          "state" => ['required', $cityRegex],
          "is_address" => "required | regex:/^[a-zA-Z0-9]+$/",
        ];
      }

      $validator = Validator::make($request->all(), $rules);
      if ($validator->fails()) {
        return $validator->errors();
      }
      $existingproperty = 0;
      $addressRepo->delete($request->all());
      // save into addressDetail Table
      if ($request['is_address'] == 'current') {
        $request['is_current_address'] = 1;
        $request['is_permanent_address'] = 0;
      } elseif ($request['is_address'] == 'permanent') {
        $request['is_permanent_address'] = 1;
        $request['is_current_address'] = 0;
      } else {
        $request['is_current_address'] = 1;
        $request['is_permanent_address'] = 1;
      }
      if ($request['is_pincode_exist'] === false) {
        $pincodeId = $addressRepo->getPincodeId((int)$request['pincode']);
        if (!$pincodeId) {
          return $this->responseJson(
            config('journey/http-status.failure.status'),
            'Invalid Pincode',
            config('journey/http-status.failure.code'),
            []
          );
        } else {
          $request['pincode_id'] = $pincodeId;
        }
      }
      $save = $addressRepo->save($request->all());
      if ($request['is_address'] == 'current' || $request['is_address'] == 'permanent') {
        $request['address1'] = $request['p_address_1'];
        $request['address2'] = $request['p_address_2'];
        $request['pincode_id'] = $request['p_pincode_id'];
        $request['city'] = $request['p_city'];
        $request['area'] = $request['p_area'];
        $request['state'] = $request['p_state'];
        if ($request['is_address'] == 'current') {
          $request['is_current_address'] = 0;
          $request['is_permanent_address'] = 1;
        } else {
          $request['is_permanent_address'] = 0;
          $request['is_current_address'] = 1;
        }
        $save = $addressRepo->save($request->all());
      }
      if ($save) {
        // save into Impression Table
        $breFlag = false;
        $request['next_stage'] = config('constants/productStepHandle.property-loan-details');
        $request['master_product_step_id'] = $this->getCurrentStepId($request);
        $impressionSave = $impressionRepo->save($request->all());
        if ($impressionSave->id) {
          $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
          $previousImpressionId = $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
          $request['previous_impression_id'] = $previousImpressionId->id ?? $impressionSave->id;
          $request['current_impression_id'] = $impressionSave->id;
          $applicationRepo->save($request->all());
          $logPushData = $request;
          $logPushData['cc_stage_handle'] = 'property-details';
          $logPushData['cc_sub_stage_handle'] = 'property-details-pending';
          $this->pushDataFieldTrackingLog($logPushData);
        }
        // existing property call BRE2
        $propertyData = $propRepo->view($request->all());
        if ($propertyData && $propertyData->is_existing_property == 1) {
          $existingproperty = $propertyData->is_existing_property;
          $empData = $empRepo->view($request->all());
          // Check property convertion status
          $updatedProductId =  $this->productIdMapping($request->all(), $propertyData);
          if ($updatedProductId) {
            $request['master_product_id'] = $updatedProductId;
          }
          $breFlag = true;
          if (
            $empData && $empData['is_income_proof_document_available'] != 0
            && $empData['salary_mode_id'] != 2 && $empData['total_experience'] != 1
          ) {
            $request['bre_type'] = config('constants/apiType.BRE_LEVEL_TWO');
            $request['stage'] = config('constants/apiSourcePage.ADDRESS_PAGE');
            $breTwoData = $this->prepareBREData($request);
            if ($breTwoData || gettype($breTwoData) == "string") {
              $breData = json_decode($breTwoData, true);
              if (isset($breData['Table1']) && $breData['Table1'][0]['IsDev'] == 'N') {
                $breFlag = true;
                $loanArray = array();
                $finalBreData = array();
                if ($breData) {
                  $request['next_stage'] = config('constants/productStepHandle.offer-details');
                  $request['master_product_step_id'] = $this->getCurrentStepId($request);
                  $impressionSave = $impressionRepo->save($request->all());
                  $request['is_bre_execute'] = true;
                  $applicationRepo->save($request->all());
                  $logPushData = $request;
                  $logPushData['cc_stage_handle'] = 'offer-details';
                  $logPushData['cc_sub_stage_handle'] = 'offer-details-final-voucher-page';
                  $this->pushDataFieldTrackingLog($logPushData);
                  foreach ($breData['Table'] as $bre) {
                    if ($bre['LnAmount'] != 0 && $bre['LnAmount'] > 750000) {
                      $loanArray[$bre['Tenure']] = $bre['LnAmount'];
                      $finalBreData[] = $bre;
                    }
                  }
                }
                $breResponse['actual_data'] = $finalBreData;
                if (count($breResponse['actual_data']) == 0) {
                  $request['next_stage'] = config('constants/productStepHandle.document-upload');
                  $request['master_product_step_id'] = $this->getCurrentStepId($request);
                  $impressionSave = $impressionRepo->save($request->all());
                  $request['is_bre_execute'] = false;
                  $request['bre2_loan_amount'] = 0;
                  $applicationRepo->save($request->all());
                  $reData['lead_id'] = $request->lead_id;
                  $reData['quote_id'] = $request->quote_id;
                  $reData['type'] = 'BRE2';
                  $existEligibile = $eligiRepo->getBre2Eligibile($reData);
                  if ($existEligibile) {
                    $eligiRepo->removeExistData($reData);
                  }
                  $logPushData = $request;
                  $logPushData['cc_stage_handle'] = 'document-upload';
                  $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                  $this->pushDataFieldTrackingLog($logPushData);
                }
              } elseif ($breTwoData == 'Connection timeout' || $breTwoData == 'Error:Contact Administator' || $breTwoData == 'Internal Server Error.') {
                $request['next_stage'] =  config('constants/productStepHandle.address-details');
                $request['master_product_step_id'] = $this->getCurrentStepId($request);
                $impressionRepo->save($request->all());
                $breFlag = false;
                $request['is_bre_execute'] = false;
                $request['bre2_loan_amount'] = 0;
                $applicationRepo->save($request->all());
                return $this->responseJson(
                  ADDRS_API_STATUS_CONNECTION_ERROR,
                  ADDRS_API_STATUS_CONNECTION_ERROR_MESSAGE,
                  ADDRS_API_STATUS_CONNECTION_ERROR_CODE,
                  []
                );
              } else {
                $request['next_stage'] = config('constants/productStepHandle.document-upload');
                $request['master_product_step_id'] = $this->getCurrentStepId($request);
                $impressionSave = $impressionRepo->save($request->all());
                $request['is_bre_execute'] = false;
                $request['bre2_loan_amount'] = 0;
                $applicationRepo->save($request->all());
                $breFlag = false;
                $reData['lead_id'] = $request->lead_id;
                $reData['quote_id'] = $request->quote_id;
                $reData['type'] = 'BRE2';
                $existEligibile =  $eligiRepo->getBre2Eligibile($reData);
                if ($existEligibile) {
                  $eligiRepo->removeExistData($reData);
                }
                $logPushData = $request;
                $logPushData['cc_stage_handle'] = 'document-upload';
                $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                $this->pushDataFieldTrackingLog($logPushData);
              }
            } elseif ($breTwoData == 'Connection timeout' || $breTwoData == 'Error:Contact Administator' || $breTwoData == 'Internal Server Error.') {
              $request['next_stage'] =  config('constants/productStepHandle.address-details');
              $request['master_product_step_id'] = $this->getCurrentStepId($request);
              $impressionRepo->save($request->all());
              $breFlag = false;
              $request['is_bre_execute'] = false;
              $request['bre2_loan_amount'] = 0;
              $applicationRepo->save($request->all());
              return $this->responseJson(
                ADDRS_API_STATUS_CONNECTION_ERROR,
                ADDRS_API_STATUS_CONNECTION_ERROR_MESSAGE,
                ADDRS_API_STATUS_CONNECTION_ERROR_CODE,
                []
              );
            }
          } else {
            $breFlag = false;
            $reData['lead_id'] = $request->lead_id;
            $reData['quote_id'] = $request->quote_id;
            $reData['type'] = 'BRE2';
            $existEligibile = $eligiRepo->getBre2Eligibile($reData);
            if ($existEligibile) {
              $eligiRepo->removeExistData($reData);
            }
            $request['next_stage'] = config('constants/productStepHandle.document-upload');
            $request['master_product_step_id'] = $this->getCurrentStepId($request);
            $impressionSave = $impressionRepo->save($request->all());
            $request['is_bre_execute'] = false;
            $request['bre2_loan_amount'] = 0;
            $applicationRepo->save($request->all());
            $logPushData = $request;
            $logPushData['cc_stage_handle'] = 'document-upload';
            $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
            $this->pushDataFieldTrackingLog($logPushData);
          }
        }
        return $this->responseJson(
          config('journey/http-status.success.status'),
          config('journey/http-status.success.message'),
          config('journey/http-status.success.code'),
          ['bre_flag' => $breFlag, 'is_existing_property' => $existingproperty]
        );
      } else {
        return $this->responseJson(
          config('journey/http-status.failure.status'),
          config('journey/http-status.failure.message'),
          config('journey/http-status.failure.code'),
          []
        );
      }
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("saveaddressDetail" . $throwable->__toString());
    }
  }
  /**
   * Retrieve Address Detail
   *
   */
  public function view(Request $request, AddressRepository $addressRepo)
  {
    try {
      $addressDetail = $addressRepo->view($request);
      return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $addressDetail);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressDetailsService - edit " . $throwable);
      return $this->responseJson(
        config('journey/http-status.error.status'),
        config('journey/http-status.error.message'),
        config('journey/http-status.error.code'),
        []
      );
    }
  }
  /**
   * Retrieve Address Detail
   *
   */
  public function fetchAddress(
    Request $request,
    AddressRepository $addressRepo,
    PersonalDetailRepository $personalDtRepo,
    EligibilityRepository $eligibleRepo,
    LeadRepository $leadRepo,
    ApplicationRepository $apRepo,
    BreLogRepository $breRepo

  ) {
    try {
      $appData = $apRepo->getAppDataByQuoteId($request['quote_id']);
      $reqData['quote_id'] = $request['quote_id'];
      if (isset($request['lead_id'])) {
        $reqData['lead_id'] = $request['lead_id'];
      } else {
        $reqData['lead_id'] = $appData->lead_id;
      }
      $panNumber = $personalDtRepo->getPanNumber($reqData);
      $reqData['pan'] = $panNumber;
      $addressData = $addressRepo->getAddressData($reqData);
      $eligibilityData = $eligibleRepo->getBre1Eligibile($reqData);
      if ($eligibilityData) {
        unset($eligibilityData['is_co_applicant']);
        unset($eligibilityData['loan_amount']);
        unset($eligibilityData['tenure']);
        unset($eligibilityData['type']);
      }
      $coApplicantData = $leadRepo->getCoApplicantData($reqData);
      $appData = $apRepo->getAppDataByQuoteId($request['quote_id']);
      $breCoApplicantData = null;
      if ($coApplicantData) {
        $requestData['lead_id'] = $coApplicantData['co_applicant_id'];
        $requestData['quote_id'] = $request['quote_id'];
        $breCoApplicantData = $eligibleRepo->getBre1Eligibile($requestData);
        if ($breCoApplicantData) {
          unset($breCoApplicantData['is_co_applicant']);
          unset($breCoApplicantData['loan_amount']);
          unset($breCoApplicantData['tenure']);
          unset($breCoApplicantData['type']);
        }
        $exeCoAppBreData = $breRepo->fetchBreCoApplicantOneData($reqData);
        $breCoApplicantData['is_co_app_eligible_show'] = false;
        if (
          $exeCoAppBreData
          && $exeCoAppBreData != null && $exeCoAppBreData != '' && isset($exeCoAppBreData->api_data)
        ) {
          $coAppBreData = json_decode($exeCoAppBreData->api_data, true);
          if (isset($coAppBreData['Table1']) && $coAppBreData['Table1'][0]['IsDev'] == 'N') {
            $breCoApplicantData['is_co_app_eligible_show'] = true;
          } else {
            $breCoApplicantData['is_co_app_eligible_show'] = false;
          }
        }
      }
      if (count($addressData) > 0) {
        $addressData['is_address_exist'] = true;
        $addressData['eligibility_check'] = $eligibilityData;
        $addressData['co_applicant_eligibility_check'] = $breCoApplicantData;
        $addressData['bre_status'] = $appData->is_bre_execute ?? 0;
        unset($addressData[0]['quote_id']);
        return $this->responseJson(
          config('journey/http-status.success.status'),
          config('journey/http-status.success.message'),
          config('journey/http-status.success.code'),
          [$addressData]
        );
      } else {
        if (array_key_exists('pan', $reqData) && $reqData['pan'] != null) {
          $reqAddress = array();
          $karzaAddress = $addressRepo->viewKarzaHistroy($reqData);
          $viewKarzaHistroy['karza_address'] = null;
          if ($karzaAddress && isset($karzaAddress->api_data)) {
            $personalData = $personalDtRepo->view($reqData);
            $reqAddress = $karzaAddress->api_data;
            if ($personalData && $personalData->dob && isset($reqAddress['pandob'])) {
              $dobFlag = $this->dobCheckAddressDisplay($personalData->dob, $reqAddress['pandob']);
              if ($dobFlag && isset($reqAddress['panNo'])) {
                unset($reqAddress['requestId']);
                unset($reqAddress['caseId']);
                unset($reqAddress['firstName']);
                unset($reqAddress['lastName']);
                unset($reqAddress['panNo']);
                unset($reqAddress['panAadhaarLinked']);
                unset($reqAddress['panGender']);
                unset($reqAddress['pandob']);
                unset($reqAddress['source']);
                unset($reqAddress['panName']);
                $viewKarzaHistroy['karza_address'] = $reqAddress;
              }
            }
          }
          $viewKarzaHistroy['is_address_exist'] = false;
          $viewKarzaHistroy['eligibility_check'] = $eligibilityData;
          $viewKarzaHistroy['co_applicant_eligibility_check'] = $breCoApplicantData;
          $viewKarzaHistroy['bre_status'] = $appData->is_bre_execute ?? 0;
          return $this->responseJson(
            config('journey/http-status.success.status'),
            config('journey/http-status.success.message'),
            config('journey/http-status.success.code'),
            $viewKarzaHistroy
          );
        } else {
          return $this->responseJson(
            config('journey/http-status.failure.status'),
            config('journey/http-status.failure.message'),
            config('journey/http-status.failure.code'),
            []
          );
        }
      }
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressDetailsService -  fetchAddress " . $throwable);
      return $this->responseJson(
        config('journey/http-status.error.status'),
        config('journey/http-status.error.message'),
        config('journey/http-status.error.code'),
        []
      );
    }
  }
  /**
   * dob check for karza address display
   *
   */
  public function dobCheckAddressDisplay($reqDob, $karzaDob)
  {
    try {
      $givenDob = Carbon::createFromFormat('Y-m-d', $reqDob);
      $karzaFetchDob = Carbon::createFromFormat('Y-m-d', $karzaDob);
      if ($givenDob == $karzaFetchDob) {
        return true;
      }
      return false;
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressDetailsService -  dobCheckAddressDisplay " . $throwable);
      return $this->responseJson(
        config('journey/http-status.error.status'),
        config('journey/http-status.error.message'),
        config('journey/http-status.error.code'),
        []
      );
    }
  }
  /**
   * list address  Data
   *
   */
  public function list(Request $request, AddressRepository $addressRepo,)
  {
    try {
      $addressList = $addressRepo->list($request);
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $addressList
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressService list : %s", $throwable->__toString());
    }
  }
  public function exportAddress(Request $request)
  {
    try {
      $repository = new AddressRepository();
      $data['methodName'] = 'list';
      $data['fileName'] = 'Address-Report-';
      $data['moduleName'] = 'Address';
      return $this->exportData($request, $repository, $data);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressService exportAddress : %s", $throwable->__toString());
    }
  }
  /**
   * existing property address product mapping
   *
   */
  public function productIdMapping($request, $propertyData)
  {
    try {
      $applicationRepo = new ApplicationRepository();
      $propertyRepo = new PropertyLoanDetailRepository();
      $proRepo = new MasterProductRepository();
      $appData = $applicationRepo->getAppData($request['quote_id']);
      $existingCustomer = true;
      $propertySelected = true;
      $loanFree = false;
      if (isset($propertyData['property_type_id'])) {
        $propertyDt = $propertyRepo->getPropertyType($propertyData['property_type_id']);
        $proHandle = $propertyDt['handle'];
      }
      if (isset($propertyData['is_property_loan_free'])) {
        $loanFree = $propertyData['is_property_loan_free'] == 1 ? true : false;
      }
      switch ($appData['originMasterProductData']['code']) {
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
          //   LAPTopup or HLTopup
        case config('constants/productCode.HLTopup'):
        case config('constants/productCode.LAPTopup'):

          if ($existingCustomer === false && $proHandle == "residential" && $loanFree === false) {
            return $proRepo->masterProductIdFetch(config('constants/productCode.LAPResi'));
          } elseif ($existingCustomer === false && $proHandle == "residential" && $loanFree === true) {
            return $proRepo->masterProductIdFetch(config('constants/productCode.HLBTTopup'));
          } elseif (
            $existingCustomer === false
            && ($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === false
          ) {
            return $proRepo->masterProductIdFetch(config('constants/productCode.LAPTopup'));
          } elseif (
            $existingCustomer === false
            && ($proHandle == "commercial" || $proHandle == "industrial") && $loanFree === true
          ) {
            return $proRepo->masterProductIdFetch(config('constants/productCode.LAPBTTopup'));
          } elseif (
            $existingCustomer === true
            && $propertySelected === true && ($proHandle == "commercial" || $proHandle == "industrial")
          ) {
            return $proRepo->masterProductIdFetch(config('constants/productCode.LAPTopup'));
          } elseif ($existingCustomer === true && $propertySelected === true &&  $proHandle == "residential") {
            return $proRepo->masterProductIdFetch(config('constants/productCode.HLTopup'));
          }
          break;
      }
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PropertyLoanDetailService -  productIdMapping " . $throwable);
    }
  }
}
