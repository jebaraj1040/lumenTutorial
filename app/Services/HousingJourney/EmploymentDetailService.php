<?php

namespace App\Services\HousingJourney;

use Exception;
use Throwable;
use App\Utils\CrmTrait;
use App\Utils\CoreTrait;
use App\Services\Service;
use App\Utils\CommonTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\BreLogRepository;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\LeadRepository;

// Define constant
define('PRODUCT_STEP_HANDLE_ADDRESS_DETAILS', config('constants/productStepHandle.address-details'));
define('EMP_API_STATUS_SUCCESS', config('journey/http-status.success.status'));
define('EMP_API_STATUS_SUCCESS_MESSAGE', config('journey/http-status.success.message'));
define('EMP_API_STATUS_SUCCESS_CODE', config('journey/http-status.success.code'));
define('EMP_API_STATUS_ERROR', config('journey/http-status.error.status'));
define('EMP_API_STATUS_ERROR_MESSAGE', config('journey/http-status.error.message'));
define('EMP_API_STATUS_ERROR_CODE', config('journey/http-status.error.code'));
define('EMP_API_STATUS_FAILURE', config('journey/http-status.failure.status'));
define('EMP_API_STATUS_FAILURE_MESSAGE', config('journey/http-status.failure.message'));
define('EMP_API_STATUS_FAILURE_CODE', config('journey/http-status.failure.code'));
define('EMP_API_STATUS_CONNECTION_ERROR', config('journey/http-status.connection-error.status'));
define('EMP_API_STATUS_CONNECTION_ERROR_MESSAGE', config('journey/http-status.connection-error.message'));
define('EMP_API_STATUS_CONNECTION_ERROR_CODE', config('journey/http-status.connection-error.code'));

