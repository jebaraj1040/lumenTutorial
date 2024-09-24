<?php

namespace App\Services;

use App\Repositories\HousingJourney\ApplicationRepository;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Repositories\HousingJourney\MasterProductRepository;
use App\Repositories\UserSessionActivityRepository;
use App\Utils\CrmTrait;

define('API_STATUS_FAILURE', config('journey/http-status.failure.status'));
define('API_STATUS_FAILURE_CODE', config('journey/http-status.failure.code'));
define('API_STATUS_FAILURE_MESSAGE', config('journey/http-status.failure.message'));
define('API_STATUS_SUCCESS_CODE', config('journey/http-status.success.code'));
define('API_STATUS_SUCCESS_MESSAGE', config('journey/http-status.success.message'));
define('API_STATUS_SUCCESS', config('journey/http-status.success.status'));
class UserSessionActivityService extends Service
{
    /**
     * save user Activity
     *
     * @param  Request $request
     *
     */
    use CrmTrait;
    public function save(Request $request)
    {
        try {
            if (isset($request['data']['mfid']) && $request['data']['mfid'] != '' && $request['data']['mfid'] != '') {
                return  $this->saveUserPanActivity($request);
            } else {
                return  $this->saveUserSessionActivity($request);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityService save " . $throwable);
        }
    }
    /**
     * save user session Activity
     *
     * @param $request
     *
     */
    public function saveUserSessionActivity($request)
    {
        try {
            $masterProductRepo = new MasterProductRepository();
            $userSessionRepo = new UserSessionActivityRepository();
            if (isset($request['data']['session_id']) && $request['data']['session_id'] != '' && $request['data']['session_id'] != null) {
                $applicationRepo = new ApplicationRepository();
                $applicationData = $applicationRepo->getAuthTokenByToken($request->bearerToken());
                $userLog['lead_id']  = $request['data']['mlid'] ?? null;
                $userLog['mobile_number']  = $request['data']['muid'] ?? null;
                $userLog['quote_id'] = $request['data']['quote_id'] ?? null;
                $userLog['product_id'] = isset($request['data']['product_id']) ? $masterProductRepo->getProductId($request['data']['product_id']) : null;
                if ($applicationData && $request->header('X-Api-Source') ==  config('constants/apiSource.HOUSING_JOURNEY')) {
                    $userLog['lead_id']  = $applicationData->lead_id ? $applicationData->lead_id : null;
                    $userLog['mobile_number']  = $applicationData->mobile_number ? $applicationData->mobile_number : null;
                    $userLog['quote_id'] = $applicationData->quote_id ?  $applicationData->quote_id : null;
                    $userLog['product_id'] =  $applicationData->master_product_id ? $applicationData->master_product_id : null;
                }
                $userLog['session_id'] = $request['data']['session_id'] ?? null;
                $userLog['browser_id'] = $request['data']['browser_id'] ?? null;
                $userLog['client_id'] = $request['data']['client_id'] ?? null;
                $userLog['referer'] = $request['data']['referer'] ?? null;
                $userLog['expiry'] = $request['data']['expiry'] ?? null;
                $userLog['slug'] = $request['data']['slug'] ?? null;
                $userLog['utm_source'] = $request['data']['utm_source'] ?? null;
                $userLog['utm_medium'] = $request['data']['utm_medium'] ?? null;
                $userLog['utm_campaign'] = $request['data']['utm_campaign'] ?? null;
                $userLog['utm_term'] = $request['data']['utm_term'] ?? null;
                $userLog['utm_content'] = $request['data']['utm_content'] ?? null;

                $userLog['source'] = isset($request['data']['source']) ? $request['data']['source'] : $request->header('X-Api-Source');
                $userSessionData =  $userSessionRepo->userSessionSave($userLog);
                if (!$userSessionData) {
                    return $this->responseJson(
                        API_STATUS_FAILURE,
                        API_STATUS_FAILURE_MESSAGE,
                        API_STATUS_FAILURE_CODE,
                        []
                    );
                }
                return $this->responseJson(
                    API_STATUS_SUCCESS,
                    API_STATUS_SUCCESS_MESSAGE,
                    API_STATUS_SUCCESS_CODE,
                    []
                );
            }
            return $this->responseJson(
                API_STATUS_FAILURE,
                'Session id could not be empty',
                API_STATUS_FAILURE_CODE,
                []
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityService saveUserSessionActivity " . $throwable->__toString());
        }
    }
    /**
     * save user pan Activity
     *
     * @param  Request $request
     *
     */
    public function saveUserPanActivity($request)
    {
        try {
            $userSessionRepo = new UserSessionActivityRepository();
            $masterProductRepo = new MasterProductRepository();
            $applicationRepo = new ApplicationRepository();
            $applicationData = $applicationRepo->getAuthTokenByToken($request->bearerToken());
            $data = $request->input('data', []);
            $data['muid'] = $applicationData->mobile_number ?? $request['data']['muid'];
            $request->merge(['data' => $data]);
            if ((isset($request['data']['muid']) && $request['data']['muid']) != '' && isset($request['data']['mfid']) && $request['data']['mfid'] != '') {
                $panLog['session_id'] = $request['data']['session_id'] ??  null;
                $panLog['browser_id'] = $request['data']['browser_id'] ??  null;
                $panLog['mobile'] = $request['data']['muid'] ??  null;
                $panLog['product_id'] = isset($request['data']['product_id']) ? $masterProductRepo->getProductId($request['data']['product_id']) : null;
                $panLog['lead_id'] = $request['data']['mlid'] ?? null;
                $panLog['quote_id'] = $request['data']['quote_id'] ??  null;
                if ($applicationData && $request->header('X-Api-Source') ==  config('constants/apiSource.HOUSING_JOURNEY')) {
                    $panLog['lead_id']  = $applicationData->lead_id ? $applicationData->lead_id : null;
                    $panLog['mobile_number']  = $applicationData->mobile_number ? $applicationData->mobile_number : null;
                    $panLog['quote_id'] = $applicationData->quote_id ?  $applicationData->quote_id : null;
                    $panLog['product_id'] =  $applicationData->master_product_id ? $applicationData->master_product_id : null;
                }
                $panLog['pan'] = strtoupper($request['data']['mfid']) ??  null;
                $panLog['source'] = isset($request['data']['source']) ? $request['data']['source'] : $request->header('X-Api-Source');
                $userSessionData = $userSessionRepo->userPanSave($panLog);
                if (!$userSessionData) {
                    return $this->responseJson(
                        API_STATUS_FAILURE,
                        API_STATUS_FAILURE_MESSAGE,
                        API_STATUS_FAILURE_CODE,
                        []
                    );
                }
                return $this->responseJson(
                    API_STATUS_SUCCESS,
                    API_STATUS_SUCCESS_MESSAGE,
                    API_STATUS_SUCCESS_CODE,
                    []
                );
            }
            return $this->responseJson(
                API_STATUS_FAILURE,
                'Pan and mobile number could not be empty',
                API_STATUS_FAILURE_CODE,
                []
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityService saveUserPanActivity " . $throwable->__toString());
        }
    }

    /**
     * list user session log
     *
     * @param  Request $request
     *
     */
    public function userSessionList(Request $request)
    {
        try {
            $userSesRepo = new UserSessionActivityRepository();
            $userSessionData = $userSesRepo->getUserSessionLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $userSessionData
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityService list " . $throwable->__toString());
        }
    }
    /**
     * filter data
     *
     * @param  Request $request
     *
     */
    public function getFilterData()
    {
        try {
            $apiSource = $this->getFilterDatas('apiSource');
            $filterList['api_source'] =  $this->convertFilterData($apiSource, 'api_source');
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $filterList
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityService getFilterData " . $throwable->__toString());
        }
    }

    /**
     * export user Session log
     *
     * @param  Request $request
     *
     */
    public function userSessionExportLog(Request $request)
    {
        try {
            $userSesRepo =  new UserSessionActivityRepository();
            $datas['methodName'] = 'getUserSessionLog';
            $datas['fileName'] = 'User-Session-Log-Report-';
            $datas['moduleName'] = 'User-Session-Log';
            return $this->exportData($request, $userSesRepo, $datas);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("UserSessionActivityService userSessionExportLog " . $throwable->__toString());
        }
    }
    /**
     * list user session log
     *
     * @param  Request $request
     *
     */
    public function userPortfolioList(Request $request)
    {
        try {
            $userSesRepo = new UserSessionActivityRepository();
            $userPortfolioData = $userSesRepo->getUserPortfolioLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $userPortfolioData
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("UserSessionActivityService list " . $throwable->__toString());
        }
    }
    /**
     * export user port folio log
     *
     * @param  Request $request
     *
     */
    public function userPortFolioExportLog(Request $request)
    {
        try {
            $userSesRepo =  new UserSessionActivityRepository();
            $datas['methodName'] = 'getUserPortfolioLog';
            $datas['fileName'] = 'User-Portfolio-Log-Report-';
            $datas['moduleName'] = 'User-Portfolio-Log';
            return $this->exportData($request, $userSesRepo, $datas);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("UserSessionActivityService userPortFolioExportLog " . $throwable->__toString());
        }
    }
}
