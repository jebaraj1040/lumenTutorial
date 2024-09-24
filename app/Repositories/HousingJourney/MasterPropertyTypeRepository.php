<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterPropertyType;

class MasterPropertyTypeRepository
{
    /**
     * save property type
     *
     */
    public function save($data)
    {
        try {
            return HjMasterPropertyType::updateOrCreate(['name' => $data['name']], $data);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPropertyTypeRepository save " . $throwable->__toString());
        }
    }
}
