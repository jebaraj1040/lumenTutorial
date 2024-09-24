<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\BvnCallRepository;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;

class BvnCallService extends Service
{
    use CrmTrait;

    private $repo;
    /**
     * Create a new Service instance.
     *
     * @param
     * @return void
     */
    public function __construct(BvnCallRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * list Bvn call details,
     *
     * @param
     * @return void
     */
    public function list(Request $request)
    {
        try {
            $bvnCallList = $this->repo->list($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $bvnCallList
            );
        } catch (Throwable | ClientException $throwable) {
            Log::info("BvnCallService -  list " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }

    /**
     * Export Bvn Calls Details.
     *
     * @param
     * @return void
     */
    public function exportBvnCallDetails(Request $request)
    {
        try {
            $repository = $this->repo;
            $data['methodName'] = 'list';
            $data['fileName'] = 'Bvn-calls-Report-';
            $data['moduleName'] = 'Bvn-calls';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable | ClientException $throwable) {
            Log::info("BvnCallService -  exportBvnCallDetails " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }
}
