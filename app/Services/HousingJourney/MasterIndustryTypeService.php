<?php

namespace App\Services\HousingJourney;

use Illuminate\Http\Request;
use App\Services\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use Throwable;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\MasterIndustryTypeRepository;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HjMasterIndustryTypeImport;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use Illuminate\Support\Facades\Log;
use App\Jobs\MasterIndustryTypeExport;

class MasterIndustryTypeService extends Service
{
    use CrmTrait;
    /**
     * 
     * industry type insert
     *
     */
    public function save(
        Request $request,
        MasterApiLogRepository $masterApiLogRepo,
        MasterIndustryTypeRepository $industryTypeMasterRepo
    ) {
        try {
            $rules = [
                "name" => "required",
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $requestUrl = $request->url . $request->path();
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.INDUSTRY_TYPE_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogData = $masterApiLogRepo->save($requestData);
            $request['handle'] = strtolower(str_replace(' ', '-', $request->name));
            $industryTypeSave = $industryTypeMasterRepo->save($request->all());
            if ($industryTypeSave) {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.SUCCESS');
                $response = $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    []
                );
                $masterApiLogRepo->update(
                    $masterApiLogData['id'],
                    json_encode($response),
                    $requestData['customHeader']['X-Api-Status']
                );
                return $response;
            } else {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                $response = $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
                $masterApiLogRepo->update(
                    $masterApiLogData['id'],
                    json_encode($response),
                    $requestData['customHeader']['X-Api-Status']
                );
                return $response;
            }
        } catch (Throwable | HttpClientException $throwable) {
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
            $masterApiLogRepo->save($requestData);
            throw new Throwable(sprintf("Service : MasterIndustryTypeService,
            Method : save : %s", $throwable->__toString()));
        }
    }
    /**
     * industry type insert
     *
     */
    public function import(Request $request, MasterApiLogRepository $masterApiLogRepo)
    {
        try {
            $file = $request->file('file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/excel'), $filename);
            $path = storage_path('app/excel/' . $filename);
            $requestUrl = $request->url . $request->path();
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.MASTER_IMPORT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogRepo->save($requestData);
            Excel::import(new HjMasterIndustryTypeImport, $path);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                []
            );
        } catch (Throwable | HttpClientException $throwable) {
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
            $masterApiLogRepo->save($requestData);
            throw new Throwable(sprintf("Service : MasterIndustryTypeService,
            Method : save : %s", $throwable->__toString()));
        }
    }
    public function editIndustry(Request $request, MasterIndustryTypeRepository $industryTypeMasterRepo)
    {
        try {
            $industryId = $request->industry_id ?? null;
            $industryData = $industryTypeMasterRepo->edit($industryId);
            $industryData['msg'] = config('crm/http-status.success.message');
            return  $this->successResponse($industryData);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : MenuService , Method : editIndustry : %s" . $throwable->__toString())
            );
        }
    }
    /**
     * List Master Industries
     *@param  $request
     */
    public function getMasterindustries(
        Request $request,
        MasterApiLogRepository $masterApiLogRepo,
        MasterIndustryTypeRepository $industryMasterRepo
    ) {
        try {
            $industryMenu = $industryMasterRepo->list($request);
            $industryList['mainMenu'] = $industryMenu;
            $industryList['msg'] = config('crm/http-status.success.message');
            return  $this->successResponse($industryList);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : MenuService , Method : getMenu : %s" . $throwable->__toString())
            );
        }
    }
}
