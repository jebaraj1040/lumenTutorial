<?php

namespace App\Services;

use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Crypt;
use App\Repositories\ApiLogRepository;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Entities\HousingJourney\HjApplication;
use App\Repositories\HousingJourney\ImpressionRepository;

// Define constant
define('API_SOURCE_PAGE',  config('constants/apiSourcePage'));
define('API_TYPE',  config('constants/apiType'));
define('API_SOURCE',  config('constants/apiSource'));


class AuthService extends Service
{
    /**
     * create journey auth token
     *
     * @param  Request $request
     * @return mixed
     */
    public function getJourneyAuthToken(Request $request, ApiLogRepository $apiLogRepo): mixed
    {
        try {
            // Bearer Token, api-type, api-source api-source-page journey based log.
            if (
                $request->bearerToken() &&
                $request->header('X-Api-Source') &&
                $request->header('X-Api-Type') &&
                $request->header('X-Api-Source-Page') &&
                (in_array(
                    $request->header('X-Api-Source-Page'),
                    API_SOURCE_PAGE
                ))  &&
                (in_array(
                    $request->header('X-Api-Type'),
                    API_TYPE
                ))  &&
                (in_array(
                    $request->header('X-Api-Source'),
                    API_SOURCE
                ))
            ) {
                // get application from Bearer Token.
                $application = HjApplication::select('quote_id', 'auth_token')->where('auth_token', $request->bearerToken())->first();
                if ($application) {
                    // prepare log data.
                    $logData['api_source'] = $request->header('X-Api-Source');
                    $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                    $logData['api_type'] = $request->header('X-Api-Type');
                    $logData['api_header'] = $request->header();
                    $logData['api_url'] = $request->url();
                    $logData['api_request_type'] = config('constants/apiType.REQUEST');
                    $logData['api_data'] = null;
                    $logData['api_status_code'] = config('journey/http-status.success.code');
                    $logData['api_status_message'] = config('journey/http-status.success.message');
                    $apiLogRepo->save($logData);
                    return $this->responseJson(
                        config('journey/http-status.success.status'),
                        config('journey/http-status.success.message'),
                        config('journey/http-status.success.code'),
                        [
                            'auth_token' => $application['auth_token']
                        ]
                    );
                } else {
                    return $this->responseJson(
                        config('journey/http-status.bad-request.status'),
                        config('journey/http-status.bad-request.message'),
                        config('journey/http-status.bad-request.code'),
                        []
                    );
                }
            } else {
                // default track them to api log.
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = null;
                $logData['api_status_code'] = config('journey/http-status.bad-request.code');
                $logData['api_status_message'] = config('journey/http-status.bad-request.message');
                $apiLogRepo->save($logData);
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable | HttpClientException $throwable) {
            // default track them to api log.
            // prepare log data.
            $logData['api_source'] = $request->header('X-Api-Source');
            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
            $logData['api_type'] = $request->header('X-Api-Type');
            $logData['api_header'] = $request->header();
            $logData['api_url'] = $request->url();
            $logData['api_request_type'] = config('constants/apiType.REQUEST');
            $logData['api_data'] = null;
            $logData['api_status_code'] = config('journey/http-status.error.code');
            $logData['api_status_message'] = config('journey/http-status.error.message');
            $apiLogRepo->save($logData);
            return $this->responseJson(
                config('journey/http-status.bad-request.status'),
                config('journey/http-status.bad-request.message'),
                config('journey/http-status.bad-request.code'),
                []
            );
        }
    }
    /**
     *  Create core auth token
     *
     * @param  Request $request
     * @return mixed
     */
    public function createCoreAuthToken(Request $request, MasterApiLogRepository $masterApiLogRepo): mixed
    {
        try {
            $customHeader['X-Api-Source'] =  config('constants/masterApiSource.CORE');
            $customHeader['X-Api-Type'] = config('constants/masterApiType.CORE_AUTH_TOKEN_CREATION');
            $customHeader['X-Api-Url'] = env('APP_URL') . 'core-auth';
            $requestData['request'] = $request;
            if ($request->getUser() && $request->getPassword()) {
                if ($request->getUser() == env('CORE_USER_NAME') && $request->getPassword() == env('CORE_PASSWORD')) {
                    $userCredentital = $request->getUser() . $request->getPassword();
                    $authTokenType = $userCredentital . bin2hex(random_bytes(10));
                    $token = Crypt::encrypt($authTokenType);
                    $expirationTime = 24 * 60 * 60; // 86400 sec (1 day)
                    $redis = Redis::connection();
                    $redis->set(
                        $authTokenType,
                        $token,
                        'EX',
                        $expirationTime
                    );
                    $value = Redis::get($authTokenType);
                    $customHeader['X-Api-Status'] = config('constants/masterApiStatus.INIT');
                    $requestData['customHeader'] = $customHeader;
                    $masterApiLogRepo->save($requestData);
                    return $this->responseJson(
                        config('journey/http-status.success.status'),
                        config('journey/http-status.success.message'),
                        config('journey/http-status.success.code'),
                        ['token' => $value]
                    );
                } else {
                    $customHeader['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                    $requestData['customHeader'] = $customHeader;
                    $masterApiLogRepo->save($requestData);
                    return $this->responseJson(
                        config('journey/http-status.unauthorized.status'),
                        config('journey/http-status.unauthorized.message'),
                        config('journey/http-status.unauthorized.code'),
                        []
                    );
                }
            } else {
                $customHeader['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                $requestData['customHeader'] = $customHeader;
                $masterApiLogRepo->save($requestData);
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable | HttpClientException $throwable) {
            // default track them to api log.
            $requestData['request'] = $request;
            $customHeader['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
            $requestData['customHeader'] = $customHeader;
            $masterApiLogRepo->save($requestData);
            return $this->responseJson(
                config('journey/http-status.bad-request.status'),
                config('journey/http-status.bad-request.message'),
                config('journey/http-status.bad-request.code'),
                []
            );
        }
    }
    /**
     * create web site session token
     *
     * @param  Request $request
     * @return mixed
     */
    public function createWebsiteSessionToken(Request $request, ApiLogRepository $apiLogRepo): mixed
    {
        try {
            // X-Auth-Token-Type, X-Api-Source, X-Api-Source-Page, X-Api-Typ journey based log.
            if ($request->header('X-Auth-Token-Type') && $request->header('X-Api-Source') && $request->header('X-Api-Type') && $request->header('X-Api-Source-Page') && (in_array($request->header('X-Api-Source-Page'), API_SOURCE_PAGE))  && (in_array($request->header('X-Api-Type'), API_TYPE)) && (in_array($request->header('X-Api-Source'), API_SOURCE))) {
                $tokenType = $request->header('X-Auth-Token-Type') . strtotime(date("Y-m-d H:i:s"));
                $token = Crypt::encrypt($tokenType);
                $expirationTime = 24 * 60 * 60; // 86400 sec (1 day)
                $redis = Redis::connection();
                $redis->set(
                    $tokenType,
                    $token,
                    'EX',
                    $expirationTime
                );
                $token = Redis::get($tokenType);
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = null;
                $logData['api_status_code'] = config('journey/http-status.success.code');
                $logData['api_status_message'] = config('journey/http-status.success.message');
                $apiLogRepo->save($logData);
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    ['session_token' => $token]
                );
            } else {
                // default track them to api log.
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] =  null;
                $logData['api_status_code'] = config('journey/http-status.bad-request.code');
                $logData['api_status_message'] = config('journey/http-status.bad-request.message');
                $apiLogRepo->save($logData);
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable | HttpClientException $throwable) {
            // default track them to api log.
            // prepare log data.
            $logData['api_source'] = $request->header('X-Api-Source');
            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
            $logData['api_type'] = $request->header('X-Api-Type');
            $logData['api_header'] = $request->header();
            $logData['api_url'] = $request->url();
            $logData['api_request_type'] = config('constants/apiType.REQUEST');
            $logData['api_data'] = null;
            $logData['api_status_code'] = config('journey/http-status.error.code');
            $logData['api_status_message'] = config('journey/http-status.error.message');
            $apiLogRepo->save($logData);
            return $this->responseJson(
                config('journey/http-status.bad-request.status'),
                config('journey/http-status.bad-request.message'),
                config('journey/http-status.bad-request.code'),
                []
            );
        }
    }

    /**
     * create cc auth token
     *
     * @param  Request $request
     * @return mixed
     */
    public function createCCAuthToken(Request $request, ApiLogRepository $apiLogRepo): mixed
    {
        try {
            // X-Auth-CC-Token, X-Api-Source, X-Api-Type, X-Api-Source-Page journey based log.
            if (
                $request->header('X-Auth-CC-Token') &&
                $request->header('X-Api-Source') &&
                $request->header('X-Api-Type') &&
                $request->header('X-Api-Source-Page') &&
                (in_array(
                    $request->header('X-Api-Source-Page'),
                    API_SOURCE_PAGE
                ))  &&
                (in_array(
                    $request->header('X-Api-Type'),
                    API_TYPE
                ))  &&
                (in_array(
                    $request->header('X-Api-Source'),
                    API_SOURCE
                ))
            ) {
                $ccToken = $request->header('X-Auth-CC-Token');
                if ($ccToken !== env('CC_TOKEN')) {
                    return $this->responseJson(
                        config('journey/http-status.bad-request.status'),
                        config('journey/http-status.bad-request.message'),
                        config('journey/http-status.bad-request.code'),
                        []
                    );
                }
                $token = Crypt::encrypt($ccToken);
                $expirationTime = 24 * 60 * 60; // 86400 sec (1 day)
                $redis = Redis::connection();
                $redis->set(
                    $ccToken,
                    $token,
                    'EX',
                    $expirationTime
                );
                $value = Redis::get($ccToken);
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = null;
                $logData['api_status_code'] = config('journey/http-status.success.code');
                $logData['api_status_message'] = config('journey/http-status.success.message');
                $apiLogRepo->save($logData);

                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    ['token' => $value]
                );
            } else {
                // default track them to api log.
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = null;
                $logData['api_status_code'] = config('journey/http-status.bad-request.code');
                $logData['api_status_message'] = config('journey/http-status.bad-request.message');
                $apiLogRepo->save($logData);
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable | HttpClientException $throwable) {
            // default track them to api log.
            // prepare log data.
            $logData['api_source'] = $request->header('X-Api-Source');
            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
            $logData['api_type'] = $request->header('X-Api-Type');
            $logData['api_header'] = $request->header();
            $logData['api_url'] = $request->url();
            $logData['api_request_type'] = config('constants/apiType.REQUEST');
            $logData['api_data'] = null;
            $logData['api_status_code'] = config('journey/http-status.error.code');
            $logData['api_status_message'] = config('journey/http-status.error.message');
            $apiLogRepo->save($logData);
            return $this->responseJson(
                config('journey/http-status.bad-request.status'),
                config('journey/http-status.bad-request.message'),
                config('journey/http-status.bad-request.code'),
                []
            );
        }
    }

    /**
     * Remove redis auth token
     *
     * @param  Request $request
     */
    public function removeRedisToken(Request $request)
    {
        try {
            $apiLogRepo = new ApiLogRepository();
            $appRepo = new ApplicationRepository();
            $impRepo = new ImpressionRepository();
            // X-Auth-CC-Token, X-Api-Source, X-Api-Type, X-Api-Source-Page journey based log.
            if (
                $request->header('X-Api-Source') &&
                $request->header('X-Api-Type') &&
                $request->header('X-Api-Source-Page') &&
                (in_array(
                    $request->header('X-Api-Source-Page'),
                    API_SOURCE_PAGE
                ))  &&
                (in_array(
                    $request->header('X-Api-Type'),
                    API_TYPE
                ))  &&
                (in_array(
                    $request->header('X-Api-Source'),
                    API_SOURCE
                ))
            ) {
                if (!isset($request->quote_id)) {
                    $this->handleBadRequest();
                }
                $appData = $appRepo->getQuoteIdDetails($request->all());
                if (empty($appData) || !$appData) {
                    $this->handleBadRequest();
                }
                $redis = Redis::connection();
                $leadID =
                    $appReqData['lead_id'] = $appData['lead_id'];
                $appReqData['quote_id'] = $appData['quote_id'];
                if (($appData->is_paid == 1 && $appData->is_purchased == 1) || ($appData->is_bre_execute == 0 && $appData->is_paid == 0)) {
                    $appReqData['master_product_step_id'] = 15;
                    // save into impression
                    $impression['lead_id'] = $appData['lead_id'];
                    $impression['quote_id'] = $appData['quote_id'];
                    $impression['master_product_id'] = $appData['master_product_id'];
                    $impression['master_product_step_id'] = 15;
                    $impRepo->save($impression);
                }

                $appRepo->save($appReqData);
                $redis->del($leadID);
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = null;
                $logData['api_status_code'] = config('journey/http-status.success.code');
                $logData['api_status_message'] = config('journey/http-status.success.message');
                $apiLogRepo->save($logData);

                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    []
                );
            } else {
                // default track them to api log.
                // prepare log data.
                $logData['api_source'] = $request->header('X-Api-Source');
                $logData['api_source_page'] = $request->header('X-Api-Source-Page');
                $logData['api_type'] = $request->header('X-Api-Type');
                $logData['api_header'] = $request->header();
                $logData['api_url'] = $request->url();
                $logData['api_request_type'] = config('constants/apiType.REQUEST');
                $logData['api_data'] = null;
                $logData['api_status_code'] = config('journey/http-status.bad-request.code');
                $logData['api_status_message'] = config('journey/http-status.bad-request.message');
                $apiLogRepo->save($logData);
                $this->handleBadRequest();
            }
        } catch (Throwable | HttpClientException $throwable) {
            // default track them to api log.
            // prepare log data.
            $logData['api_source'] = $request->header('X-Api-Source');
            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
            $logData['api_type'] = $request->header('X-Api-Type');
            $logData['api_header'] = $request->header();
            $logData['api_url'] = $request->url();
            $logData['api_request_type'] = config('constants/apiType.REQUEST');
            $logData['api_data'] = null;
            $logData['api_status_code'] = config('journey/http-status.error.code');
            $logData['api_status_message'] = config('journey/http-status.error.message');
            $apiLogRepo->save($logData);
            $this->handleBadRequest();
        }
    }

    /**
     * Handle bad request 
     *
     *
     * @return mixed
     */
    public function handleBadRequest(): mixed
    {
        return $this->responseJson(
            config('journey/http-status.bad-request.status'),
            config('journey/http-status.bad-request.message'),
            config('journey/http-status.bad-request.code'),
            []
        );
    }
}
