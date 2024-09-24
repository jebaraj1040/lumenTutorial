<?php

namespace App\Services\HousingJourney;

use Exception;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Imports\HjMasterProjectImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\MasterProjectRepository;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\MasterPincodeRepository;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use App\Jobs\MasterProjectExport;

class MasterProjectService extends Service
{
      use CrmTrait;
      private $masterprojectRepo;
      /**
       * Save Import File
       *
       */
      public function import(Request $request, MasterApiLogRepository $masterApiLogRepo)
      {
            $file = $request->file('file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/excel'), $filename);
            $path = storage_path('app/excel/' . $filename);
            $requestUrl = $request->url . $request->path();
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PROJECT_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogRepo->save($requestData);
            Excel::import(new HjMasterProjectImport, $path);
            return $this->responseJson(
                  config('journey/http-status.success.status'),
                  config('journey/http-status.success.message'),
                  config('journey/http-status.success.code'),
                  []
            );
      }
      public function save(Request $request, MasterProjectRepository $projectMasterRepo, MasterApiLogRepository $masterApiLogRepo)
      {
            try {
                  $rules = [
                        "name" => "required",
                        "builder" => "required",
                        "pincode_id" => "required",
                        "is_approved" => "required",
                        "is_active" => "required",
                  ];
                  $validator = Validator::make($request->all(), $rules);
                  if ($validator->fails()) {
                        return $validator->errors();
                  }
                  $requestUrl = $request->url . $request->path();
                  $requestData['request'] = $request;
                  $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
                  $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PROJECT_UPSERT');
                  $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
                  $requestData['customHeader']['X-Api-Url'] = $requestUrl;
                  $requestData['request'] = $request;
                  $masterApiLogData = $masterApiLogRepo->save($requestData);
                  $request['name_handle'] = strtolower(str_replace(' ', '-', $request->name));
                  $request['builder_handle'] = strtolower(str_replace(' ', '-', $request->builder));
                  $save = $projectMasterRepo->save($request->all(), $request);
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
                  $requestUrl = $request->url . $request->path();
                  $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
                  $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PROJECT_UPSERT');
                  $requestData['customHeader']['X-Api-Url'] = $requestUrl;
                  $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                  $masterApiLogRepo->save($requestData);
                  Log::info("MasterProjectService save " . $throwable->__toString());
            }
      }
      public function getMasterprojects(
            Request $request,
            MasterApiLogRepository $masterApiLogRepo,
            MasterProjectRepository $projectMasterRepo
      ) {
            try {
                  $projectMenu = $projectMasterRepo->list($request);
                  $projectList['mainMenu'] = $projectMenu;
                  $projectList['msg'] = config('crm/http-status.success.message');
                  return  $this->successResponse($projectList);
            } catch (Throwable  | ClientException $throwable) {
                  throw new Throwable(
                        Log::info("Service : MenuService , Method : getMenu : %s" . $throwable->__toString())
                  );
            }
      }
      public function editProduct(Request $request, MasterProjectRepository $projectMasterRepo, MasterPincodeRepository $pincodemasterRepo)
      {
            try {
                  $projectId = $request->project_id ?? null;
                  $projectData = $projectMasterRepo->edit($projectId);
                  $projectData['msg'] = config('crm/http-status.success.message');
                  return  $this->successResponse($projectData);
            } catch (Throwable  | ClientException $throwable) {
                  throw new Throwable(
                        Log::info("Service : MenuService , Method : editProduct : %s" . $throwable->__toString())
                  );
            }
      }
}
