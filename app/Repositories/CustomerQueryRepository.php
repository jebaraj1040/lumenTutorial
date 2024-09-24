<?php

namespace App\Repositories;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\MongoLog\CustomerQueryLog;
use App\Entities\MongoLog\TalismaCreateContactLog;
use App\Entities\MongoLog\TalismaResolveContactLog;
use App\Utils\CrmTrait;

class CustomerQueryRepository
{
    use CrmTrait;
    /**
     * Insert customer query.
     *
     * @param $request
     *
     */
    public function save($request)
    {
        try {
            return CustomerQueryLog::create($request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CustomerQueryRepository : " . $throwable->__toString());
        }
    }
    public function getLog($request)
    {
        try {
            $query = CustomerQueryLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('mobile_number', 'LIKE', '%' . $keyword . '%');
                });
            }
            $totalLength = $query->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            $query->options(['allowDiskUse' => true]);
            $queryList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $queryList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getLog " . $throwable->__toString());
        }
    }
    /**
     * Insert talisma resolve contact log.
     *
     * @param $request
     *
     */
    public function resolveContactSave($request)
    {
        try {
            TalismaResolveContactLog::create($request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CustomerQueryRepository resolveContactSave " . $throwable->__toString());
        }
    }
    /**
     * Insert talisma create contact log.
     *
     * @param $request
     *
     */
    public function createContactSave($request)
    {
        try {
            TalismaCreateContactLog::create($request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CustomerQueryRepository createContactSave " . $throwable->__toString());
        }
    }
}
