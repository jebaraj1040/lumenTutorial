<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjAuctionBidForm;
use App\Utils\CrmTrait;
use GuzzleHttp\Exception\ClientException;

class AuctionBidFormDetailRepository
{
    use CrmTrait;
    public function save($request)
    {
        try {
            return HjAuctionBidForm::create($request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("PersonalDetailRepository save" . $throwable->__toString());
        }
    }
    public function list($request, $offset = null)
    {
        try {
            $query = HjAuctionBidForm::query();
            $totalLength = $query->count();
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('bank_name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('project_name', 'LIKE', '%' . $keyword . '%');
                });
            }
            $query = $this->applyFilter($query, $request);
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            if (empty($offset === false) && $offset != 'null' && $offset != '') {
                $limit = (int)env('EXPORT_EXCEL_LIMIT');
                $query->offset($offset)->limit($limit);
            }
            $auctionbid = $query->select('*')->orderBy('id', 'desc')->get();
            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $auctionbid;
            return $menuData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Auctionbidlist : " . $throwable->__toString());
        }
    }
}
