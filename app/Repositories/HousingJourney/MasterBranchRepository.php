<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterBranch;

class MasterBranchRepository
{
    /**
     * save branaches
     *
     */
    public function save($data)
    {
        try {
            return HjMasterBranch::updateOrCreate(['code' => $data['code'], 'name' => $data['name']], $data);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterBranchRepository save " . $throwable->__toString());
        }
    }
}
