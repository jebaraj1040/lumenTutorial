<?php

namespace App\Repositories\HousingJourney;

use App\Entities\MongoLog\CibilLog;
use App\Utils\CrmTrait;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;

class CibilLogRepository
{
    use CrmTrait;
    /**
     *Get logs from Table
     * @param $request
     */
    public function getLog($request, $offset = null)
    {
        try {
            $query = CibilLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('pan', 'LIKE', '%' . $keyword . '%');
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
            $cibilLogList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $cibilLogList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info(" CibilLogRepository   getLog " . $throwable->__toString());
        }
    }
    /**
     * save logs
     * @param $request
     */
    public function save($request)
    {
        try {
            CibilLog::create($request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info(" CibilLogRepository   save " . $throwable->__toString());
        }
    }
}
