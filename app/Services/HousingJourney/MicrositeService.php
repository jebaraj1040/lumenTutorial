<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use App\Repositories\HousingJourney\MicrositeRepository;

class MicrositeService extends Service
{
  use CrmTrait;
  /**
   * Export Log.
   *
   * @param $request
   *
   */
  public function exportList(Request $request)
  {

    try {
      $rules = [
        "fromDate" => "required",
        "toDate"  => "required",
      ];
      $validator = $this->validator($request->all(), $rules);
      if ($validator !== false) {
        return $validator;
      }
      $repository = new MicrositeRepository();
      $datas['methodName'] = 'list';
      $datas['fileName'] = 'Microsite-Report-';
      $datas['moduleName'] = 'Microsite';
      return $this->exportData($request, $repository, $datas);
    } catch (Throwable  | ClientException $throwable) {
      Log::info("MicrositeService  exportList" . $throwable->__toString());
      return $this->responseJson(
        config('crm/http-status.error.status'),
        config('crm/http-status.error.message'),
        config('crm/http-status.error.code'),
        []
      );
    }
  }
  /**
   * getMicrositeList
   *
   * @param  Request $request
   * @return mixed
   */
  public function getMicrositeList(Request $request, MicrositeRepository $micrositeRepo): mixed
  {
    try {
      $logsList = $micrositeRepo->list($request);
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $logsList
      );
    } catch (Throwable   | ClientException $throwable) {
      throw new Throwable(
        Log::info("Service : MicrositeService , Method : getMicrositeList : %s", $throwable->__toString())
      );
    }
  }
}
