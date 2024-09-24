<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\MongoLog\CibilLog;
use App\Entities\MongoLog\PanLog;
use App\Entities\Service\Bvncalls;
use App\Entities\Service\Googlechat;
use App\Entities\Service\Microsite;
use App\Entities\MongoLog\CCPushLog;
use App\Entities\HousingJourney\WebSubmission;




class CoreRepository
{
  public function savePanHistory($request)
  {
    try {
      return PanLog::create($request);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("CoreRepository savePanHistory " . $throwable->__toString());
    }
  }
  public function viewCibilHistroy($panNumber)
  {
    try {
      return CibilLog::where('pan', $panNumber)->where('api_request_type', 'RESPONSE')->latest('created_at')->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("CoreRepository viewPanHistroy : " . $throwable->__toString());
    }
  }

  public function saveBvncalls($requestData)
  {
    try {
      return Bvncalls::create($requestData);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("Bvncalls " . $throwable->__toString());
    }
  }
  public function saveGoogleChat($requestData)
  {
    try {
      return Googlechat::create($requestData);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("Googlechat " . $throwable->__toString());
    }
  }
  public function saveMicrosite($requestData)
  {
    try {
      return Microsite::create($requestData);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("Microsite " . $throwable->__toString());
    }
  }
  /**
   * get quoteid count
   *
   * @param $quoteId
   */
  public function getQuoteCount($quoteId)
  {
    try {
      return CCPushLog::where('quote_id', $quoteId)->count();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("CoreRepository  getQuoteCount " . $throwable->__toString());
    }
  }

  public function update($request)
  {
    try {
      $lastRecord = WebSubmission::where('mobile_number', $request->mobile_number)->where('source_page', config('constants/apiSourcePage.CREDIT_SCORE_PAGE'))->where('is_verified', 1)->orderBy('created_at', 'DESC')->first();
      if ($lastRecord) {
        $requestData['name'] = $request['full_name'];
        $requestData['email'] = $request['email'];
        $requestData['pincode_id'] = $request['pincode_id'];
        WebSubmission::where('id', $lastRecord->id)->update($requestData);
      }
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("WebSubmissionRepository update " . $throwable->__toString());
    }
  }
}
