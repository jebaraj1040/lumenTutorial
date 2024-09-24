<?php

namespace App\Services\HousingJourney;


use Exception;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HjMasterPincodeImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\MasterStateRepository;
use App\Repositories\HousingJourney\MasterApiLogRepository;

class MasterStateService extends Service
{
      /**
       * property type insert
       *
       */
      public function list(Request $request, MasterStateRepository $stateMasterRepo, MasterApiLogRepository $masterApiLogRepo)
      {
            try {
                  $requestUrl = $request->url . $request->path();
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Source'] = config('constants/apiSource.WEB');
                  $requestData['customHeader']['X-Api-Type'] = config('constants/apiType.STATE_LIST');
                  $requestData['customHeader']['X-Api-Status'] = config('constants/apiStatus.INIT');
                  $requestData['customHeader']['X-Api-Url'] = $requestUrl;
                  $requestData['request'] = $request;
                  $masterApiLogData = $masterApiLogRepo->save($requestData);
                  $request['master_log_id'] = $masterApiLogData['id'];
                  return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $stateMasterRepo->list());
            } catch (Throwable | HttpClientException $throwable) {
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Status'] = config('constants/apiStatus.FAILURE');
                  $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        []
                  );
                  $masterApiLogRepo->save($requestData);
                  Log::info("MasterStateService list" . $throwable->__toString());
            }
      }
}
