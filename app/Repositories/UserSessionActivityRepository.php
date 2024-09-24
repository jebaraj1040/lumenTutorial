<?php

namespace App\Repositories;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\MongoLog\UserSessionActivityLog;
use App\Entities\MongoLog\UserPortfolioLog;
use App\Utils\CrmTrait;

class UserSessionActivityRepository
{
    /**
     * save user session activity
     *
     * @param  Request $request
     *
     */
    use CrmTrait;
    public function userSessionSave($request)
    {
        try {
            return  UserSessionActivityLog::create($request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityRepository userSessionSave ", $throwable);
        }
    }
    /**
     * save user pan activity
     *
     * @param  Request $request
     *
     */
    public function userPanSave($request)
    {
        try {
            return UserPortfolioLog::create($request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityRepository userPanSave ", $throwable);
        }
    }
    /**
     * get user session log
     *
     * @param  Request $request
     *
     */
    public function getUserSessionLog($request, $offset = null)
    {
        try {
            $query = UserSessionActivityLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('session_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('browser_id', 'LIKE', '%' . $keyword . '%');
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
            Log::info("UserSessionActivityRepository getUserSessionLog ", $throwable);
        }
    }
    /**
     * get user portfolio log
     *
     * @param  Request $request
     *
     */
    public function getUserPortfolioLog($request, $offset = null)
    {
        try {
            $query = UserPortfolioLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('session_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('browser_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('pan', $keyword);
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
            Log::info("UserSessionActivityRepository getUserPortfolioLog ", $throwable);
        }
    }
}
