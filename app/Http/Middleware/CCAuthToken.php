<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Crypt;
use App\Repositories\ApiLogRepository;
use Throwable;

class CCAuthToken extends Service
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    private $apiLogRepo;
    public function __construct(ApiLogRepository $apiLogRepo)
    {
        $this->apiLogRepo = $apiLogRepo;
    }
    public function handle($request, Closure $next)
    {
        try {
            $knownRequest = false;
            // cc request.
            if ($request->header('X-Auth-CC-Token') && $request->header('X-Api-Source') && $request->header('X-Api-Source-Page') && $request->header('X-Api-Type')) {
                // return failure if conditions not matched.
                if (in_array($request->header('X-Api-Source'), config('constants/apiSource')) && in_array($request->header('X-Api-Source-Page'), config('constants/apiSourcePage')) && in_array($request->header('X-Api-Type'), config('constants/apiType')) && $request->header('X-Api-Type') !== config('constants/apiType.AUTHENTICATE')) {
                    $knownRequest = true;
                } else {
                    // failure log.
                    // prepare log data.
                    $logData['api_source'] = $request->header('X-Api-Source');
                    $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                    $logData['api_type'] = $request->header('X-Api-Type');
                    $logData['api_header'] = $request->header();
                    $logData['api_url'] = $request->url();
                    $logData['api_request_type'] = config('constants/apiType.REQUEST');
                    $logData['api_data'] = $request->all();
                    $logData['api_status_code'] = config('journey/http-status.bad-request.code');
                    $logData['api_status_message'] = config('journey/http-status.bad-request.message');
                    $this->apiLogRepo->save($logData);
                    return $this->responseJson(
                        config('journey/http-status.bad-request.status'),
                        config('journey/http-status.bad-request.message'),
                        config('journey/http-status.bad-request.code'),
                        []
                    );
                }
            } else {
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = $request->all();
                $logData['api_status_code'] = config('journey/http-status.unauthorized.code');
                $logData['api_status_message'] = config('journey/http-status.unauthorized.message');
                $this->apiLogRepo->save($logData);
                return $this->responseJson(
                    config('journey/http-status.unauthorized.status'),
                    config('journey/http-status.unauthorized.message'),
                    config('journey/http-status.unauthorized.code'),
                    []
                );
            }

            // Known request progress.
            if ($knownRequest) {
                $csrfToken = $request->header('X-Auth-CC-Token');
                $authTokenType = Crypt::decrypt($csrfToken);
                $token = Redis::get($authTokenType);
                if (isset($csrfToken) && $csrfToken !== '' && isset($authTokenType) && $authTokenType !== '' && isset($token) && $token !== '' && $csrfToken == $token) {
                    // prepare log data.
                    $logData['api_source'] = $request->header('X-Api-Source');
                    $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                    $logData['api_type'] = $request->header('X-Api-Type');
                    $logData['api_header'] = $request->header();
                    $logData['api_url'] = $request->url();
                    $logData['api_request_type'] = config('constants/apiType.REQUEST');
                    $logData['api_data'] = $request->all();
                    $logData['api_status_code'] = config('journey/http-status.success.code');
                    $logData['api_status_message'] = config('journey/http-status.success.message');
                    $this->apiLogRepo->save($logData);
                } else {
                    // prepare log data.
                    $logData['api_source'] = $request->header('X-Api-Source');
                    $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                    $logData['api_type'] = $request->header('X-Api-Type');
                    $logData['api_header'] = $request->header();
                    $logData['api_url'] = $request->url();
                    $logData['api_request_type'] = config('constants/apiType.REQUEST');
                    $logData['api_data'] = $request->all();
                    $logData['api_status_code'] = config('journey/http-status.unauthorized.code');
                    $logData['api_status_message'] = config('journey/http-status.unauthorized.message');
                    $this->apiLogRepo->save($logData);
                    return $this->responseJson(
                        config('journey/http-status.unauthorized.status'),
                        config('journey/http-status.unauthorized.message'),
                        config('journey/http-status.unauthorized.code'),
                        []
                    );
                }
                return $next($request);
            } else {
                // failure log.
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = $request->all();
                $logData['api_status_code'] = config('journey/http-status.bad-request.code');
                $logData['api_status_message'] = config('journey/http-status.bad-request.message');
                $this->apiLogRepo->save($logData);
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable $e) {
            return $this->responseJson(
                config('journey/http-status.unauthorized.status'),
                config('journey/http-status.unauthorized.message'),
                config('journey/http-status.unauthorized.code'),
                []
            );
        }
    }
}
