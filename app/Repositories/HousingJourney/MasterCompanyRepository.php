<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterCompany;
use GuzzleHttp\Exception\ClientException;


class MasterCompanyRepository
{
    public function save($data)
    {
        try {
            if ($data['company_id'] == null) {
                return HjMasterCompany::Create([
                    'name' => $data['name'], 'handle' => $data['handle'], 'is_active' => $data['is_active']
                ]);
            } else {
                return HjMasterCompany::where('id', $data['company_id'])
                    ->update([
                        'name' => $data['name'],
                        'handle' => $data['handle'], 'is_active' => $data['is_active']
                    ]);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterCompanyRepository save " . $throwable->__toString());
        }
    }
    public function list($request)
    {
        try {
            $query = HjMasterCompany::query();
            if (empty($request->name === false) && $request->name != 'null' && $request->name != '') {
                $keyword = $request->name;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('handle', 'LIKE', '%' . $keyword . '%');
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
            $company = $query->select('*')->orderBy('id', 'desc')->get();
            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $company;
            return $menuData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Companylist : " . $throwable->__toString());
        }
    }
    public function edit($companyId)
    {
        try {
            return HjMasterCompany::select('*')
                ->where('id', $companyId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("edit : " . $throwable->__toString());
        }
    }
}
