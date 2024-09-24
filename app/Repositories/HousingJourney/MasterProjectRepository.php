<?php

namespace App\Repositories\HousingJourney;

use App\Entities\HousingJourney\HjMasterPincode;
use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterProject;
use GuzzleHttp\Exception\ClientException;

class MasterProjectRepository
{
    public function save($data)
    {
        try {
            if ($data['project_id'] == null) {
                return HjMasterProject::Create($data);
            } else {
                return HjMasterProject::where('id', $data['project_id'])
                    ->update([
                        'name' =>  $data['name'], 'builder' => $data['builder'], 'code' => $data['code'],
                        'pincode_id' => $data['pincode_id'], 'is_approved' => $data['is_approved'],
                        'is_active' => $data['is_active']
                    ]);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterProjectRepository save " . $throwable->__toString());
        }
    }
    public function list($request)
    {
        try {
            $query = HjMasterProject::query();
            if (empty($request->name === false) && $request->name != 'null' && $request->name != '') {
                $keyword = $request->name;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('code', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('name_handle', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('pincode_id', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('builder', 'LIKE', '%' . $keyword . '%');
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
            $project = $query->select('*')->orderBy('id', 'desc')->get();
            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $project;
            return $menuData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Projectlist : " . $throwable->__toString());
        }
    }
    public function edit($projectId)
    {
        try {
            $project = HjMasterProject::select('*')
                ->where('id', $projectId)->first();
            $pincode = HjMasterPincode::find($project->pincode_id);
            $project['pin'] = $pincode;
            return $project;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("edit : " . $throwable->__toString());
        }
    }
}
