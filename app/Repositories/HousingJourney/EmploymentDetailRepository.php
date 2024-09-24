<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjEmploymentDetail;
use App\Entities\HousingJourney\HjMasterCompany;
use App\Entities\HousingJourney\HjMasterEmploymentSalaryMode;
use App\Entities\HousingJourney\HjMasterEmploymentType;
use App\Entities\HousingJourney\HjMasterEmploymentConstitutionType;
use App\Entities\HousingJourney\HjMasterIndustryType;
use App\Entities\HousingJourney\HjMasterProfessionalType;
use App\Entities\HousingJourney\HjMasterIndustrySegment;
use App\Utils\CrmTrait;

class EmploymentDetailRepository
{
  use CrmTrait;
  /**
   * Insert employmentDetail.
   *
   */

  public function employmentDetailsSave($request)
  {
    try {
      return HjEmploymentDetail::updateOrCreate(['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id']], $request);
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository save " . $throwable->__toString());
    }
  }

  /**
   * Get the get employment detail
   *
   * @param $reqest
   */
  public function view($reqest)
  {
    try {
      return HjEmploymentDetail::with(
        'employmentType:id,name,handle',
        'professionalType:id,name,handle',
        'industryType:id,name,handle',
        'industrySegment:id,name,handle',
        'constitutionTypeDetail:id,name,handle,display_name',
        'employmentSalaryModeDetail:id,name'
      )->where(
        'quote_id',
        $reqest['quote_id']
      )->where('lead_id', $reqest['lead_id'])->select(
        'employment_type_id',
        'company_id',
        'company_name',
        'constitution_type_id',
        'salary_mode_id',
        'net_monthly_salary',
        'monthly_emi',
        'total_experience',
        'current_experience',
        'other_income',
        'industry_segment_id',
        'industry_type_id',
        'net_monthly_sales',
        'net_monthly_profit',
        'gross_receipt',
        'business_vintage',
        'professional_type_id',
        'is_income_proof_document_available',
      )->first();
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository view" . $throwable->__toString());
    }
  }

  /**
   * Get the get employment detail
   *
   * @param $reqest
   */
  public function getEmploymentDetail($reqest)
  {
    try {
      return HjEmploymentDetail::with('employmentType', 'professionalType', 'employmentSalaryModeDetail', 'industryType', 'industrySegment', 'constitutionTypeDetail', 'professionalType')->where(
        'quote_id',
        $reqest['quote_id']
      )->get();
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository view" . $throwable->__toString());
    }
  }
  /**
   * Get company Data
   *
   * @param
   */
  public function getCompanyData($request)
  {
    try {
      return HjMasterCompany::select('id', 'name', 'handle')
        ->where('name', 'LIKE', '%' . $request->companyName . '%')
        ->where('is_active', '1')->orderBy('name', 'ASC')->groupBy('name')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getCompanyData" . $throwable->__toString());
    }
  }
  /**
   * Get Salary Mode
   *
   * @param
   */
  public function getSalaryMode()
  {
    try {
      return HjMasterEmploymentSalaryMode::select('id', 'name', 'handle')->where('is_active', '1')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getSalaryMode" . $throwable->__toString());
    }
  }

  /**
   * Get Salary Mode
   *
   * @param
   */
  public function getEmploymentType()
  {
    try {
      return HjMasterEmploymentType::select('id', 'name', 'handle')->where('is_active', '1')->orderBy('name', 'ASC')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getEmploymentType" . $throwable->__toString());
    }
  }
  /**
   * Get Constitution Type
   *
   * @param
   */
  public function getConstitutionType()
  {
    try {
      return HjMasterEmploymentConstitutionType::select('id', 'name', 'handle', 'display_name')->where('is_active', '1')->orderBy('order_id', 'ASC')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getConstitutionType " . $throwable->__toString());
    }
  }
  /**
   * Get Industry Type
   *
   * @param
   */
  public function getIndustryType($industrySegmentId)
  {
    try {
      return HjMasterIndustryType::where('industry_segment_id', $industrySegmentId)->where('is_active', '1')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getIndustryType " . $throwable->__toString());
    }
  }
  /**
   * Get Industry Segment
   *
   * @param
   */
  public function getIndustrySegment()
  {
    try {
      return HjMasterIndustrySegment::select('id', 'name', 'handle')->where('is_active', '1')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getIndustrySegment " . $throwable->__toString());
    }
  }
  /**
   * Get Professional Type
   *
   * @param
   */
  public function getProfessionalType()
  {
    try {
      return HjMasterProfessionalType::select('id', 'name', 'handle')->where('is_active', '1')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getProfessionalType " . $throwable->__toString());
    }
  }

  /**
   * Get employee details
   *
   * @param $reqest
   */
  public function getEmployeeDetails($reqest)
  {
    try {
      return  HjEmploymentDetail::select('employment_type_id', 'quote_id', 'id')->with(
        'employmentType:handle,id,name',
        'applicationData:is_paid,quote_id,master_product_id'
      )->where(
        'quote_id',
        $reqest['quote_id']
      )->where('lead_id', $reqest['lead_id'])->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getEmployeeDetails " . $throwable->__toString());
    }
  }
  public function list($request, $offset = null)
  {
    try {
      $query = HjEmploymentDetail::query();
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

      $employementData = $query->select('*')
        ->with('industryType:id,name')
        ->with('industrySegment:id,name')
        ->with('constitutionTypeDetail:id,name')
        ->with('professionalType:id,name')
        ->with('leadDetail:id,mobile_number')
        ->with('employmentType:id,name')
        ->with('employmentSalaryModeDetail:id,name')
        ->orderBy('id', 'desc')
        ->get();
      if ($request->action == 'download') {
        foreach ($employementData as $key => $item) {
          // Check if masterproductstep is loaded and not null
          if ($item->employmentType) {
            $employementData[$key]['employment_type_id'] =  $item->employmentType->name;
          } else {
            $employementData[$key]['employment_type_id'] = null;
          }
          // Check if employmentSalaryModeDetail is loaded and not null
          if ($item->employmentSalaryModeDetail) {
            $employementData[$key]['salary_mode_id'] =  $item->employmentSalaryModeDetail->name;
          } else {
            $employementData[$key]['salary_mode_id'] = null;
          }
          // Check if leadDetail is loaded and not null
          if ($item->leadDetail) {
            $employementData[$key]['lead_id'] =  $item->leadDetail->mobile_number;
          } else {
            $employementData[$key]['lead_id'] = null;
          }
          unset($employementData[$key]['employmentType']);
          unset($employementData[$key]['employmentSalaryModeDetail']);
          unset($employementData[$key]['leadDetail']);
          unset($employementData[$key]['industrySegment']);
          unset($employementData[$key]['constitutionTypeDetail']);
          unset($employementData[$key]['professionalType']);
          unset($employementData[$key]['industryType']);
        }
      }
      $employementDetailData['totalLength'] =  $totalLength;
      $employementDetailData['dataList'] = $employementData;
      return $employementDetailData;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository list " . $throwable->__toString());
    }
  }

  /**
   * get employment handle
   *
   * @param $reqest
   */
  public function getEmploymentTypeHandle($employmentId)
  {
    try {
      return HjMasterEmploymentType::where('id', $employmentId)->where('is_active', 1)->value('handle');
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("EmploymentDetailRepository getEmploymentTypeHandle " . $throwable->__toString());
    }
  }
}
