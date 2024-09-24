<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Repositories\ApiLogRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Crypt;
use Throwable;

// Define constant
define('API_TYPE_REQUEST',  config('constants/apiType.REQUEST'));


class OtpMiddleware extends Service
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    private $apiLogRepo;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth, ApiLogRepository $apiLogRepo)
    {
        $this->auth = $auth;
        $this->apiLogRepo = $apiLogRepo;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $requestPath = explode("/", $request->path());
            $endPoint = end($requestPath);
            if ($endPoint == "send") {
                // web site request.
                if ($request->header('X-Api-Source') && $request->header('X-Api-Source-Page') && $request->header('X-Api-Type') && $request->header('X-Session-Token')) {
                    // return failure if conditions not matched.
                    if (in_array($request->header('X-Api-Source'), config('constants/apiSource')) && in_array($request->header('X-Api-Source-Page'), config('constants/apiSourcePage')) && in_array($request->header('X-Api-Type'), config('constants/apiType'))) {
                        // check X-Session-Token in redis.
                        $sessionAuthToken = $request->header('X-Session-Token');
                        $authTokenType = Crypt::decrypt($sessionAuthToken);
                        $token = Redis::get($authTokenType);
                        if (isset($sessionAuthToken) && $sessionAuthToken !== '' && isset($authTokenType) && $authTokenType !== '' && isset($token) && $token !== '' && $sessionAuthToken == $token) {
                            // prepare log data.
                            $logData['api_source'] = $request->header('X-Api-Source');
                            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                            $logData['api_type'] = $request->header('X-Api-Type');
                            $logData['api_header'] = $request->header();
                            $logData['api_url'] = $request->url();
                            $logData['api_request_type'] = API_TYPE_REQUEST;
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
                            $logData['api_request_type'] = API_TYPE_REQUEST;
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
                    }
                } else {
                    // prepare log data.
                    $logData['api_source'] = $request->header('X-Api-Source');
                    $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                    $logData['api_type'] = $request->header('X-Api-Type');
                    $logData['api_header'] = $request->header();
                    $logData['api_url'] = $request->url();
                    $logData['api_request_type'] = API_TYPE_REQUEST;
                    $logData['api_data'] = $request->all();
                    $logData['api_status_code'] = config('journey/http-status.unauthorized.code');
                    $logData['api_status_message'] = config('journey/http-status.unauthorized.message');
                    $apiLogRepo = new ApiLogRepository();
                    $apiLogRepo->save($logData);
                    return $this->responseJson(
                        config('journey/http-status.unauthorized.status'),
                        config('journey/http-status.unauthorized.message'),
                        config('journey/http-status.unauthorized.code'),
                        []
                    );
                }
            }
            if ($endPoint == "verify") {
                if ($request->header('X-Api-Source') && $request->header('X-Api-Source-Page') && $request->header('X-Api-Type') && $request->header('X-Session-Token')) {
                    // return failure if conditions not matched.
                    if (in_array($request->header('X-Api-Source'), config('constants/apiSource')) && in_array($request->header('X-Api-Source-Page'), config('constants/apiSourcePage')) && in_array($request->header('X-Api-Type'), config('constants/apiType'))) {
                        // check X-Session-Token in redis.
                        $csrfToken = $request->header('X-Session-Token');
                        $authTokenType = Crypt::decrypt($csrfToken);
                        $token = Redis::get($authTokenType);
                        if (isset($csrfToken) && $csrfToken !== '' && isset($authTokenType) && $authTokenType !== '' && isset($token) && $token !== '' && $csrfToken == $token) {
                            // prepare log data.
                            $logData['api_source'] = $request->header('X-Api-Source');
                            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                            $logData['api_type'] = $request->header('X-Api-Type');
                            $logData['api_header'] = $request->header();
                            $logData['api_url'] = $request->url();
                            $logData['api_request_type'] = API_TYPE_REQUEST;
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
                            $logData['api_request_type'] = API_TYPE_REQUEST;
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
                    } else {
                        // failure log.
                        // prepare log data.
                        $logData['api_source'] = $request->header('X-Api-Source');
                        $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                        $logData['api_type'] = $request->header('X-Api-Type');
                        $logData['api_header'] = $request->header();
                        $logData['api_url'] = $request->url();
                        $logData['api_request_type'] = API_TYPE_REQUEST;
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
                    $logData['api_request_type'] = API_TYPE_REQUEST;
                    $logData['api_data'] = $request->all();
                    $logData['api_status_code'] = config('journey/http-status.unauthorized.code');
                    $logData['api_status_message'] = config('journey/http-status.unauthorized.message');
                    $apiLogRepo = new ApiLogRepository();
                    $apiLogRepo->save($logData);
                    return $this->responseJson(
                        config('journey/http-status.unauthorized.status'),
                        config('journey/http-status.unauthorized.message'),
                        config('journey/http-status.unauthorized.code'),
                        []
                    );
                }
            }
            return $next($request);
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
