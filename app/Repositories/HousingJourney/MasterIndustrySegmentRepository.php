<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterIndustrySegment;

class MasterIndustrySegmentRepository
{
    /**
     * save industry segment
     *
     */
    public function save($data)
    {
        try {
            return HjMasterIndustrySegment::updateOrCreate(['name' => $data['name']], $data);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterIndustrySegmentRepository save " . $throwable->__toString());
        }
    }
}