class EmploymentDetailService extends Service
{
  use CrmTrait;
  use CoreTrait;
  use CommonTrait;
  /**
   * Save Employment Detail
   *
   */
  public function save(
    Request $request,
    EmploymentDetailRepository $employmentDetailsRepo,
    ImpressionRepository $impressionRepo,
    ApplicationRepository $applicationRepo,
    EligibilityRepository $eligibleRepo,
    LeadRepository $leadRepo
  ) {
    try {
      $employmentType = $employmentDetailsRepo->getEmploymentTypeHandle($request['employment_type']);
      if ($employmentType == 'salaried') {
        $alphaRequried = 'required | regex:/^[a-zA-Z0-9]+$/';
        $rules = [
          "employment_type" => "required",
          "company_name" => ['required', 'regex:/^[A-Za-z0-9&\'\|\:\+\-\.\+\(\)\, ]+$/'],
          "current_experience" =>  $alphaRequried,
          "total_experience" => $alphaRequried,
          "net_monthly_salary" => "required | numeric",
          "is_income_proof_document_available" => $alphaRequried,
          "mode_of_salary" => $alphaRequried
        ];
      } elseif ($employmentType == 'self-employed-non-professional') {
        $alphaRequried = 'required | regex:/^[a-zA-Z0-9]+$/';
        $rules = [
          "employment_type" => "required",
          "company_name" => ['required', 'regex:/^[A-Za-z0-9&\'\|\:\+\-\.\+\(\)\, ]+$/'],
          "business_vintage" => $alphaRequried,
          "net_monthly_sales" => "required | numeric",
          "net_monthly_profit" => "required | numeric",
          "is_income_proof_document_available" => $alphaRequried,
          "constitution_type" => $alphaRequried,
          "industry_segment" => $alphaRequried,
          "industry_type" => $alphaRequried
        ];
      } elseif ($employmentType == 'self-employed-professional') {
        $alphaRequried = 'required | regex:/^[a-zA-Z0-9]+$/';
        $rules = [
          "employment_type" => "required",
          "professional_type" => $alphaRequried,
          "gross_receipt" => "required | numeric",
          "is_income_proof_document_available" => $alphaRequried,
        ];
      }
      $validator = Validator::make($request->all(), $rules);
      if ($validator->fails()) {
        return $validator->errors();
      }
      // save into employmentDetail Table
      $request['employment_type_id'] = $request['employment_type'];
      $request['salary_mode_id'] = $request['mode_of_salary'];
      $request['constitution_type_id'] = $request['constitution_type'];
      $request['industry_type_id'] = $request['industry_type'];
      $request['industry_segment_id'] = $request['industry_segment'];
      $request['professional_type_id'] = $request['professional_type'];
      $save = $employmentDetailsRepo->employmentDetailsSave($request->all());
      if ($save) {
        $request['next_stage'] = config('constants/productStepHandle.eligibility');
        $request['master_product_step_id'] = $this->getCurrentStepId($request);
        // save into Impression Table
        $impressionSave = $impressionRepo->save($request->all());

        if ($impressionSave->id) {
          $previousImpression = $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
          $request['previous_impression_id'] = $previousImpression->id ?? $impressionSave->id;
          $request['current_impression_id'] = $impressionSave->id;
          // save into Application Table
          $applicationRepo->save($request->all());
          $request['loan_product_id'] =  $impressionSave->master_product_id;
          // Prepare BRE Level one Data
          $breFlag = true;
          if (
            $request['is_income_proof_document_available'] != 0
            && $request['salary_mode_id'] != 2 && $request['total_experience'] != 1
          ) {
            $breFlag = true;
            $request['bre_type'] = config('constants/apiType.BRE_LEVEL_ONE');
            $request['stage'] = config('constants/apiSourcePage.EMPLOYMENT_DETAIL_PAGE');
            $resData = $this->prepareBREData($request);
            if ($resData && $resData != null && $resData != '' && gettype($resData) == 'string') {
              $request['is_bre_execute'] = true;
              $breData = json_decode($resData, true);
              if (isset($breData['Table1']) && $breData['Table1'][0]['IsDev'] == 'N') {
                $breFlag = true;
                $lnAmounts = array_column($breData['Table'], 'LnAmount');
                $allLnAmountsZero = array_sum($lnAmounts) == 0;
                if ($allLnAmountsZero) {
                  $breFlag = false;
                  $reData['lead_id'] = $request->lead_id;
                  $reData['quote_id'] = $request->quote_id;
                  $reData['type'] = 'BRE1';
                  $existEligibile = $eligibleRepo->view($reData);
                  if (
                    $existEligibile['eligibility_data'] &&
                    $existEligibile['eligibility_data']['type'] == 'BRE1'
                  ) {
                    $eligibleRepo->removeExistData($reData);
                    $coApplicantData = $leadRepo->getCoApplicantData($reData);
                    if ($coApplicantData) {
                      $reData['lead_id'] = $coApplicantData->co_applicant_id;
                      $eligibleRepo->removeExistData($reData);
                    }
                  }
                  $logPushData = $request;
                  $logPushData['cc_stage_handle'] = 'address-details';
                  $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                  $this->pushDataFieldTrackingLog($logPushData);
                  $request['next_stage'] = PRODUCT_STEP_HANDLE_ADDRESS_DETAILS;
                  $request['master_product_step_id'] = $this->getCurrentStepId($request);
                  $impressionRepo->save($request->all());
                  $request['bre1_loan_amount'] = 0;
                  $request['bre1_updated_loan_amount'] = 0;
                  $request['is_bre_execute'] = false;
                  $applicationRepo->save($request->all());
                } else {
                  $logPushData = $request;
                  $logPushData['cc_stage_handle'] = 'eligibility-applicant';
                  $logPushData['cc_sub_stage_handle'] = 'eligibility-applicant-bre-success';
                  $this->pushDataFieldTrackingLog($logPushData);
                }
              } elseif ($resData == 'Connection timeout' || $resData == 'Error:Contact Administator' || $resData == 'Internal Server Error.') {
                $request['next_stage'] =  config('constants/productStepHandle.employment-details');
                $request['master_product_step_id'] = $this->getCurrentStepId($request);
                $impressionRepo->save($request->all());
                $breFlag = false;
                $request['is_bre_execute'] = false;
                $request['bre1_loan_amount'] = 0;
                $request['bre1_updated_loan_amount'] = 0;
                $applicationRepo->save($request->all());
                return $this->responseJson(
                  EMP_API_STATUS_CONNECTION_ERROR,
                  EMP_API_STATUS_CONNECTION_ERROR_MESSAGE,
                  EMP_API_STATUS_CONNECTION_ERROR_CODE,
                  []
                );
              } else {
                $breFlag = false;
                $reData['lead_id'] = $request->lead_id;
                $reData['quote_id'] = $request->quote_id;
                $reData['type'] = 'BRE1';
                $existEligibile = $eligibleRepo->view($reData);
                if (
                  $existEligibile['eligibility_data'] &&
                  $existEligibile['eligibility_data']['type'] == 'BRE1'
                ) {
                  $eligibleRepo->removeExistData($reData);
                  $coApplicantData = $leadRepo->getCoApplicantData($reData);
                  if ($coApplicantData) {
                    $reData['lead_id'] = $coApplicantData->co_applicant_id;
                    $eligibleRepo->removeExistData($reData);
                  }
                }
                $request['next_stage'] = PRODUCT_STEP_HANDLE_ADDRESS_DETAILS;
                $request['master_product_step_id'] = $this->getCurrentStepId($request);
                $impressionRepo->save($request->all());
                $request['bre1_loan_amount'] = 0;
                $request['bre1_updated_loan_amount'] = 0;
                $request['is_bre_execute'] = false;
                $logPushData = $request;
                $logPushData['cc_stage_handle'] = 'address-details';
                $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
                $this->pushDataFieldTrackingLog($logPushData);
                $applicationRepo->save($request->all());
              }
              $applicationRepo->save($request->all());
              return $this->responseJson(
                EMP_API_STATUS_SUCCESS,
                EMP_API_STATUS_SUCCESS_MESSAGE,
                EMP_API_STATUS_SUCCESS_CODE,
                ['bre_flag' => $breFlag]
              );
            } elseif ($resData == 'Connection timeout' || $resData == 'Error:Contact Administator' || $resData == 'Internal Server Error.') {
              $request['next_stage'] =  config('constants/productStepHandle.employment-details');
              $request['master_product_step_id'] = $this->getCurrentStepId($request);
              $impressionRepo->save($request->all());
              $breFlag = false;
              $request['is_bre_execute'] = false;
              $request['bre1_loan_amount'] = 0;
              $request['bre1_updated_loan_amount'] = 0;
              $applicationRepo->save($request->all());
              return $this->responseJson(
                EMP_API_STATUS_CONNECTION_ERROR,
                EMP_API_STATUS_CONNECTION_ERROR_MESSAGE,
                EMP_API_STATUS_CONNECTION_ERROR_CODE,
                []
              );
            } else {
              $request['next_stage'] = PRODUCT_STEP_HANDLE_ADDRESS_DETAILS;
              $request['master_product_step_id'] = $this->getCurrentStepId($request);
              $impressionRepo->save($request->all());
              $breFlag = false;
              $request['is_bre_execute'] = false;
              $request['bre1_loan_amount'] = 0;
              $request['bre1_updated_loan_amount'] = 0;
              $applicationRepo->save($request->all());
              return $this->responseJson(
                EMP_API_STATUS_ERROR,
                EMP_API_STATUS_ERROR_MESSAGE,
                EMP_API_STATUS_ERROR_CODE,
                ['bre_flag' => $breFlag]
              );
            }
          } else {
            $request['next_stage'] = PRODUCT_STEP_HANDLE_ADDRESS_DETAILS;
            $request['master_product_step_id'] = $this->getCurrentStepId($request);
            $impressionRepo->save($request->all());
            $request['is_bre_execute'] = false;
            $request['bre1_loan_amount'] = 0;
            $request['bre1_updated_loan_amount'] = 0;
            $applicationRepo->save($request->all());
            $breFlag = false;
            $reData['lead_id'] = $request->lead_id;
            $reData['quote_id'] = $request->quote_id;
            $reData['type'] = 'BRE1';
            $existEligibile = $eligibleRepo->view($reData);
            if (
              $existEligibile['eligibility_data'] &&
              $existEligibile['eligibility_data']['type'] == 'BRE1'
            ) {
              $eligibleRepo->removeExistData($reData);
              $coApplicantData = $leadRepo->getCoApplicantData($reData);
              if ($coApplicantData) {
                $reData['lead_id'] = $coApplicantData->co_applicant_id;
                $eligibleRepo->removeExistData($reData);
              }
            }
            $logPushData = $request;
            $logPushData['cc_stage_handle'] = 'address-details';
            $logPushData['cc_sub_stage_handle'] = 'address-details-pending';
            $this->pushDataFieldTrackingLog($logPushData);
            return $this->responseJson(
              EMP_API_STATUS_SUCCESS,
              EMP_API_STATUS_SUCCESS_MESSAGE,
              EMP_API_STATUS_SUCCESS_CODE,
              ['bre_flag' => $breFlag]
            );
          }
        }
      } else {
        return $this->responseJson(EMP_API_STATUS_ERROR, EMP_API_STATUS_ERROR_MESSAGE, EMP_API_STATUS_ERROR_CODE, []);
      }
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("saveEmploymentDetails" . $throwable->__toString());
    }
  }

