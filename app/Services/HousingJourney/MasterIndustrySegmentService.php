<?php

namespace App\Services\HousingJourney;

use Illuminate\Http\Request;
use App\Services\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use Throwable;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\MasterIndustrySegmentRepository;

class MasterIndustrySegmentService extends Service
{
    /**
     * save into industry segment table
     *
     */
    public function save(
        Request $request,
        MasterApiLogRepository $masterApiLogRepo,
        MasterIndustrySegmentRepository $industrySegmentMasterRepo
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
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.INDUSTRY_SEGMENT_UPSERT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogData = $masterApiLogRepo->save($requestData);
            $request['handle'] = strtolower(str_replace(' ', '-', $request->name));
            $industrySegmentSave = $industrySegmentMasterRepo->save($request->all());
            if ($industrySegmentSave) {
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
            throw new Throwable(sprintf("Service : MasterIndustrySegmentService,
            Method : save : %s", $throwable->__toString()));
        }
    }
}
