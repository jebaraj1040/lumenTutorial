<?php

namespace App\Services\HousingJourney;

use Exception;
use Throwable;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\AuctionBidFormDetailRepository;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;

class AuctionBidFormDetailService extends Service
{
    use CrmTrait;
    public function save(
        Request $request,
        AuctionBidFormDetailRepository $auctionbidfomRepo,
        MasterApiLogRepository $masterApiLogRepo
    ) {
        try {
            $rules = [
                'project_number' => 'required',
                'project_name' => 'required',
                'file_number' => 'required',
                'pan_number' => 'required',
                'name' => ['required', 'regex:/^[a-zA-Z]+(?:\s+[a-zA-Z]+)*$/'],
                'mobile_number' => 'required',
                'account_number' => 'required',
                'ifsc_code' => 'required',
                'bank_name' => 'required',
                'branch_name' => 'required',
                'property_item_number' => 'required',
                'is_emd_remitted' => 'required',
                'is_same_bank_details' => 'required',
                'emd_account_number' => 'required',
                'emd_ifsc_code' => 'required',
                'emd_branch_name' => 'required',
                'emd_bank_name' => 'required',
                'consent' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $validator->errors();
            }
            $requestUrl = $request->url . $request->path();
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
            $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.BID_FORM_SUBMIT');
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
            $requestData['customHeader']['X-Api-Url'] = $requestUrl;
            $requestData['request'] = $request;
            $masterApiLogData = $masterApiLogRepo->save($requestData);
            $request['master_log_id'] = $masterApiLogData['id'];
            $save = $auctionbidfomRepo->save($request->all(), $request);
            if ($save) {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.SUCCESS');
                $response = $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.auction-data-add.message'),
                    config('journey/http-status.success.code'),
                    [$save]
                );
                $masterApiLogRepo->update(
                    $masterApiLogData['id'],
                    json_encode($response),
                    $requestData['customHeader']['X-Api-Status']
                );
                return $response;
            } else {
                $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                $response = $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
                $masterApiLogRepo->update(
                    $masterApiLogData['id'],
                    json_encode($response),
                    $requestData['customHeader']['X-Api-Status']
                );
                return $response;
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            $requestData['request'] = $request;
            $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
            $response = $this->responseJson(
                config('journey/http-status.failure.status'),
                config('journey/http-status.failure.message'),
                config('journey/http-status.failure.code'),
                []
            );
            $masterApiLogRepo->save($requestData);
            Log::info("AuctionBidFormService " . $throwable->__toString());
        }
    }
    public function list(Request $request, AuctionBidFormDetailRepository $auctionbidfomRepo)
    {
        try {
            $auctionbidList = $auctionbidfomRepo->list($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $auctionbidList
            );
        } catch (Throwable | Exception | ClientException $throwable) {
            Log::info("AuctionbidService -  list " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }
    /**
     * Export Auctionbids.
     *
     * @param
     * @return void
     */
    public function export(Request $request)
    {
        try {
            $repository = new AuctionBidFormDetailRepository();
            $data['methodName'] = 'list';
            $data['fileName'] = 'Auction-Bid-Report-';
            $data['moduleName'] = 'Auction-Bid';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable | ClientException $throwable) {
            throw new (sprintf("AuctionbidService list : %s", $throwable->__toString()));
        }
    }
}
