<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\MongoLog\SmsLog;
use App\Utils\CrmTrait;

class SmsLogRepository
{
    use CrmTrait;
    public function save($request)
    {
        try {
            SmsLog::create($request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("SmsLogRepository save " . $throwable->__toString());
        }
    }

    public function getLastSmsLog($request)
    {
        try {
            return SmsLog::where('quote_id', $request['quote_id'])->where('cc_quote_id', $request['cc_quote_id'])->where('mobile_number', $request['mobile_number'])->orderBy('created_at', 'DESC')->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("SmsLogRepository getLastSmsLog " . $throwable->__toString());
        }
    }

    public function updateSmsLog($request)
    {
        try {
            SmsLog::where('_id', $request['id'])->update(['response' => $request['response']]);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("SmsLogRepository updateSmsLog " . $throwable->__toString());
        }
    }

    public function getSmsLog($request, $offset = null)
    {
        try {
            $query = SmsLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
                    $query->orWhere('cc_quote_id', $keyword);
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('sms_template_type', 'LIKE', '%' . $keyword . '%');
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
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("SmsLogRepository getSmsLog " . $throwable->__toString());
        }
    }
}
