<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use App\Repositories\HousingJourney\FinalSubmitLogRepository;
use Illuminate\Http\Client\HttpClientException;


class FinalSubmitLogService extends Service
{
    use CrmTrait;
    /**
     * Export Log.
     *
     * @param $request
     *
     */
    public function exportLog(Request $request)
    {

        try {
            $rules = [
                "fromDate" => "required",
                "toDate"  => "required",
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $repository = new FinalSubmitLogRepository();
            $datas['methodName'] = 'getLog';
            $datas['fileName'] = 'Final-Submit-Log-Report-';
            $datas['moduleName'] = 'Final-Submit-Log';
            return $this->exportData($request, $repository, $datas);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("FinalSubmitLogService  exportLog" . $throwable->__toString());
            return $this->responseJson(
                config('crm/http-status.error.status'),
                config('crm/http-status.error.message'),
                config('crm/http-status.error.code'),
                []
            );
        }
    }
    /**
     * getLogList
     *
     * @param  Request $request
     * @return mixed
     */
    public function getLogList(Request $request, FinalSubmitLogRepository $finalSubmitLogRepo): mixed
    {
        try {
            $logsList = $finalSubmitLogRepo->getLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $logsList
            );
        } catch (Throwable   | HttpClientException $throwable) {
            throw new Throwable(
                Log::info("Service : FinalSubmitLogService , Method : getLogList : %s", $throwable->__toString())
            );
        }
    }
}
