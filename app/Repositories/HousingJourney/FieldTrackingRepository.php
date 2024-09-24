<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\MongoLog\FieldTrackingLog;
use Carbon\Carbon;
use App\Entities\HousingJourney\HjMasterPincode;
use App\Utils\CrmTrait;

class FieldTrackingRepository
{
    use CrmTrait;
    /**
     * Insert Fieldwise update table
     *
     */
    public function save($request)
    {
        try {
            $request['created_timestamp'] = Carbon::now()->timestamp;
            return FieldTrackingLog::create($request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository save " . $throwable->__toString());
        }
    }

    /**
     * Get field count from fieldwise update table
     *
     */
    public function viewCount($reqData)
    {
        try {
            if ($reqData['quote_id']) {
                return FieldTrackingLog::where(
                    'mobile_number',
                    $reqData['mobile_number']
                )->where('quote_id', $reqData['quote_id'])->orderBy('created_at', 'DESC')->count();
            } else {
                return FieldTrackingLog::where(
                    'mobile_number',
                    $reqData['mobile_number']
                )->where('cc_auth_token', $reqData['cc_auth_token'])
                    ->orderBy('created_at', 'DESC')->count();
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository view " . $throwable->__toString());
        }
    }

    /**
     * Get field  from fieldwise update table
     *
     */
    public function view($reqData)
    {
        try {
            if ($reqData['quote_id']) {
                return FieldTrackingLog::where(
                    'mobile_number',
                    $reqData['mobile_number']
                )->where('quote_id', $reqData['quote_id'])->orderBy('created_at', 'DESC')->get()->toArray();
            } else {
                return FieldTrackingLog::where(
                    'mobile_number',
                    $reqData['mobile_number']
                )->where('cc_auth_token', $reqData['cc_auth_token'])
                    ->orderBy('created_at', 'DESC')->get()->toArray();
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository viewCount " . $throwable->__toString());
        }
    }

    /**
     * Get All Field Tracking Records
     *
     */
    public function getAllFieldTrackingRecords()
    {
        $fiveMinutesBack = Carbon::now()->subMinutes(05)->timestamp;
        $tenMinutesBack = Carbon::now()->subMinutes(10)->timestamp;
        try {
            // 15 mins check.
            return FieldTrackingLog::where(function ($query) use ($tenMinutesBack, $fiveMinutesBack) {
                $query->where('created_timestamp', '<=', Carbon::now()->timestamp)
                    ->whereBetween('created_timestamp', [$tenMinutesBack, $fiveMinutesBack])
                    ->whereNotNull('quote_id')
                    ->where('cc_push_status', 0);
            })
                ->orWhere('cc_push_tag', 1)
                ->groupBy('quote_id')->pluck('quote_id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getAllFieldTrackingRecords" . $throwable->__toString());
        }
    }

    /**
     * Get InActive Tracking Records
     *
     */
    public function getInActiveTrackingRecords($quoteId)
    {
        $fiveMinutesBack = Carbon::now()->subMinutes(05)->timestamp;
        $currentTimeStamp = Carbon::now()->timestamp;
        try {
            return FieldTrackingLog::where('created_timestamp', '<=', Carbon::now()->timestamp)
                ->whereBetween('created_timestamp', [$fiveMinutesBack, $currentTimeStamp])->where('quote_id', $quoteId)->count();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getInActiveTrackingRecords" . $throwable->__toString());
        }
    }

    /**
     *Get Individual Field Tracking Records
     *
     */
    public function getIndividualFieldTrackingRecords($mobileNumber)
    {
        try {
            return FieldTrackingLog::where('mobile_number', $mobileNumber)->count();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getIndividualFieldTrackingRecords" . $throwable->__toString());
        }
    }

    /**
     * Get pincode id from value
     *
     */
    public function getPincodeId($pincode)
    {
        try {
            return HjMasterPincode::where('code', $pincode)->value('id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository getPincodeId " . $throwable->__toString());
        }
    }

    /**
     * List
     *
     */
    public function list($request, $offset = null)
    {
        try {
            $query = FieldTrackingLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('cc_quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('api_source_page', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('cc_push_sub_stage_priority', 'LIKE', '%' . $keyword . '%');
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
            $fieldTrackingLogList = $query->orderby('created_at', 'DESC')->get();
            $logList = [];
            $logList['totalLength'] =  $totalLength;
            $logList['dataList'] = $fieldTrackingLogList;
            return $logList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info(" fieldTrackingLogList   getLog " . $throwable->__toString());
        }
    }

    /**
     * Check priority exit count
     *
     */
    public function checkPriorityExist($appData, $priority)
    {
        try {
            return FieldTrackingLog::where('cc_push_sub_stage_priority', $priority)->where('lead_id', $appData->lead_id)->where('quote_id', $appData->quote_id)->count();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository checkPriorityExist " . $throwable->__toString());
        }
    }
    /**
     * update fieldtracking tag
     *
     */
    public function updateCCPushTag($applicationData)
    {
        try {
            $trackedValue = FieldTrackingLog::where('mobile_number', (string)$applicationData->mobile_number)
                ->where('quote_id', (string)$applicationData->quote_id)
                ->where('lead_id', $applicationData->lead_id)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($trackedValue) {
                FieldTrackingLog::where('_id', $trackedValue->_id)->update(['cc_push_tag' => 1]);
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository updateCCPushTag " . $throwable->__toString());
        }
    }

    /**
     * Update CC Tag data
     *
     */
    public function ccPushTagUpdate($quoteId)
    {
        try {
            $trackedValue = FieldTrackingLog::where('quote_id', (string)$quoteId)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($trackedValue) {
                return FieldTrackingLog::where('_id', $trackedValue->_id)->update(['cc_push_tag' => 1]);
            }
            return null;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingRepository ccPushTagUpdate " . $throwable->__toString());
        }
    }
}
