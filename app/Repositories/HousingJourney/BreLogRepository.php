<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\MongoLog\BRELog;
use App\Entities\HousingJourney\HjBreVersion;
use App\Utils\CrmTrait;

// Define the constant outside the class
define('API_TYPE_RESPONSE', config('constants/apiType.RESPONSE'));

class BreLogRepository
{
    use CrmTrait;
    /**
     * Insert into BRELog.
     *
     */
    public function save($request)
    {
        try {
            return BRELog::create($request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("BreLogRepository save " . $throwable->__toString());
        }
    }

    /**
     * fetch BRE One Data
     *
     */
    public function fetchBreOneData($request)
    {
        try {
            return BRELog::where('lead_id', $request['lead_id'])
                ->where('quote_id', $request['quote_id'])
                ->where('api_request_type', API_TYPE_RESPONSE)
                ->where('api_type', 'BRE_LEVEL_ONE')->where('api_source_page', 'EMPLOYMENT_DETAIL_PAGE')->orderBy('created_at', 'DESC')->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("BreLogRepository fetchBreOneData " . $throwable->__toString());
        }
    }

    /**
     * fetch BRE One Data
     *
     */
    public function fetchBreCoApplicantOneData($request)
    {
        try {
            return BRELog::where('lead_id', $request['lead_id'])
                ->where('quote_id', $request['quote_id'])
                ->where('api_request_type', API_TYPE_RESPONSE)
                ->where('api_type', 'BRE_LEVEL_ONE')->where('api_source_page', 'CO_APPLICANT_PAGE')->orderBy('created_at', 'DESC')->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("BreLogRepository fetchBreOneData " . $throwable->__toString());
        }
    }
    /**
     * fetch BRE Two Data
     *
     */
    public function fetchBreTwoData($request)
    {
        try {
            return BRELog::where('lead_id', $request['lead_id'])
                ->where('quote_id', $request['quote_id'])
                ->where('api_request_type', API_TYPE_RESPONSE)
                ->where('api_type', 'BRE_LEVEL_TWO')->orderBy('created_at', 'DESC')->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("BreLogRepository fetchBreTwoData " . $throwable->__toString());
        }
    }
    /**
     *Get logs from Table
     * @param $request
     */
    public function getLog($request, $offset = null)
    {
        try {
            $query = BRELog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
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
            Log::info("BreLogRepository  getLog" . $throwable->__toString());
        }
    }

    /**
     * get bre version date
     * @param $request
     */
    public function getBreVersionDate()
    {
        try {
            return HjBreVersion::latest('created_at')->first();
        } catch (Throwable |  HttpClientException $throwable) {
            Log::info("BreLogRepository getBreVersionDate " . $throwable->__toString());
        }
    }

    /**
     * get BRE Deviation
     * @param $request
     */

    public function getBreDeviation($request)
    {
        try {
            $isDeviation = null;
            $breLogCount = BRELog::where('quote_id', $request['quote_id'])->orderBy('created_at', 'desc')->count();
            if ($breLogCount > 0) {
                $breLog = BRELog::where('quote_id',  $request['quote_id'])->orderBy('created_at', 'desc')->first();
                $apiData = json_decode($breLog->api_data, true);
                if ($apiData && isset($apiData['Table1']) && count($apiData['Table1']) == 1) {
                    $isDeviation = $apiData['Table1'][0]['IsDev'] ?? null;
                }
            }
            return $isDeviation;
        } catch (Throwable |  HttpClientException $throwable) {
            Log::info("BreLogRepository getBreDeviation " . $throwable->__toString());
        }
    }
}
