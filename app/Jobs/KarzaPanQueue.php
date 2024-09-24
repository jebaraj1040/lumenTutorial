<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\AddressRepository;
use App\Utils\CoreTrait;
use Carbon\Carbon;

class KarzaPanQueue extends Job
{
    use Queueable, InteractsWithQueue, SerializesModels, CoreTrait;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $request;
    public function __construct(array $request)
    {
        $this->request = $request;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $request = $this->request;
        $this->karzaApiCall($request);
    }
    /**
     * Karza Pan Core API call.
     *
     * @param array $request
     *
     */
    public function karzaApiCall(array $request)
    {
        try {
            $applicationRepo = new ApplicationRepository();
            $masterProductId = $applicationRepo->getMasterProductId($request['quote_id']);
            $reqData['pan'] = (string)strtoupper($request['pan']);
            $reqData['lead_id'] = $request['lead_id'];
            $reqData['quote_id'] = $request['quote_id'];
            if (array_key_exists('pan', $reqData)) {
                $addressRepo = new AddressRepository();
                $viewKarzaHistroy = $addressRepo->viewKarzaHistroy($reqData);
                if ($viewKarzaHistroy && count($viewKarzaHistroy) > 0) {
                    $currentDate = Carbon::now()->format('Y-m-d');
                    $carbonDate = Carbon::createFromTimestampMs($viewKarzaHistroy[0]->created_at);
                    $createdDate = $carbonDate->format('Y-m-d');
                    $to = Carbon::parse($currentDate);
                    $from = Carbon::parse($createdDate);
                    $days = $to->diffInDays($from);
                    if ($days < 30) {
                        return $viewKarzaHistroy;
                    } else {
                        $panReq['PanNo'] = strtoupper($request['pan']);
                        $address = $this->fetchAddressFromKarza($panReq);
                        $decodedAddress  =  json_decode($address, true);
                        $karzaLog['lead_id'] = $request['lead_id'];
                        $karzaLog['quote_id'] = $request['quote_id'];
                        $karzaLog['pan'] = $decodedAddress['panNo'];
                        $karzaLog['master_product_id'] = $masterProductId;
                        $karzaLog['api_source'] = config('constants/apiSource.CORE');
                        $karzaLog['api_source_page'] = config('constants/apiSourcePage.ADDRESS_PAGE');
                        $karzaLog['api_type'] = config('constants/apiType.KARZA_PAN_DATA');
                        $karzaLog['api_header'] = $request['header'] ?? null;
                        $karzaLog['api_url'] = env('CORE_API_URL') . 'karzaPan';
                        $karzaLog['api_request_type'] = config('constants/apiType.RESPONSE');
                        $karzaLog['api_data'] = $decodedAddress;
                        $karzaLog['api_status_code'] = config('journey/http-status.success.code');
                        $karzaLog['api_status_message'] = config('journey/http-status.success.message');
                        $addressRepo = new AddressRepository();
                        $addressRepo->saveKarzaHistroy($karzaLog);
                        return $decodedAddress;
                    }
                } else {
                    $panReq['PanNo'] = strtoupper($request['pan']);
                    $address = $this->fetchAddressFromKarza($panReq);
                    $decodedAddress  =  json_decode($address, true);
                    if (array_key_exists('panNo', ($decodedAddress))) {
                        $karzaLog['lead_id'] = $request['lead_id'];
                        $karzaLog['quote_id'] = $request['quote_id'];
                        $karzaLog['pan'] = $decodedAddress['panNo'];
                        $karzaLog['master_product_id'] = $masterProductId;
                        $karzaLog['api_source'] = config('constants/apiSource.CORE');
                        $karzaLog['api_source_page'] = config('constants/apiSourcePage.ADDRESS_PAGE');
                        $karzaLog['api_type'] = config('constants/apiType.KARZA_PAN_DATA');
                        $karzaLog['api_header'] = $request['header'] ?? null;
                        $karzaLog['api_url'] = env('CORE_API_URL') . 'karzaPan';
                        $karzaLog['api_request_type'] = config('constants/apiType.RESPONSE');
                        $karzaLog['api_data'] = $decodedAddress;
                        $karzaLog['api_status_code'] = config('journey/http-status.success.code');
                        $karzaLog['api_status_message'] = config('journey/http-status.success.message');
                        $addressRepo = new AddressRepository();
                        $addressRepo->saveKarzaHistroy($karzaLog);
                        return true;
                    } else {
                        $karzaLog['lead_id'] = $request['lead_id'];
                        $karzaLog['quote_id'] = $request['quote_id'];
                        $karzaLog['pan'] = $reqData['pan'];
                        $karzaLog['master_product_id'] = $masterProductId;
                        $karzaLog['api_source'] = config('constants/apiSource.CORE');
                        $karzaLog['api_source_page'] = config('constants/apiSourcePage.ADDRESS_PAGE');
                        $karzaLog['api_type'] = config('constants/apiType.KARZA_PAN_DATA');
                        $karzaLog['api_header'] = $request['header'] ?? null;
                        $karzaLog['api_url'] = env('CORE_API_URL') . 'karzaPan';
                        $karzaLog['api_request_type'] = config('constants/apiType.RESPONSE');
                        $karzaLog['api_data'] = $decodedAddress;
                        $karzaLog['api_status_code'] = 402;
                        $karzaLog['api_status_message'] = config('journey/http-status.success.message');
                        $addressRepo = new AddressRepository();
                        $addressRepo->saveKarzaHistroy($karzaLog);
                    }
                }
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("EligibilityRepository karzaApiCall " . $throwable->__toString());
        }
    }
}
