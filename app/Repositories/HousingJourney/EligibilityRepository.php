<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjEligibility;
use App\Entities\HousingJourney\HjMappingCoApplicant;
use App\Utils\CrmTrait;

class EligibilityRepository
{
  use CrmTrait;
  /**
   * Insert EligibilityRepository.
   *
   */
  public function save($request)
  {
    try {
      return HjEligibility::updateOrCreate(['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id'], 'type' => $request['type']], $request);
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("EligibilityRepository save " . $throwable->__toString());
    }
  }

  /**
   * get eligibilityDetail
   *
   */
  public function view($request)
  {
    try {
      $query = HjEligibility::query();
      $query->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id'])->first();
      $getEligibilityData = $query->select('type', 'tenure', 'loan_amount', 'is_deviation', 'is_co_applicant')->first();
      $coApplicant = HjMappingCoApplicant::query();
      $coApplicant->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id']);
      $coApplicant->with('coApplicantDetail');
      $getCoApplicant = $coApplicant->get();
      $getAll['eligibility_data'] = $getEligibilityData;
      $getAll['co_applicant_data'] = $getCoApplicant;
      return $getAll;
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("EligibilityRepository view" . $throwable->__toString());
    }
  }
  /**
   * get co applicant status
   *
   */
  public function getCoApplicantStatus($quoteId)
  {
    try {
      return HjEligibility::where('quote_id', $quoteId)->value('is_co_applicant');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EligibilityRepository getCoApplicantStatus" . $throwable->__toString());
    }
  }

  /**
   * get co eligibility data by quote_id
   *
   */
  public function getEligibilityData($quoteId)
  {
    try {
      return HjEligibility::where('quote_id', $quoteId)->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EligibilityRepository getEligibilityData" . $throwable->__toString());
    }
  }

  /**
   * get co eligibility data by quote_id
   *
   */
  public function getLoanAmountTenure($quoteId)
  {
    try {
      return HjEligibility::select('loan_amount', 'tenure')->where('quote_id', $quoteId)->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EligibilityRepository getLoanAmountTenure" . $throwable->__toString());
    }
  }

  /**
   * list eligibility from Table
   * @param $request
   */
  public function list($request, $offset = null)
  {
    try {
      $query = HjEligibility::query();
      $query = $this->applyFilter($query, $request);
      if (isset($request->search) && $request->search != '' && $request->search != 'null') {
        $keyword = $request->search;
        $query->where('quote_id', $keyword);
        $query->orWhere('lead_id', $keyword);
        $query->orWhereHas('leadDetail', function ($subquery) use ($keyword) {
          $subquery->where('mobile_number', $keyword);
        });
      }
      $totalLength = $query->count();
      if ($request->action != 'download') {
        $skip = intval($request->skip);
        $limit = intval($request->limit);
        $query->skip($skip)->limit($limit);
      }
      if (empty($offset === false) && $offset != 'null' && $offset != '') {
        $limit = (int)env('EXPORT_EXCEL_LIMIT');
        $query->offset($offset)->limit($limit);
      }
      $eligibilityData = $query->select('*')
        ->with('leadDetail:id,mobile_number')
        ->orderBy('id', 'desc')
        ->get();

      if ($request->action == 'download') {
        foreach ($eligibilityData as $key => $item) {
          // Check if lead is loaded and not null
          if ($item->leadDetail) {
            $eligibilityData[$key]['lead_id'] =  $item->leadDetail->mobile_number;
          } else {
            $eligibilityData[$key]['lead_id'] = null;
          }
          unset($eligibilityData[$key]['leadDetail']);
        }
      }
      $eligibilityDetailData['totalLength'] =  $totalLength;
      $eligibilityDetailData['dataList'] = $eligibilityData;
      return $eligibilityDetailData;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("eligibilityRepository list " . $throwable->__toString());
    }
  }
  /**
   * remove exist eligibility
   * @param $request
   */
  public function removeExistData($reqData)
  {
    try {
      $query =  HjEligibility::where('lead_id', $reqData['lead_id'])->where('quote_id', $reqData['quote_id']);
      if ($reqData['type'] == 'BRE1') {
        $query->where('type', 'BRE1');
      } else {
        $query->where('type', 'BRE2');
      }
      $query->delete();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("eligibilityRepository removeExistData " . $throwable->__toString());
    }
  }
  /**
   * get BRE2 exist eligibility
   * @param $request
   */
  public function getBre2Eligibile($reqData)
  {
    try {
      return HjEligibility::where('quote_id', $reqData['quote_id'])
        ->where('lead_id', $reqData['lead_id'])->where('type', 'BRE2')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("eligibilityRepository getBre2Eligibile " . $throwable->__toString());
    }
  }
  /**
   * get BRE1 exist eligibility
   * @param $request
   */
  public function getBre1Eligibile($reqData)
  {
    try {
      return HjEligibility::select(
        'type',
        'loan_amount',
        'tenure',
        'is_deviation',
        'is_co_applicant'
      )->where('quote_id', $reqData['quote_id'])
        ->where('lead_id', $reqData['lead_id'])->where('type', 'BRE1')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("eligibilityRepository getBre1Eligibile " . $throwable->__toString());
    }
  }

  /**
   * get Tenure exist eligibility
   * @param $request
   */
  public function getTenure($reqData, $offerAmount)
  {
    try {
      return HjEligibility::where('quote_id', $reqData['quote_id'])
        ->where('lead_id', $reqData['lead_id'])
        ->where('loan_amount', $offerAmount)->value('tenure');
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("eligibilityRepository getBre1Eligibile " . $throwable->__toString());
    }
  }
}
