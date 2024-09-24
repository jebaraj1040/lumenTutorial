<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\GoogleChatRepository;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;

class GoogleChatService extends Service
{
    use CrmTrait;

    private $repo;
    /**
     * Create a new Service instance.
     *
     * @param
     * @return void
     */
    public function __construct(GoogleChatRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * list Google Chat details,
     *
     * @param
     * @return void
     */
    public function list(Request $request)
    {
        try {
            $googleChatList = $this->repo->list($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $googleChatList
            );
        } catch (Throwable | ClientException $throwable) {
            Log::info("GoogleChatService -  list " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }

    /**
     * Export Google Chat Details.
     *
     * @param
     * @return void
     */
    public function exportGoogleChatDetails(Request $request)
    {
        try {
            $repository = $this->repo;
            $data['methodName'] = 'list';
            $data['fileName'] = 'Google-chat-Report-';
            $data['moduleName'] = 'Google-chat';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable | ClientException $throwable) {
            Log::info("GoogleChatService -  exportGoogleChatDetails " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }
}
