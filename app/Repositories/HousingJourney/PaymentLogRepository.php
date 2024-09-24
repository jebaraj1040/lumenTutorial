<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\MongoLog\PaymentLog;
use App\Utils\CrmTrait;

class PaymentLogRepository
{
    use CrmTrait;
    /**
     * Save the lead payment log
     *
     * @param $request
     * @return mixed
     */
    public function save($request)
    {
        try {
            $dataExist = PaymentLog::where('lead_id', $request['lead_id'])
                ->where('quote_id', $request['quote_id'])
                ->where('payment_transaction_id', $request['payment_transaction_id'])
                ->where('api_type', 'PAYMENT_SUCCESS')->count();
            if ($dataExist == 0) {
                return PaymentLog::create($request);
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PaymentLogRepository save: " . $throwable->__toString());
        }
    }

    /**
     * View payment details
     *
     */
    public function view($id)
    {
        try {
            return PaymentLog::where('id', $id)->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("PaymentLogRepository view" . $throwable->__toString());
        }
    }

    public function getPaymentLog($request, $offset = null)
    {
        try {
            $query = PaymentLog::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
                    $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('payment_transaction_id', 'LIKE', '%' . $keyword . '%');
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
            $getPaymentLogDataList = $query->orderby('created_at', 'DESC')->get();
            $getPaymentLogList = [];
            $getPaymentLogList['totalLength'] =  $totalLength;
            $getPaymentLogList['dataList'] = $getPaymentLogDataList;
            return $getPaymentLogList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("Repository : PaymentLogRepository , Method : getPaymentLog : %s", $throwable->__toString());
        }
    }
    public function getFilterData($constant)
    {
        try {
            return config(sprintf("constants/%s", $constant));
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getFilterData " . $throwable->__toString());
        }
    }
}
