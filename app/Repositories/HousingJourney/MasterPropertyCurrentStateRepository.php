<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterPropertyCurrentState;
use App\Entities\HousingJourney\HjMasterPropertyPurpose;
use App\Entities\HousingJourney\HjMasterProject;
use App\Entities\HousingJourney\HjMasterIfsc;

class MasterPropertyCurrentStateRepository
{
    /**
     * save property current state
     *
     */
    public function save($data)
    {
        try {
            return HjMasterPropertyCurrentState::updateOrCreate(['name' => $data['name']], $data);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPropertyCurrentStateRepository save " . $throwable->__toString());
        }
    }

    /**
     * get current state of property data
     *
     */
    public function getCurrentStateData()
    {
        try {
            return HjMasterPropertyCurrentState::select('display_name', 'handle', 'id', 'name')->where('is_active', '1')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPropertyCurrentStateRepository getCurrentStateData" . $throwable->getMessage());
        }
    }

    /**
     * get property purpose.
     *
     */
    public function getPurposeData()
    {
        try {
            return HjMasterPropertyPurpose::select('handle', 'id', 'name')->where('is_active', '1')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPropertyCurrentStateRepository getPurposeData" . $throwable->getMessage());
        }
    }

    /**
     * get project name list
     *
     */
    public function getProjectList()
    {
        try {
            return HjMasterProject::select('builder', 'builder_handle', 'code', 'id', 'name', 'name_handle', 'pincode_id')->where('is_active', '1')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPropertyCurrentStateRepository getProjectList" . $throwable->getMessage());
        }
    }

    /**
     * get exiting loan provider list
     *
     */
    public function getLoanProvider($loanProvider)
    {
        try {
            return HjMasterIfsc::select('id', 'bank_name')->where('bank_name', 'LIKE', '%' . $loanProvider . '%')->groupBy('bank_name')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterPropertyCurrentStateRepository getLoanProvider" . $throwable->getMessage());
        }
    }
}
