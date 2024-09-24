<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\WebSubmission;
use App\Utils\CrmTrait;

class WebSubmissionsRepository
{
    use CrmTrait;
    /**
     * upsertWebSubmission WebSubmission data
     *
     */

    public function webSubmissionsRepoList($request, $offset = null)
    {
        try {
            $query = WebSubmission::query();
            $query = $this->applyFilter($query, $request, 'websubmission');
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('mobile_number', $keyword);
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                });
            }
            $sourcePage  = str_replace('-', '_', strtoupper($request->sourcePage));
            $totalLength = $query->where('source_page', '=', $sourcePage)->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            if (empty($offset === false) && $offset != 'null' && $offset != '') {
                $limit = (int)env('EXPORT_EXCEL_LIMIT');
                $query->offset($offset)->limit($limit);
            }
            $getWebSubmissionDataList = $query->where('source_page', '=', $sourcePage)
                ->with('pincodeData:id,code')
                ->with('masterProductData:id,name')
                ->orderby('created_at', 'DESC')
                ->get();

            if ($request->action == 'download') {
                foreach ($getWebSubmissionDataList as $key => $item) {
                    if ($item->pincodeData) {
                        $getWebSubmissionDataList[$key]['pincode_id'] =  $item->pincodeData->code;
                    } else {
                        $getWebSubmissionDataList[$key]['pincode_id'] = null;
                    }
                    if ($item->masterProductData) {
                        $getWebSubmissionDataList[$key]['master_product_id'] =  $item->masterProductData->name;
                    } else {
                        $getWebSubmissionDataList[$key]['master_product_id'] = null;
                    }
                    unset($getWebSubmissionDataList[$key]['pincodeData']);
                    unset($getWebSubmissionDataList[$key]['masterProductData']);
                }
            }
            $webSubmissionDataListData['totalLength'] =  $totalLength;
            $webSubmissionDataListData['dataList'] = $getWebSubmissionDataList;
            return $webSubmissionDataListData;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("Repository : WebSubmissionsRepository ,
             Method : webSubmissionsRepoList : %s", $throwable->__toString());
        }
    }
}
