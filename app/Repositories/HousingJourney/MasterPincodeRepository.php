<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterPincode;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use GuzzleHttp\Exception\ClientException;

class MasterPincodeRepository
{
    public function save($data)
    {
        try {
            if ($data['pincode_id'] == null) {
                return HjMasterPincode::Create([
                    'code' => $data['code'], 'area' => $data['area'], 'city' => $data['area'],
                    'district' => $data['district'], 'is_active' => $data['is_active'], 'is_serviceable' => $data['is_active'], 'state' => $data['state'],
                ]);
            } else {
                return HjMasterPincode::where('id', $data['pincode_id'])
                    ->update([
                        'code' => $data['code'], 'area' => $data['area'], 'city' => $data['area'],
                        'district' => $data['district'], 'is_active' => $data['is_active'], 'state' => $data['state'],
                    ]);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterProductRepository save " . $throwable->__toString());
        }
    }
    /**
     * Get pincode detail
     *
     * @param $reqest
     */
    public function list($request)
    {
        try {

            $query = HjMasterPincode::query();
            if (empty($request['code'] === false) && $request['code'] != 'null' && $request['code'] != '') {
                $keyword = $request['code'];

                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('code', $keyword);
                    $query->orWhere('area', 'LIKE', '%' . $keyword . '%');
                    $query->orwhere('district', 'LIKE', '%' . $keyword . '%');
                    $query->orwhere('state', 'LIKE', '%' . $keyword . '%');
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
            $pincode = $query->select('*')->orderBy('id', 'desc')->get();
            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $pincode;
            return $menuData;
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPincodeRepository list" . $throwable->__toString());
        }
    }
    /**
     * Get pincode detail
     *
     * @param $reqest
     */
    public function getPincodeData($request)
    {
        try {

            return HjMasterPincode::select('area', 'city', 'code', 'district', 'id', 'state')->where('code', $request['code'])
                ->where('is_serviceable', 1)
                ->where('is_active', 1)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPincodeRepository getPincodeData" . $throwable->__toString());
        }
    }
    public function filter()
    {
        try {
            $query = HjMasterPincode::query();
            $query->select('id', 'code', 'area');
            $filterdata['pincode'] = $query->orderBy('id', 'desc')->get();
            return $filterdata;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("filter " . $throwable->__toString());
        }
    }
    public function edit($pincodeId)
    {
        try {
            return HjMasterPincode::select('*')
                ->where('id', $pincodeId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("edit : " . $throwable->__toString());
        }
    }
}