  /**
   * Retrieve Employment Detail
   *
   */
  public function view(Request $request)
  {
    try {
      $empRepo = new EmploymentDetailRepository();
      $appRepo = new ApplicationRepository();
      $breLog = new BreLogRepository();
      $redData['quote_id'] = $request->quote_id;
      $appData = $appRepo->getQuoteIdDetails($redData);
      if (!$appData && empty($appData)) {
        return $this->responseJson(
          EMP_API_STATUS_FAILURE,
          EMP_API_STATUS_FAILURE_MESSAGE,
          EMP_API_STATUS_FAILURE_CODE,
          []
        );
      }
      $redData['lead_id'] = $appData['lead_id'];
      $employmentDetail = $empRepo->view($redData);
      if (!$employmentDetail && empty($employmentDetail)) {
        return $this->responseJson(
          EMP_API_STATUS_FAILURE,
          EMP_API_STATUS_FAILURE_MESSAGE,
          EMP_API_STATUS_FAILURE_CODE,
          []
        );
      }
      $redData['lead_id'] = $appData['lead_id'];
      $employmentDetail['industry_type_data'] = $empRepo->getIndustryType($employmentDetail->industry_segment_id);
      $breData = $breLog->fetchBreOneData($redData);
      if ($breData && $breData != null && $breData != '' && isset($breData->api_data)) {
        $breDataResponse = json_decode($breData->api_data, true);
        $employmentDetail['eligibility_check'] = $breDataResponse['Table1'] ?? null;
      } else {
        $employmentDetail['eligibility_check'] = null;
      }
      return $this->responseJson(EMP_API_STATUS_SUCCESS, EMP_API_STATUS_SUCCESS_MESSAGE, EMP_API_STATUS_SUCCESS_CODE, $employmentDetail);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailsService -  view " . $throwable);
      return $this->responseJson(
        EMP_API_STATUS_ERROR,
        EMP_API_STATUS_ERROR_MESSAGE,
        EMP_API_STATUS_ERROR_CODE,
        []
      );
    }
  }
  public function list(Request $request, EmploymentDetailRepository $empDetailRepo,)
  {
    try {
      $employmentDetailList = $empDetailRepo->list($request);
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $employmentDetailList
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailService list : %s", $throwable->__toString());
    }
  }
  public function exportEmploymentDetail(Request $request)
  {
    try {
      $repository = new EmploymentDetailRepository();
      $data['methodName'] = 'list';
      $data['fileName'] = 'EmploymentDetail-Report-';
      $data['moduleName'] = 'EmploymentDetail';
      return $this->exportData($request, $repository, $data);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailService exportEmploymentDetail : %s", $throwable->__toString());
    }
  }
  /**
   * Retrieve Industry type
   *
   */
  public function fetchIndustryType(Request $request, EmploymentDetailRepository $empDetailRepo)
  {
    try {
      $industryType = $empDetailRepo->getIndustryType($request['indusSegId']);
      if ($industryType) {
        return $this->responseJson(
          EMP_API_STATUS_SUCCESS,
          EMP_API_STATUS_SUCCESS_MESSAGE,
          EMP_API_STATUS_SUCCESS_CODE,
          $industryType
        );
      } else {
        return $this->responseJson(
          EMP_API_STATUS_FAILURE,
          EMP_API_STATUS_FAILURE_MESSAGE,
          EMP_API_STATUS_FAILURE_CODE,
          []
        );
      }
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailService fetchIndustryType : %s", $throwable->__toString());
    }
  }
}
