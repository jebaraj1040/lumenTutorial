<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Illuminate\Http\Request;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;
use App\Repositories\HousingJourney\WebSubmissionsRepository;

class WebSubmissionsService  extends Service
{
     use CrmTrait;
     private $webSubmissionsData;
     /**
      * Create a new Service instance.
      *
      * @param
      * @return void
      */
     public function __construct(
          WebSubmissionsRepository $webSubmissionsData,
     ) {
          $this->webSubmissionsData = $webSubmissionsData;
     }
     /**
      * Create and Update  Menu.
      *
      * @param
      * @return void
      */
     public function getWebSubmissionsList(Request $request)
     {
          try {
               $webSubmissionsData = $this->webSubmissionsData->webSubmissionsRepoList($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $webSubmissionsData
               );
          } catch (Throwable  | ClientException $throwable) {
               throw new Throwable(Log::info("Service : WebSubmissionsService , Method : getWebSubmissionsList : %s" . $throwable->__toString()));
          }
     }
     public function getWebSubmissionsExport(Request $request)
     {
          try {
               $repository = new WebSubmissionsRepository();
               $data['methodName'] = 'webSubmissionsRepoList';
               $data['fileName'] = ucwords($request->sourcePage, "-");
               $data['moduleName'] = 'Web-Submissions';
               return $this->exportData($request, $repository, $data);
          } catch (Throwable | ClientException $throwable) {
               throw new (sprintf("WebSubmissionsService getWebSubmissionsExport : %s", $throwable->__toString()));
          }
     }
}
