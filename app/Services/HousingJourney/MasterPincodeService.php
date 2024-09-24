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
use App\Repositories\HousingJourney\MasterPincodeRepository;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use App\Jobs\MasterPincodeExport;

class MasterPincodeService extends Service
{
      use CrmTrait;
      /**
       * Save Import File
       *
       */
      private $masterpincodeRepo;
      use CrmTrait;
      public function __construct(MasterPincodeRepository $masterpincodeRepo)
      {
            $this->masterpincodeRepo = $masterpincodeRepo;
      }
      public function import(Request $request, MasterApiLogRepository $masterApiLogRepo)
      {
            $file = $request->file('file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/excel'), $filename);
            $path = storage_path('app/excel/' . $filename);
            $requestUrl = $request->url . $request->path();
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PINCODE_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogRepo->save($requestData);
            Excel::import(new HjMasterPincodeImport, $path);
            return $this->responseJson(
                  config('journey/http-status.success.status'),
                  config('journey/http-status.success.message'),
                  config('journey/http-status.success.code'),
                  []
            );
      }
      public function editpincode(Request $request)
      {
            try {
                  $pincodeId = $request->pincode_id ?? null;
                  $pincodeData = $this->masterpincodeRepo->edit($pincodeId);
                  $pincodeData['msg'] = config('crm/http-status.success.message');
                  return  $this->successResponse($pincodeData);
            } catch (Throwable  | ClientException $throwable) {
                  throw new Throwable(
                        Log::info("Service : MenuService , Method : editpincode : %s" . $throwable->__toString())
                  );
            }
      }
      public function save(Request $request, MasterPincodeRepository $pincodeMasterRepo, MasterApiLogRepository $masterApiLogRepo)
      {
            try {
                  $rules = [
                        "code" => "required",
                        "area" => "required",
                        "city" => "required",
                        "district" => "required",
                        "state" => "required",
                        "is_active" => "required",
                  ];
                  $validator = Validator::make($request->all(), $rules);
                  if ($validator->fails()) {
                        return $validator->errors();
                  }
                  $requestUrl = $request->url . $request->path();
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
                  $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PINCODE_UPSERT');
                  $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
                  $requestData['customHeader']['X-Api-Url'] = $requestUrl;
                  $requestData['request'] = $request;
                  $masterApiLogData = $masterApiLogRepo->save($requestData);
                  $request['master_log_id'] = $masterApiLogData['id'];
                  $save = $pincodeMasterRepo->save($request->all(), $request);
                  if ($save) {
                        $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.SUCCESS');
                        $response = $this->responseJson(
                              config('journey/http-status.success.status'),
                              config('journey/http-status.success.message'),
                              config('journey/http-status.success.code'),
                              []
                        );
                        $masterApiLogRepo->update($masterApiLogData['id'], json_encode($response), $requestData['customHeader']['X-Api-Status']);
                        return $response;
                  } else {
                        $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                        $response = $this->responseJson(
                              config('journey/http-status.failure.status'),
                              config('journey/http-status.failure.message'),
                              config('journey/http-status.failure.code'),
                              []
                        );
                        $masterApiLogRepo->update($masterApiLogData['id'], json_encode($response), $requestData['customHeader']['X-Api-Status']);
                        return $response;
                  }
            } catch (Throwable | Exception | HttpClientException $throwable) {
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                  $response = $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        []
                  );
                  $masterApiLogRepo->save($requestData);
                  Log::info("MasterPincodeService " . $throwable->__toString());
            }
      }
      public function searchPincode(Request $request, MasterPincodeRepository $pincodeMasterRepo, MasterApiLogRepository $masterApiLogRepo)
      {
            try {
                  $rules = [
                        "code" => "required",
                  ];
                  $validator = Validator::make($request->all(), $rules);
                  if ($validator->fails()) {
                        return $validator->errors();
                  }
                  $requestUrl = $request->url . $request->path();
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
                  $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PINCODE_LIST');
                  $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
                  $requestData['customHeader']['X-Api-Url'] = $requestUrl;
                  $requestData['request'] = $request;
                  $masterApiLogData = $masterApiLogRepo->save($requestData);
                  $request['master_log_id'] = $masterApiLogData['id'];
                  $pincodeData = $pincodeMasterRepo->getPincodeData($request->all());
                  if (empty($pincodeData)) {
                        return $this->responseJson(
                              config('journey/http-status.failure.status'),
                              config('journey/http-status.failure.message'),
                              config('journey/http-status.failure.code'),
                              []
                        );
                  }
                  return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $pincodeData);
            } catch (Throwable | HttpClientException $throwable) {
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                  $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        []
                  );
                  $masterApiLogRepo->save($requestData);
                  Log::info("MasterPincodeService View" . $throwable->__toString());
            }
      }
      public function getMasterpincodes(
            Request $request,
            MasterApiLogRepository $masterApiLogRepo,
            MasterPincodeRepository $masterpincodeRepo
      ) {
            try {
                  $pincodeMenu = $this->masterpincodeRepo->list($request);
                  $pincodeList['mainMenu'] = $pincodeMenu;
                  $pincodeList['msg'] = config('crm/http-status.success.message');
                  return  $this->successResponse($pincodeList);
            } catch (Throwable  | ClientException $throwable) {
                  throw new Throwable(
                        Log::info("Service : MenuService , Method : getMenu : %s" . $throwable->__toString())
                  );
            }
      }
}
