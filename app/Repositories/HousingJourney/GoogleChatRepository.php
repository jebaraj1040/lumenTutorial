<?php

namespace App\Repositories\HousingJourney;

use App\Entities\Service\Googlechat;
use App\Utils\CrmTrait;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleChatRepository
{
    use CrmTrait;
    /*
    * Fetch the Google Chat data
    */
    public function list($request, $offset = null)
    {
        try {
            $query = Googlechat::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('store_code', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('business_name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('user_name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('phone_number', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('email_address', 'LIKE', '%' . $keyword . '%');
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
            $googleChatList = $query->orderBy('id', 'desc')->get();
            $googleChatData['totalLength'] =  $totalLength;
            $googleChatData['dataList'] = $googleChatList;
            return $googleChatData;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("Google chat list " . $throwable->__toString());
        }
    }
}
