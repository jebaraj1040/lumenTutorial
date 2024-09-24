<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterPincode;

class PincodeRepository
{
  /**
   * Insert class PincodeRepository
   *
   */
  public function view($id)
  {
    try {
      return HjMasterPincode::where('id', $id)->first();
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("PincodeRepository view" . $throwable->__toString());
    }
  }
}
