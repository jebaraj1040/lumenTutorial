<?php

namespace App\Services\HousingJourney;

use Illuminate\Http\Client\HttpClientException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Services\Service;
use GuzzleHttp\Exception\ClientException;
use Exception;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\BreLogRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Utils\CrmTrait;
use App\Entities\HousingJourney\HjApplication;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use App\Repositories\HousingJourney\PaymentTransactionRepository;

class ImpressionService extends Service
{
     use CrmTrait;
     private $repo;
     /**
      * Create a new Service instance.
      *
      */
     public function stepperStage(
          Request $request,
          ImpressionRepository $impRepo,
          EligibilityRepository $eligRp,
          ApplicationRepository $appRepo,
          BreLogRepository $breLogRepo,
          LeadRepository $leadRepo,
          PropertyLoanDetailRepository $propRepo
     ) {
          try {
               $paymentRepo = new PaymentTransactionRepository();
               if ($request->bearerToken()) {
                    // get quote_id from session_auth_token
                    $request['quote_id'] = HjApplication::where('auth_token', $request->bearerToken())->value('quote_id');
                    $responseData['previous_impression'] = $impRepo->fetchCurrentImpressionId($request);
                    $initialProductID = $impRepo->fetchInitalImpProductId($request['quote_id']);
                    $initProductType = "";
                    if ($initialProductID) {
                         $initProductName =   $impRepo->getProductName($initialProductID);
                         $initProductTypeData =   $impRepo->getProductType($initialProductID);
                         $initProductType = $initProductTypeData['productType']['name'];
                         $initProductCode = $impRepo->getProductCode($initialProductID);
                    } else {
                         $initProductName =  $impRepo->getProductName($responseData['previous_impression']['master_product_id']);
                         $initProductTypeData =   $impRepo->getProductType($responseData['previous_impression']['master_product_id']);
                         $initProductType = $initProductTypeData['productType']['name'];
                         $initProductCode = $impRepo->getProductCode($initialProductID);
                    }
                    $leadId = $responseData['previous_impression']['lead_id'];
                    $responseData['previous_impression']['base_product_name'] = $initProductName;
                    $responseData['previous_impression']['base_product_type'] = $initProductType;
                    $responseData['previous_impression']['base_product_code'] = $initProductCode;
                    $responseData['previous_impression']['base_product_id'] = $initialProductID;
                    $responseData['lead_picode_data'] = $impRepo->getLeadData($leadId);
                    $responseData['product_type'] = $impRepo->getProductDetails($responseData['previous_impression']['master_product_id']);
                    $responseData['app_data'] = $appRepo->getPaymentDataByQuoteId($request);
                    $responseData['app_data']['custom_string'] =  $responseData['app_data']['quote_id'];
                    $request['payment_transaction_id'] = $responseData['app_data']['payment_transaction_id'];
                    $responseData['co_applicant_id'] = $appRepo->getCoApplicantId($request);

                    $responseData['payment_transaction'] = $paymentRepo->fetchTransactionData($request);
                    $currentHandle = config('constants/productStepHandle.' . $request['current_url']);
                    $eligibilityStatus = false;
                    $reqData['lead_id'] = $leadId;
                    $reqData['quote_id'] = $request['quote_id'];
                    $leadEligibility = $eligRp->getBre1Eligibile($reqData);
                    $propertyDetail = $propRepo->view($reqData);
                    if ($leadEligibility &&  $leadEligibility->is_deviation == 'N') {
                         $eligibilityStatus = true;
                    }
                    $coApEligiStatus = false;
                    $coApplicantData = $leadRepo->getCoApplicantData($reqData);
                    if ($coApplicantData) {
                         $reqData['lead_id'] = $coApplicantData->co_applicant_id;
                         $coAppEligibility = $eligRp->getBre1Eligibile($reqData);
                         if ($coAppEligibility && $coAppEligibility->is_deviation == 'N') {
                              $coApEligiStatus = true;
                         }
                    }
                    $responseData['current_step_id'] = $impRepo->getCurrentStepId($currentHandle);
                    $responseData['stage_percentage'] = $impRepo->getStagePercentage($currentHandle);
                    $responseData['is_co_applicant'] =  $leadEligibility->is_co_applicant ?? 0;
                    $responseData['is_property_identified'] = $impRepo->getPropertyIdentified($request['quote_id']);
                    $appData = $appRepo->getQuoteIdDetails($request);
                    $responseData['loan_amount'] = $appData['loan_amount'];
                    $responseData['bre_status'] = $appData['is_bre_execute'];
                    $responseData['is_eligible'] = $eligibilityStatus;
                    $responseData['co_app_is_eligibile'] = $coApEligiStatus;
                    $responseData['is_existing_property'] =
                         $propertyDetail && $propertyDetail->is_existing_property == 1 ? 1 : 0;

                    unset($responseData['lead_picode_data']['pincodeData']['id']);
                    unset($responseData['lead_picode_data']['id']);
                    unset($responseData['app_data']['session_auth_token']);
                    unset($responseData['app_data']['quote_id']);
                    unset($responseData['app_data']['cc_quote_id']);
                    unset($responseData['app_data']['auth_token']);
                    unset($responseData['app_data']['digital_transaction_no']);
                    unset($responseData['app_data']['created_at']);
                    unset($responseData['app_data']['updated_at']);
                    unset($responseData['app_data']['mobile_number']);
                    unset($responseData['app_data']['cc_token']);
                    unset($responseData['previous_impression']['quote_id']);
                    unset($responseData['previous_impression']['created_at']);
                    unset($responseData['previous_impression']['updated_at']);
                    unset($responseData['previous_impression']['id']);
                    unset($responseData['previous_impression']['master_product_step_id']);
                    unset($responseData['previous_impression']['stepName']['updated_at']);
                    unset($responseData['previous_impression']['stepName']['created_at']);
                    unset($responseData['previous_impression']['stepName']['is_active']);
                    unset($responseData['previous_impression']['stepName']['id']);
                    unset($responseData['product_type']['id']);
                    unset($responseData['product_type']['productDetails']['id']);
                    unset($responseData['product_type']['productType']['id']);
                    return $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         $responseData
                    );
               }
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("ImpressionService stepperStage " . $throwable->__toString());
          }
     }
     public function __construct(ImpressionRepository $repo)
     {
          $this->repo = $repo;
     }
     /**
      * list impression details,
      *
      * @param
      * @return void
      */
     public function list(Request $request)
     {
          try {
               $impressionList = $this->repo->list($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $impressionList
               );
          } catch (Throwable | Exception | ClientException $throwable) {
               Log::info("ImpressionService -  list " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
     /**
      * Export Impressions.
      *
      * @param
      * @return void
      */
     public function exportImpression(Request $request)
     {
          try {
               $repository = new ImpressionRepository();
               $data['methodName'] = 'list';
               $data['fileName'] = 'Impression-Report-';
               $data['moduleName'] = 'Impression';
               return $this->exportData($request, $repository, $data);
          } catch (Throwable | ClientException $throwable) {
               Log::info("ImpressionService -  exportImpression " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
}
