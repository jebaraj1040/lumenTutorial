<?php

namespace App\Repositories\HousingJourney;

use App\Entities\HousingJourney\HjMasterApiLog;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;

class MasterApiLogRepository
{
    /**
     * Insert log to master api log table.
     *
     * @param $request
     *
     */
    public function save($requestData)
    {
        try {
            $request = $requestData['request'];
            if (array_key_exists('customHeader', $requestData)) {
                $apiSource = $requestData['customHeader']['X-Api-Source'];
                $apiType = $requestData['customHeader']['X-Api-Type'];
                $apiStatus = $requestData['customHeader']['X-Api-Status'] ?? config('constants/apiStatus.INIT');
                $apiUrl = $requestData['customHeader']['X-Api-Url'];
                if (array_key_exists('type', $requestData)) {
                    $apiRequest = $request;
                } else {
                    $apiRequest = $request->all();
                }
            }
            return HjMasterApiLog::create([
                "api_type" => $apiType,
                "api_source" => $apiSource,
                "request" =>  json_encode($apiRequest),
                "response" => null,
                "url" => $apiUrl,
                "api_status" => $apiStatus
            ]);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("MasterApiLogRepository save " . $throwable->__toString());
        }
    }
    /**
     * Update log to api log table.
     *
     * @param $request
     *
     */
    public function update($apiLogId, $response, $apistatus)
    {
        try {
            HjMasterApiLog::where('id', $apiLogId)->update(['response' => $response, 'api_status' => $apistatus]);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("update " . $throwable->__toString());
        }
    }
}
