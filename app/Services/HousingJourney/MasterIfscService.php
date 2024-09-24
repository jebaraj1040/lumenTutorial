<?php

namespace App\Services\HousingJourney;

use Exception;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HjMasterIfscImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\MasterIfscRepository;
use App\Utils\CrmTrait;
use GuzzleHttp\Exception\ClientException;

class MasterIfscService extends Service
{
    use CrmTrait;
    public function import(Request $request, MasterApiLogRepository $masterApiLogRepo)
    {
        $file = $request->file('file');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(storage_path('app/excel'), $filename);
        $path = storage_path('app/excel/' . $filename);
        $requestUrl = $request->url . $request->path();
        $requestData['request'] = $request;
        $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
        $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.IFSC_UPSERT');
        $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
        $requestData['customHeader']['X-Api-Url'] = $requestUrl;
        $requestData['request'] = $request;
        $masterApiLogRepo->save($requestData);
        Excel::import(new HjMasterIfscImport, $path);
        return $this->responseJson(
            config('journey/http-status.success.status'),
            config('journey/http-status.success.message'),
            config('journey/http-status.success.code'),
            []
        );
    }
    public function save(Request $request, MasterIfscRepository $ifscMasterRepo, MasterApiLogRepository $masterApiLogRepo)
    {
        try {
            $rules = [
                "bank_code" => "required",
                "bank_name" => "required",
                "ifsc" => "required",
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $requestUrl = $request->url . $request->path();
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.IFSC_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogData = $masterApiLogRepo->save($requestData);
            $request['master_log_id'] = $masterApiLogData['id'];
            $save = $ifscMasterRepo->save($request->all(), $request);
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
            Log::info("MasterIfscService " . $throwable->__toString());
        }
    }
    public function getMasterIfsc(
        Request $request,
        MasterApiLogRepository $masterApiLogRepo,
        MasterIfscRepository $ifscMasterRepo
    ) {
        try {
            $ifscMenu = $ifscMasterRepo->list($request);
            $ifscList['mainMenu'] = $ifscMenu;
            $ifscList['msg'] = config('crm/http-status.success.message');
            return  $this->successResponse($ifscList);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : IfscService , Method : getMenu : %s" . $throwable->__toString())
            );
        }
    }
}
