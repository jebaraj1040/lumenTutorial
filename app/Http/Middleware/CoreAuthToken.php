<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Crypt;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use Throwable;

class CoreAuthToken extends Service
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    private $masterApiLogRepo;
    public function __construct(MasterApiLogRepository $masterApiLogRepo)
    {
        $this->masterApiLogRepo = $masterApiLogRepo;
    }
    public function handle($request, Closure $next)
    {
        try {
            $response = [];
            $knownRequest = false;
            $requestUrl = $request->url . $request->path();
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.AUTHENTICATE');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $masterApiLogData = $this->masterApiLogRepo->save($requestData);

            if ($request->header('X-Auth-Token')) {
                $knownRequest = true;
            } else {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                $response = $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
                $this->masterApiLogRepo->update($masterApiLogData['id'], json_encode($response), $requestData['customHeader']['X-Api-Status']);
                return $response;
            }

            // Known request progress.
            if ($knownRequest) {

                $csrfToken = $request->header('X-Auth-Token');
                $authTokenType = Crypt::decrypt($csrfToken);
                $token = Redis::get($authTokenType);
                if (empty($authTokenType) === true || empty($csrfToken) === true || empty($token) == true || $csrfToken != $token) {
                    $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                    $response = $this->responseJson(
                        config('journey/http-status.unauthorized.status'),
                        config('journey/http-status.unauthorized.message'),
                        config('journey/http-status.unauthorized.code'),
                        []
                    );
                    $this->masterApiLogRepo->update($masterApiLogData['id'], json_encode($response), $requestData['customHeader']['X-Api-Status']);
                    return $response;
                }
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.SUCCESS');
                $this->masterApiLogRepo->update($masterApiLogData['id'], json_encode($response), $requestData['customHeader']['X-Api-Status']);
                return $next($request);
            } else {

                // failure log. 
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                $this->masterApiLogRepo->update($masterApiLogData['id'], json_encode($response), $requestData['customHeader']['X-Api-Status']);
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable) {
            return $this->responseJson(
                config('journey/http-status.unauthorized.status'),
                config('journey/http-status.unauthorized.message'),
                config('journey/http-status.unauthorized.code'),
                []
            );
        }
    }
}
