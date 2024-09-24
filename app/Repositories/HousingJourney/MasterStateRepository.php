<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterState;

class MasterStateRepository
{

    /**
     * Get stateList
     *
     */
    public function list()
    {
        try {
            return HjMasterState::where('is_active', "1")->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterStateRepository stateList" . $throwable->__toString());
        }
    }
}
