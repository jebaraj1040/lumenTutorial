<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterEmploymentType;

class MasterEmploymentTypeRepository
{
    /**
     * save branaches
     *
     */
    public function save($data)
    {
        try {
            return HjMasterEmploymentType::updateOrCreate(['name' => $data['name']], $data);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterEmploymentTypeRepository save " . $throwable->__toString());
        }
    }
}
