<?php

namespace App\Repositories\HousingJourney;

use App\Entities\MongoLog\TalismaResolveContactLog;
use App\Utils\CrmTrait;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;

class TalismaResolveContactLogRepository
{
    use CrmTrait;
    /**
     *Get logs from Table
     * @param $request
     */
    public function getLog($request, $offset = null)
    {
        try {
            $query = TalismaResolveContactLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', 'LIKE', '%' . $keyword . '%');
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
            $talismaResolveLogList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $talismaResolveLogList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info(" TalismaResolveContactLogRepository   getLog " . $throwable->__toString());
        }
    }
}
