<?php

namespace App\Services\HousingJourney;

use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Repositories\HousingJourney\FieldTrackingRepository;
use Illuminate\Support\Facades\Crypt;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Entities\HousingJourney\HjMasterCcSubStage;
use App\Entities\HousingJourney\HjMasterCcStage;
use App\Entities\MongoLog\FieldTrackingLog;
use App\Utils\CrmTrait;
use GuzzleHttp\Exception\ClientException;

class FieldTrackingService extends Service
{
    use CrmTrait;
    /**
     * Insert Fieldwise update
     *
     */
    public function log(Request $request, FieldTrackingRepository $fieldTrackingRepo, ApplicationRepository $apRepo)
    {
        try {
            $mobileNumber = null;
            $ccAuthToken = $request->header('X-Session-token');
            $ccQuoteId = null;
            if (isset($request['quote_id']) && $request['quote_id'] !== '') {
                $application = $apRepo->getApplicationData($request['quote_id']);
                $mobileNumber = $application->mobile_number;
                $ccAuthToken = $application->auth_token ?? null;
                $ccQuoteId = $application->cc_quote_id ?? null;
            } else {
                if (isset($request['mobile_number']) && $request['mobile_number'] !== '') {
                    $mobileNumber = $request['mobile_number'];
                }
            }
            $reqData['mobile_number'] = $mobileNumber;
            $reqData['cc_auth_token'] = $ccAuthToken ?? null;
            $reqData['lead_id'] = $request['lead_id'] ?? null;
            $reqData['quote_id'] = $request['quote_id'] ?? null;
            $reqData['cc_quote_id'] = $ccQuoteId ?? null;
            $getFieldTrack  = $fieldTrackingRepo->viewCount($reqData);
            // check mobile number to create cc token
            if ($getFieldTrack == 0) {
                $fieldTrack['cc_token'] = null;
                if (isset($request['mobile_number']) && $request['mobile_number'] !== '' && isset($request['master_product_id']) && $request['master_product_id'] !== '') {
                    $fieldTrack['cc_token'] = $this->createCcToken(
                        $request['mobile_number'],
                        $request['master_product_id']
                    );
                }
                foreach ($request->all() as $key => $value) {
                    if ($key == 'pincode') {
                        $fieldTrack['pincode_id'] = $fieldTrackingRepo->getPincodeId((int)$value);
                    }
                    $fieldTrack[$key] = $value;
                }
            } else {
                $lastRecord =  $fieldTrackingRepo->view($reqData);
                if ($lastRecord) {
                    foreach ($lastRecord[0] as $ky => $val) {
                        $fieldTrack[$ky] = $val;
                    }
                }
                unset($fieldTrack['_id']);
                unset($fieldTrack['created_at']);
                foreach ($request->all() as $key => $value) {
                    if ($key == 'pincode') {
                        $fieldTrack['pincode_id'] = $fieldTrackingRepo->getPincodeId((int)$value);
                    }
                    $fieldTrack[$key] = $value;
                }
            }
            // get cc push details.
            $getCCPushDatas = $this->getCCPushDatas($request['cc_stage_handle'], $request['cc_sub_stage_handle']);
            $fieldTrack['cc_push_status'] = 0;
            $fieldTrack['cc_push_tag'] = 0;
            $fieldTrack['cc_push_stage_id'] = $getCCPushDatas['cc_push_stage_id'] ?? null;
            $fieldTrack['cc_push_sub_stage_id'] = $getCCPushDatas['cc_push_sub_stage_id'] ?? null;
            $fieldTrack['cc_push_sub_stage_priority'] = $getCCPushDatas['cc_push_sub_stage_priority'] ?? null;
            $fieldTrack['cc_push_block_for_calling'] = $getCCPushDatas['cc_push_block_for_calling'] ?? null;
            $fieldTrack['lead_id'] = $request['lead_id'] ?? null;
            $fieldTrack['quote_id'] = $request['quote_id'] ?? null;
            $fieldTrack['api_source'] = $request->header('X-Api-Source');
            $fieldTrack['api_source_page'] = $request->header('X-Api-Source-Page');
            $fieldTrack['cc_auth_token'] = $ccAuthToken ?? null;
            $fieldTrack['cc_quote_id'] = $ccQuoteId ?? null;
            $fieldTrack['api_type'] = $request->header('X-Api-Type');
            $fieldTrack['api_header'] = $request->header;
            $fieldTrack['api_url'] = $request->url();
            $fieldTrack['api_request_type'] = config('constants/apiType.REQUEST');
            $fieldTrack['api_data'] = $request->all();
            $fieldTrack['api_status_code'] = config('journey/http-status.success.code');
            $fieldTrack['api_status_message'] = config('journey/http-status.success.message');
            $fieldTrack['mobile_number'] = $reqData['mobile_number'];
            $fieldTrack['master_product_id'] = $request['master_product_id'];
            $fieldTrackingRepo->save($fieldTrack);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                []
            );
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("FieldTrackingService log " . $throwable->__toString());
        }
    }
    /**
     * create cc push token.
     *
     * @param $mobileNumber, $masterProductId
     */
    private function createCcToken($mobileNumber, $masterProductId)
    {
        return Crypt::encrypt($mobileNumber . $masterProductId);
    }
    private function getCCPushDatas($stageHandle, $subStageHandle)
    {
        $returnData = [];
        $stage = HjMasterCcStage::select('id', 'stage_id')->where('handle', $stageHandle)->first();
        $subStage = HjMasterCcSubStage::select(
            'id',
            'priority',
            'block_for_calling'
        )->where('handle', $subStageHandle)->first();
        $returnData['cc_push_stage_id'] = $stage['stage_id'];
        $returnData['cc_push_sub_stage_id'] = $subStage['id'];
        $returnData['cc_push_sub_stage_priority'] = $subStage['priority'];
        $returnData['cc_push_block_for_calling'] = $subStage['block_for_calling'];
        return $returnData;
    }
    public function list(Request $request, FieldTrackingRepository $fieldTrackingRepo): mixed
    {
        try {
            $logsList = $fieldTrackingRepo->list($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $logsList
            );
        } catch (Throwable   | HttpClientException $throwable) {
            throw new Throwable(
                Log::info("Service : FieldTrackingLogList , Method : list : %s", $throwable->__toString())
            );
        }
    }
    public function getFilterData()
    {
        try {
            $apiSource = $this->getFilterDatas('apiSource');
            $apiSourcePage = $this->getFilterDatas('apiSourcePage');
            $apiType = $this->getFilterDatas('apiType');
            $filterList['api_source'] =  $this->convertFilterData($apiSource, 'api_source');
            $filterList['api_source_page'] =  $this->convertFilterData($apiSourcePage, 'api_source_page');
            $filterList['api_type'] =  $this->convertFilterData($apiType, 'api_type');
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $filterList
            );
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : LogService , Method : getFilterData : %s", $throwable->__toString())
            );
        }
    }
    public function export(Request $request)
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
            $repository = new FieldTrackingRepository();
            $data['methodName'] = 'list';
            $data['fileName'] = 'Field-Tracking-Report-';
            $data['moduleName'] = 'Field-Tracking';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("LogExport " . $throwable->__toString());
            return $this->responseJson(
                config('crm/http-status.error.status'),
                config('crm/http-status.error.message'),
                config('crm/http-status.error.code'),
                []
            );
        }
    }
}
