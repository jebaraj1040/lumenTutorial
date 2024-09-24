<?php

namespace App\Repositories\HousingJourney;

use App\Entities\Service\Bvncalls;
use App\Utils\CrmTrait;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use Throwable;

class BvnCallRepository
{
    use CrmTrait;
    /*
    * Fetch the Bvn Calls data
    */
    public function list($request, $offset = null)
    {
        try {
            $query = Bvncalls::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('store_code', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('business_name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('customer_phone_number', 'LIKE', '%' . $keyword . '%');
                });
            }
            $totalLength = $query->count();
            $skip = intval($request->skip);
            $limit = intval($request->limit);
            $query->skip($skip)->limit($limit);
            if (empty($offset === false) && $offset != 'null' && $offset != '') {
                $limit = (int)env('EXPORT_EXCEL_LIMIT');
                $query->offset($offset)->limit($limit);
            }
            $bvnCallList = $query->orderBy('id', 'desc')->get();
            $bvnCallData['totalLength'] =  $totalLength;
            $bvnCallData['dataList'] = $bvnCallList;
            return $bvnCallData;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("Bvn calls list " . $throwable->__toString());
        }
    }
}
