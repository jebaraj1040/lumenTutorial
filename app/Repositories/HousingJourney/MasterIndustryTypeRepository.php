<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterIndustryType;
use App\Entities\HousingJourney\HjMasterIndustrySegment;
use GuzzleHttp\Exception\ClientException;

class MasterIndustryTypeRepository
{
    /**
     * save industry type
     *
     */
    public function save($data)
    {
        try {
            if ($data['industry_id'] == null) {
                return HjMasterIndustryType::Create([
                    'name' => $data['name'],
                    'handle' => $data['handle']
                ]);
            } else {
                return HjMasterIndustryType::where('id', $data['industry_id'])
                    ->update([
                        'name' => $data['name'],
                        'handle' => $data['handle'], 'is_active' => $data['is_active']
                    ]);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterIndustryRepository save " . $throwable->__toString());
        }
    }
    public function edit($industryId)
    {
        try {
            return HjMasterIndustryType::select('id', 'name', 'handle', 'is_active')
                ->where('id', $industryId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("edit : " . $throwable->__toString());
        }
    }
    public function list($request)
    {
        try {
            $query = HjMasterIndustryType::query();
            if (empty($request->name === false) && $request->name != 'null' && $request->name != '') {
                $keyword = $request->name;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                });
            }
            if (empty($request->status === false) && ($request->status != 'null' && $request->status != '')) {
                $request->status = $request->status == 'Active' ? 1 : 0;
                $query->where('is_active', $request->status);
            }
            $totalLength = $query->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            $industry = $query->select('id', 'name', 'handle', 'is_active')->orderBy('id', 'desc')->get();
            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $industry;
            return $menuData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Industrylist : " . $throwable->__toString());
        }
    }
    /**
     * get industry segment id
     *
     */
    public function getIndustrySegmentId($indusSegmentName)
    {
        try {
            return HjMasterIndustrySegment::where('name', $indusSegmentName)->value('id');
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Industrylist : " . $throwable->__toString());
        }
    }
}
