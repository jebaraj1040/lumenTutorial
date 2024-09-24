<?php

namespace App\Repositories\HousingJourney;

use App\Entities\MongoLog\PanLog;
use App\Utils\CrmTrait;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;

class PanLogRepository
{
    use CrmTrait;
    /**
     * Insert log to api log table.
     *
     * @param $request
     */
    public function save($requestData)
    {
        try {
            return PanLog::create($requestData);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PanLog " . $throwable->__toString());
        }
    }
    /**
     *Get logs from Table
     * @param $request
     */
    public function getLog($request, $offset = null)
    {
        try {
            $query = PanLog::query();
            $query = $this->applyFilter($query, $request);
            if (empty($request) === false && empty($request->search) === false) {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                });
            }
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
            $panLogList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $panLogList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getLog " . $throwable->__toString());
        }
    }
    /**
     * List log from Table
     * @param $request
     */
}
