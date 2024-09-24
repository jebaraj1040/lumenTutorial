<?php

namespace App\Repositories;

use App\Entities\MongoLog\ApiLog;
use App\Utils\CrmTrait;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;

class ApiLogRepository
{
    use CrmTrait;
    /**
     * Insert log to api log table.
     *
     * @param $requestData
     */
    public function save($requestData)
    {
        try {
            return ApiLog::create($requestData);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApiLog " . $throwable->__toString());
        }
    }
    /**
     *Get logs from Table
     * @param $request
     */
    public function getLog($request, $offset = null)
    {
        try {
            $query = ApiLog::query();
            $query = $this->applyFilter($query, $request);
            $totalLength = $query->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            if (empty($offset === false) && $offset != 'null' && $offset != '') {
                $limit = (int)env('EXPORT_EXCEL_LIMIT');
                $query->offset($offset)->limit($limit);
            }
            $query->options(['allowDiskUse' => true]);
            $apiLogList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $apiLogList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getLog " . $throwable->__toString());
        }
    }
}
