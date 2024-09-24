<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\MongoLog\BRELog;
use App\Entities\Service\Microsite;
use App\Utils\CrmTrait;

// Define the constant outside the class

class MicrositeRepository
{
    use CrmTrait;
    /**
     *Get logs from Table
     * @param $request
     */
    public function list($request, $offset = null)
    {
        try {
            $query = Microsite::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('customer_name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('business_name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('mobile_number', $keyword);
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
            $list = $query->orderby('created_at', 'DESC')->get();
            $micrositeList = [];
            $micrositeList['totalLength'] =  $totalLength;
            $micrositeList['dataList'] = $list;
            return $micrositeList;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("MicrositeRepository  list" . $throwable->__toString());
        }
    }
}
