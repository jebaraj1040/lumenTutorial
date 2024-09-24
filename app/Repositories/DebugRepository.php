<?php

namespace App\Repositories;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\HousingJourney\HjMasterPincode;
use App\Entities\HousingJourney\HjMasterIndustryType;
use App\Entities\HousingJourney\HjMasterCompany;
use App\Entities\HousingJourney\HjMasterProject;
use App\Entities\HousingJourney\HjMasterIfsc;
use App\Entities\HousingJourney\HjMasterEmploymentType;
use App\Entities\HousingJourney\HjApplication;
use App\Entities\MongoLog\KarzaLog;

class DebugRepository
{
    /**
     * get pincode data.
     *
     * @param $request
     *
     */
    public function pincodeSearch($pinCode)
    {
        try {
            return HjMasterPincode::where('code', $pinCode)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository pincodeSearch : " . $throwable->__toString());
        }
    }
    /**
     * get industry data.
     *
     * @param $request
     *
     */
    public function industrySearch($industry)
    {
        try {
            return HjMasterIndustryType::where('name', $industry)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository industrySearch : " . $throwable->__toString());
        }
    }
    /**
     * get company data.
     *
     * @param $request
     *
     */
    public function companySearch($company)
    {
        try {
            return HjMasterCompany::where('name', $company)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository companySearch : " . $throwable->__toString());
        }
    }
    /**
     * get project data.
     *
     * @param $request
     *
     */
    public function projectSearch($project)
    {
        try {
            return HjMasterProject::where('name', 'LIKE', '%' . $project . '%')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository projectSearch : " . $throwable->__toString());
        }
    }
    /**
     * get ifsc data.
     *
     * @param $request
     *
     */
    public function ifscSearch($ifscCode)
    {
        try {
            return HjMasterIfsc::where('ifsc', $ifscCode)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository ifscSearch : " . $throwable->__toString());
        }
    }
    /**
     * get state data.
     *
     * @param $request
     *
     */
    public function stateSearch($state)
    {
        try {
            return HjMasterPincode::where('state', $state)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository stateSearch : " . $throwable->__toString());
        }
    }
    /**
     * get employment type.
     *
     * @param $request
     *
     */
    public function employmentType($employmentType)
    {
        try {
            return HjMasterEmploymentType::where('name', $employmentType)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository employmentType : " . $throwable->__toString());
        }
    }
    /**
     * check karza log
     *
     * @param $request
     *
     */
    public function viewKarzaHistroy($panNumber)
    {
        try {
            return KarzaLog::where('pan', $panNumber)->where('api_status_code', 200)
                ->orderBy('created_at', 'DESC')->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository viewKarzaHistroy : " . $throwable->__toString());
        }
    }
    /**
     * update application updated date
     *
     * @param $request
     *
     */
    public function updateApplicationDate($reqData)
    {
        try {
            return HjApplication::where('quote_id', $reqData['quote_id'])
                ->where('mobile_number', $reqData['mobile_number'])
                ->update(['updated_at' => $reqData['date'], 'bre_version_date' => $reqData['bre_version_date']]);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugRepository updateApplicationDate : " . $throwable->__toString());
        }
    }
}
