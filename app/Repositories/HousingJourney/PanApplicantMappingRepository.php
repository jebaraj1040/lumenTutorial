<?php

namespace App\Repositories\HousingJourney;

use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMappingPanApplicant;


class PanApplicantMappingRepository
{

  public function save($request)
  {
    try {
      return HjMappingPanApplicant::updateOrCreate(['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id'], 'personal_detail_id' => $request['personal_detail_id']], $request);
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("Pan Applicant Mapping Repo save" . $throwable->__toString());
    }
  }
}
