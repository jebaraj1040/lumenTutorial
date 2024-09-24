<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjPersonalDetail;
use App\Entities\HousingJourney\HjEmploymentDetail;
use App\Entities\MongoLog\PanLog;
use App\Entities\HousingJourney\HjMappingApplicantRelationship;
use App\Utils\CrmTrait;
use Carbon\Carbon;

class PersonalDetailRepository
{
  use CrmTrait;
  /**
   * Insert class PersonalDetailRepository
   *
   */
  public function save($request)
  {
    try {
      return HjPersonalDetail::updateOrCreate(['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id']], $request);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository save" . $throwable->__toString());
    }
  }
  public function getLeadDetailsById($leadId)
  {
    try {
      return HjPersonalDetail::where('id', $leadId)->select('email', 'dob')->first();
    } catch (Throwable $throwable) {
      Log::info("Repo-getLeadDetailsById" . $throwable->__toString());
    }
  }
  public function view($request)
  {
    try {
      return HjPersonalDetail::select(
        'dob',
        'email',
        'full_name',
        'gender',
        'is_property_identified',
        'pan'
      )->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id'])->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository view" . $throwable->__toString());
    }
  }

  public function getApplicantName($request)
  {
    try {
      return HjPersonalDetail::select('full_name')->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id'])->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository view" . $throwable->__toString());
    }
  }

  public function getEmplymentType($request)
  {
    try {
      return HjEmploymentDetail::select('employment_type_id')->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id'])->value('employment_type_id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository getEmplymentType" . $throwable->__toString());
    }
  }

  /**
   * Get Personal Data
   *
   */
  public function getPersonalData($request)
  {
    try {
      return HjPersonalDetail::where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])
        ->select('pan', 'dob', 'full_name', 'gender', 'email', 'unsubscribe', 'is_property_identified')
        ->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository getPersonalData" . $throwable->__toString());
    }
  }

  /**
   * fetch pan details
   *
   */
  public function getPanData($request)
  {
    try {
      $dateFrom = Carbon::now()->subDays(30);
      $dateTo = Carbon::now();
      return PanLog::select('api_data', 'created_at')->where(
        'mobile_number',
        $request['mobile_number']
      )->whereBetween(
        'created_at',
        [$dateFrom, $dateTo]
      )->where('api_status_code', 200)->orderBy('created_at', 'DESC')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository getPanData " . $throwable->__toString());
    }
  }

  /**
   * fetch pan number from personal Detail table
   *
   */
  public function getPanNumber($request)
  {
    try {
      return HjPersonalDetail::where(
        'lead_id',
        $request['lead_id']
      )->where('quote_id', $request['quote_id'])->value('pan');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository getPanNumber " . $throwable->__toString());
    }
  }
  /**
   * pan duplicate check
   *
   */
  public function panDuplicateCheck($request)
  {
    try {
      if (array_key_exists('lead_id', $request)) {
        return HjPersonalDetail::where(
          'quote_id',
          $request['quote_id']
        )->where('pan', $request['pan'])->where('lead_id', '!=', $request['lead_id'])->count();
      } else {
        return HjPersonalDetail::where(
          'quote_id',
          $request['quote_id']
        )->where('pan', $request['pan'])->count();
      }
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository panDuplicateCheck " . $throwable->__toString());
    }
  }
  /**
   * getRelationship data
   *
   */
  public function getRelationshipData($leadId)
  {
    try {
      return HjMappingApplicantRelationship::select('relationship_id')
        ->with('relationship:id,name,handle')->where('lead_id', $leadId)->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("PersonalDetailRepository getRelationshipData " . $throwable->__toString());
    }
  }
  public function list($request, $offset = null)
  {
    try {
      $query = HjPersonalDetail::query();
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
      $personalData = $query->select('*')
        ->with('leadDetail:id,mobile_number')
        ->orderBy('id', 'desc')
        ->get();
      if ($request->action == 'download') {
        foreach ($personalData as $key => $item) {
          // Check if leadDetail is loaded and not null
          if ($item->leadDetail) {
            $personalData[$key]['lead_id'] =  $item->leadDetail->mobile_number;
          } else {
            $personalData[$key]['lead_id'] = null;
          }
          unset($personalData[$key]['leadDetail']);
        }
      }
      $personalDetailData['totalLength'] =  $totalLength;
      $personalDetailData['dataList'] = $personalData;
      return $personalDetailData;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PersonalDetailReppository list " . $throwable->__toString());
    }
  }
}
