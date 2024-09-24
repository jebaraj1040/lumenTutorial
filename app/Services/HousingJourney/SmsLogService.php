<?php

namespace App\Services\HousingJourney;

use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\SmsLogRepository;
use Illuminate\Http\Request;
use App\Utils\CrmTrait;

class SmsLogService extends Service
{

    use CrmTrait;
    public function getLog(Request $request)
    {
        try {
            $smsRepo = new SmsLogRepository();
            $logsList = $smsRepo->getSmsLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $logsList
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("SmsLogService getLog : %s", $throwable->__toString());
        }
    }

    public function getFilterData()
    {
        try {
            $apiSourcePage = $this->getFilterDatas('apiSourcePage');
            $filterList['api_source_page'] =  $this->convertFilterData($apiSourcePage, 'api_source_page');
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $filterList
            );
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("SmsLogService getFilterData : %s", $throwable->__toString());
        }
    }
    /**
     * Export Log.
     *
     * @param $request
     *
     */

    public function exportLog(Request $request)
    {
        try {
            $smsRepo = new SmsLogRepository();
            $datas['methodName'] = 'getSmsLog';
            $datas['fileName'] = 'Sms-Log-Report-';
            $datas['moduleName'] = 'Sms-Log';
            return $this->exportData($request, $smsRepo, $datas);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("SmsLogService exportLog : %s", $throwable->__toString());
        }
    }
}
