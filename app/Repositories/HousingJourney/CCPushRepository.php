<?php

namespace App\Repositories\HousingJourney;

use App\Entities\MongoLog\CCDispositionLog;
use App\Entities\MongoLog\CCPushLog;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Utils\CoreTrait;
use App\Utils\CrmTrait;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Crypt;

class CCPushRepository
{
    use CrmTrait;
    use CoreTrait;

    /**
     * Insert log to cc push log table.
     *
     * @param $request
     */
    public function save($requestData)
    {
        try {
            return CCDispositionLog::create($requestData);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CCDispositionLog " . $throwable->__toString());
        }
    }

    public function ccFinalSubmit($requestData)
    {
        $this->prepareFinalSubmitData($requestData);
    }

    /**
     *Get logs from Table
     * @param $request
     */
    public function getCCPushLog($request, $offset = null)
    {
        try {
            $query = CCPushLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
                    $query->orWhere('cc_quote_id', $keyword);
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
            $apiLogList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $apiLogList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CCPushRepository  getCCPushLog " . $throwable->__toString());
        }
    }
}
