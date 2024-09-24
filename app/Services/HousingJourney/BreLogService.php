<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use App\Repositories\HousingJourney\BreLogRepository;

class BreLogService extends Service
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
            $repository = new BreLogRepository();
            $datas['methodName'] = 'getLog';
            $datas['fileName'] = 'Bre-Log-Report-';
            $datas['moduleName'] = 'Bre-Log';
            return $this->exportData($request, $repository, $datas);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("BreLogService  exportLog" . $throwable->__toString());
            return $this->responseJson(
                config('crm/http-status.error.status'),
                config('crm/http-status.error.message'),
                config('crm/http-status.error.code'),
                []
            );
        }
    }
    /**
     * getFilterData
     *
     * @param Empty
     * @return mixed
     */
    public function getFilterData()
    {
        try {
            $apiSource = $this->getFilterDatas('apiSource');
            $apiSourcePage = $this->getFilterDatas('apiSourcePage');
            $apiType = $this->getFilterDatas('apiType');
            $filterList['api_source'] =  $this->convertFilterData($apiSource, 'api_source');
            $filterList['api_source_page'] =  $this->convertFilterData($apiSourcePage, 'api_source_page');
            $filterList['api_type'] =  $this->convertFilterData($apiType, 'api_type');
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $filterList
            );
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : BreLogService , Method : getFilterData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * getLogList
     *
     * @param  Request $request
     * @return mixed
     */
    public function getLogList(Request $request, BreLogRepository $breLogRepo): mixed
    {
        try {
            $logsList = $breLogRepo->getLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $logsList
            );
        } catch (Throwable   | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : BreLogService , Method : getLogList : %s", $throwable->__toString())
            );
        }
    }
}
