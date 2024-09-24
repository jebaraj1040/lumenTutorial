<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\HjMasterProfessionalTypeImport;
use App\Repositories\HousingJourney\MasterApiLogRepository;

class MasterProfessionalTypeService extends Service
{
  /**
   *Save Import File
   *
   */
  public function import(Request $request, MasterApiLogRepository $masterApiLogRepo)
  {
    $file = $request->file('file');
    $file->getClientOriginalExtension();
    $filename = time() . '.' . $file->getClientOriginalExtension();
    $file->move(storage_path('app/excel'), $filename);
    $path = storage_path('app/excel/' . $filename);
    $requestUrl = $request->url . $request->path();
    $requestData['request'] = $request;
    $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
    $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.MASTER_PROFESSIONAL_TYPE_IMPORT');
    $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
    $requestData['customHeader']['X-Api-Url'] = $requestUrl;
    $requestData['request'] = $request;
    $masterApiLogRepo->save($requestData);
    Excel::import(new HjMasterProfessionalTypeImport, $path);
    return $this->responseJson(
      config('journey/http-status.success.status'),
      config('journey/http-status.success.message'),
      config('journey/http-status.success.code'),
      []
    );
  }
}
