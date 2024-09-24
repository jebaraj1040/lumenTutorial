<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterIfsc;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;

class MasterIfscRepository
{
    public function save($data)
    {
        try {
            if ($data['ifsc_id'] == null) {
                return HjMasterIfsc::Create([
                    'bank_code' => $data['bank_code'], 'bank_name' => $data['bank_name'], 'location' => $data['location'],
                    'state' => $data['state'], 'ifsc' => $data['ifsc'],
                ]);
            } else {
                return HjMasterIfsc::where('id', $data['ifsc_id'])
                    ->update([
                        'bank_code' => $data['bank_code'], 'bank_name' => $data['bank_name'], 'location' => $data['location'],
                        'state' => $data['state'], 'ifsc' => $data['ifsc'],
                    ]);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterIfscRepository save " . $throwable->__toString());
        }
    }
    public function list($request)
    {
        try {
            return HjMasterIfsc::where('ifsc', $request['ifsc'])->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterIfscRepository list " . $throwable->__toString());
        }
    }
}
