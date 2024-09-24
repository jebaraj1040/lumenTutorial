<?php

namespace App\Services\HousingJourney;

use Throwable;
use Exception;
use App\Services\Service;
use App\Utils\CoreTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\PaymentRepository;
use App\Utils\CrmTrait;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Validator;

class ApplicationService extends Service
{
      /**
       * Call trait helper function
       *
       * @param
       * @return mixed
       */
      use CrmTrait;
      use CoreTrait;
      private $repo;
      /**
       * Create a new Service instance.
       *
       * @param
       * @return void
       */
      public function __construct(ApplicationRepository $repo)
      {
            $this->repo = $repo;
      }
      /**
       * Get application data by quote Id.
       *
       * @param  $request
       * @return mixed
       */
      public function getApplicationDataByQuoteId(
            Request $request,
            ApplicationRepository $applicationRepo,
            LeadRepository $leadRepo,
            PersonalDetailRepository $personalDetailRepo
      ) {
            try {
                  $rules = [
                        "quote_id" => "required"
                  ];
                  $validator = $this->validator($request->all(), $rules);
                  if ($validator !== false) {
                        return $validator;
                  }
                  $applicationData = [];
                  if (empty($applicationDetails = $applicationRepo->getQuoteIdDetails($request->all())) === false) {
                        $applicationData = $applicationDetails;
                        if (!empty($applicationDetails['lead_id'])) {
                              $applicationData['lead_details'] =
                                    $leadRepo->getLeadDetailsById($applicationDetails['lead_id']);
                              $applicationData['personal_details'] =
                                    $personalDetailRepo->getLeadDetailsById($applicationDetails['lead_id']);
                        }
                        return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              $applicationData
                        );
                  }
                  return $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        []
                  );
            } catch (Throwable | Exception | ClientException $throwable) {
                  Log::info("getApplicationDataByQuoteId" . $throwable->__toString());
                  return $this->responseJson(
                        config('journey/http-status.error.status'),
                        config('journey/http-status.error.message'),
                        config('journey/http-status.error.code'),
                        []
                  );
            }
      }
      /**
       * Get quote Id details.
       *
       * @param  $request
       * @return mixed
       */
      public function getQuoteIdDetails(
            Request $request,
            ApplicationRepository $applicationRepo,
            PaymentRepository $paymentRepo
      ) {
            try {
                  $rules = [
                        "quote_id" => "required",
                        "application_status" => "required"
                  ];
                  $validator = $this->validator($request->all(), $rules);
                  if ($validator !== false) {
                        return $validator;
                  }
                  $applicationData = [];
                  if (empty($applicationDetails = $applicationRepo->getQuoteIdDetails($request->all())) === false) {
                        $applicationData =  $applicationDetails;
                        return $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              $applicationData
                        );
                  }
                  return $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        []
                  );
            } catch (Throwable | Exception | ClientException $throwable) {
                  Log::info("getQuoteIdDetails" . $throwable->__toString());
                  return $this->responseJson(
                        config('journey/http-status.error.status'),
                        config('journey/http-status.error.message'),
                        config('journey/http-status.error.code'),
                        []
                  );
            }
      }
      /**
       * list application details.
       *
       * @param
       * @return void
       */
      public function list(Request $request)
      {
            try {
                  $applicationList = $this->repo->list($request);
                  return $this->responseJson(
                        config('crm/http-status.success.status'),
                        config('crm/http-status.success.message'),
                        config('crm/http-status.success.code'),
                        $applicationList
                  );
            } catch (Throwable | Exception | ClientException $throwable) {
                  Log::info("ApplicationService -  list " . $throwable);
                  return $this->responseJson(
                        config('journey/http-status.error.status'),
                        config('journey/http-status.error.message'),
                        config('journey/http-status.error.code'),
                        []
                  );
            }
      }
      /**
       * Export Application details.
       *
       * @param
       * @return void
       */
      public function exportApplication(Request $request)
      {
            try {
                  $repository = new ApplicationRepository();
                  $data['methodName'] = 'list';
                  $data['fileName'] = 'Application-Report-';
                  $data['moduleName'] = 'Application';
                  return $this->exportData($request, $repository, $data);
            } catch (Throwable | ClientException $throwable) {
                  Log::info("ApplicationService -  exportApplication " . $throwable);
                  return $this->responseJson(
                        config('journey/http-status.error.status'),
                        config('journey/http-status.error.message'),
                        config('journey/http-status.error.code'),
                        []
                  );
            }
      }

      /**
       * Update is Traversed field 
       *
       */
      public function updateTraversedStatus(Request $request, ApplicationRepository $appRepo)
      {
            try {
                  $rules = [
                        "is_traversed" => "required",
                  ];
                  $validator = Validator::make($request->all(), $rules);
                  if ($validator->fails()) {
                        return $validator->errors();
                  }
                  $applicationData = $appRepo->getApplicationDetails($request['quote_id']);

                  if ($applicationData && $applicationData['is_paid']) {
                        $reqData['lead_id'] = $applicationData['lead_id'];
                        $reqData['quote_id'] = $applicationData['quote_id'];
                        $reqData['is_traversed'] = true;
                        $appRepo->save($reqData);
                        return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), []);
                  } else {
                        return $this->responseJson(config('journey/http-status.bad-request.status'), config('journey/http-status.bad-request.message'), config('journey/http-status.bad-request.code'), []);
                  }
            } catch (Throwable | HttpClientException $throwable) {
                  Log::info("ApplicationService -  updateTraversedStatus " . $throwable);
                  return $this->responseJson(
                        config('journey/http-status.error.status'),
                        config('journey/http-status.error.message'),
                        config('journey/http-status.error.code'),
                        []
                  );
            }
      }
}
