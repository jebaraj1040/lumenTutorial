<?php

namespace App\Services\HousingJourney;

use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HjMasterBranchImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\MasterBranchRepository;
use App\Repositories\HousingJourney\MasterApiLogRepository;

class MasterBranchService extends Service
{
    /**
     * Create a new Service instance.
     *
     */
    public function save(
        Request $request,
        MasterBranchRepository $branchmasterRepo,
        MasterApiLogRepository $masterApiLogRepo
    ) {
        try {
            $rules = [
                "name" => "required",
                "code" => "required",
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $requestUrl = $request->url . $request->path();
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.BRANCH_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogData = $masterApiLogRepo->save($requestData);
            $request['handle'] = strtolower(str_replace(' ', '-', $request->name));
            $branchSave = $branchmasterRepo->save($request->all());
            if ($branchSave) {
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
            throw new Throwable(sprintf("Service : MasterBranchService,
            Method : save : %s", $throwable->__toString()));
        }
    }
    /**
     *Save Import File
     *
     */
    public function import(Request $request, MasterApiLogRepository $masterApiLogRepo)
    {
        $file = $request->file('file');
        $file->getClientOriginalExtension();
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $file->move(storage_path('app/excel'), $filename);
        $path = storage_path('app/excel/' . $filename);
        $requestUrl = $request->url . $request->path();
        $requestData['request'] = $request;
        $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
        $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.MASTER_BRANCH_IMPORT');
        $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
        $requestData['customHeader']['X-Api-Url'] = $requestUrl;
        $requestData['request'] = $request;
        $masterApiLogRepo->save($requestData);
        Excel::import(new HjMasterBranchImport, $path);
        return $this->responseJson(
            config('journey/http-status.success.status'),
            config('journey/http-status.success.message'),
            config('journey/http-status.success.code'),
            []
        );
    }
}
