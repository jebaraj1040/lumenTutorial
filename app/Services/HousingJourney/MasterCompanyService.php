<?php

namespace App\Services\HousingJourney;

use Exception;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HjMasterCompanyImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\MasterCompanyRepository;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use App\Jobs\MasterCompanyExport;




class MasterCompanyService extends Service
{
  use CrmTrait;
  private $mastercompanyRepo;
  /**
   *Save Import File
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
    $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.COMPANY_UPSERT');
    $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
    $requestData['customHeader']['X-Api-Url'] = $requestUrl;
    $requestData['request'] = $request;
    $masterApiLogRepo->save($requestData);
    Excel::import(new HjMasterCompanyImport, $path);
    return $this->responseJson(
      config('journey/http-status.success.status'),
      config('journey/http-status.success.message'),
      config('journey/http-status.success.code'),
      []
    );
  }
  public function save(Request $request, MasterCompanyRepository $companyMasterRepo, MasterApiLogRepository $masterApiLogRepo)
  {
    try {
      $rules = [
        "name" => "required",
        "is_active" => "required",
      ];
      $validator = Validator::make($request->all(), $rules);
      if ($validator->fails()) {
        return $validator->errors();
      }
      $requestUrl = $request->url . $request->path();
      $requestData['request'] = $request;
      $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
      $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.COMPANY_UPSERT');
      $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
      $requestData['customHeader']['X-Api-Url'] = $requestUrl;
      $requestData['request'] = $request;
      $masterApiLogData = $masterApiLogRepo->save($requestData);
      $request['handle'] = strtolower(str_replace(' ', '-', $request->name));
      $save = $companyMasterRepo->save($request->all(), $request);
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
      $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.COMPANY_UPSERT');
      $requestData['customHeader']['X-Api-Url'] = $requestUrl;
      $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
      $masterApiLogRepo->save($requestData);
      Log::info("MasterCompanyService " . $throwable->__toString());
    }
  }
  /**
   * List Master Companys
   *@param  $request
   */
  public function getMastercompanys(
    Request $request,
    MasterApiLogRepository $masterApiLogRepo,
    MasterCompanyRepository $companyMasterRepo
  ) {
    try {
      $companyMenu = $companyMasterRepo->list($request);
      $companyList['mainMenu'] = $companyMenu;
      $companyList['msg'] = config('crm/http-status.success.message');
      return  $this->successResponse($companyList);
    } catch (Throwable  | ClientException $throwable) {
      throw new Throwable(
        Log::info("Service : MenuService , Method : getMenu : %s" . $throwable->__toString())
      );
    }
  }
  public function editCompany(Request $request, MasterCompanyRepository $companyMasterRepo)
  {
    try {
      $companyId = $request->company_id ?? null;
      $companyData = $companyMasterRepo->edit($companyId);
      $companyData['msg'] = config('crm/http-status.success.message');
      return  $this->successResponse($companyData);
    } catch (Throwable  | ClientException $throwable) {
      throw new Throwable(
        Log::info("Service : MenuService , Method : editCompany : %s" . $throwable->__toString())
      );
    }
  }
}
