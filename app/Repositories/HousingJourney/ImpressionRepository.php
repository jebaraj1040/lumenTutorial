<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\HousingJourney\HjImpression;
use App\Entities\HousingJourney\HjLead;
use App\Entities\HousingJourney\HjMasterProductStep;
use App\Entities\HousingJourney\HjMappingProductType;
use App\Entities\HousingJourney\HjPersonalDetail;
use App\Entities\HousingJourney\HjMasterProduct;
use App\Utils\CrmTrait;

class ImpressionRepository
{
  use CrmTrait;
  /**
   * Insert ImpressionRepository.
   *
   */
  public function save($request)
  {
    try {
      return HjImpression::create($request);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("ImpressionRepository save " . $throwable->__toString());
    }
  }
  /**
   * get previous impression id.
   *
   */
  public function getPreviousImpressionId($impressionId, $request)
  {
    try {
      return HjImpression::where('quote_id', $request['quote_id'])
        ->where('lead_id', $request['lead_id'])
        ->where('id', '<', $impressionId)->orderBy('id', 'desc')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("ImpressionRepository getPreviousImpressionId " . $throwable->__toString());
    }
  }
  /**
   * get current Impression id
   *
   */
  public function getCurrentImpressionId($request)
  {
    // get lead from quote id.
    $leadId = HjImpression::where('quote_id', $request->quote_id)->value('lead_id');
    try {
      return HjImpression::with(['stepName', 'lead'])->where(
        'lead_id',
        $leadId
      )->where('quote_id', $request->quote_id)->orderBy('id', 'DESC')->first();
    } catch (Throwable |  HttpClientException $throwable) {
      Log::info("ImpressionRepository getCurrentImpressionId " . $throwable->__toString());
    }
  }

  /**
   * fetch current Impression id
   *
   */
  public function fetchCurrentImpressionId($request)
  {
    // get lead from quote id.
    $leadId = HjImpression::where('quote_id', $request->quote_id)->value('lead_id');
    try {
      return HjImpression::with(['stepName'])->where(
        'lead_id',
        $leadId
      )->where('quote_id', $request->quote_id)->orderBy('id', 'DESC')->first();
    } catch (Throwable |  HttpClientException $throwable) {
      Log::info("ImpressionRepository fetchCurrentImpressionId " . $throwable->__toString());
    }
  }

  /**
   * fetch current Impression id
   *
   */
  public function fetchInitalImpProductId($quoteId)
  {
    try {
      return  HjImpression::where('quote_id', $quoteId)->orderBy('id', 'ASC')->value('master_product_id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository fetchInitalImpProductId " . $throwable->__toString());
    }
  }

  /**
   * get lead pincode Data
   *
   */
  public function getLeadData($leadId)
  {
    try {
      return  $leadId = HjLead::select('pincode_id', 'id')->with('pincodeData:id,code')->where('id', $leadId)->first();
    } catch (Throwable |  HttpClientException $throwable) {
      Log::info("ImpressionRepository getLeadData " . $throwable->__toString());
    }
  }
  /*
   * Fetch the Impression data
   */
  public function list($request, $offset = null)
  {
    try {
      $query = HjImpression::query();
      $query = $this->applyFilter($query, $request);
      if ($request->search != '' && $request->search != 'null') {
        $keyword = $request->search;
        $query->where(function ($query) use ($keyword) {
          $query->orWhere('lead_id', $keyword);
          $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
          $query->orWhereHas('lead', function ($subquery) use ($keyword) {
            $subquery->where('name', 'LIKE', '%' . $keyword . '%');
            $subquery->orWhere('mobile_number', $keyword);
          });
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
      $impressionList = $query->with('lead')
        ->with('productName:id,name,code')->with('application:quote_id,name')
        ->with('stepName:id,name')
        ->orderBy('id', 'desc')
        ->get();
      if ($request->action == 'download') {
        foreach ($impressionList as $key => $item) {
          // Check if productName is loaded and not null
          if ($item->productName) {
            $impressionList[$key]['master_product_id'] =  $item->productName->name;
          } else {
            $impressionList[$key]['master_product_id'] = null;
          }
          // Check if stepName is loaded and not null
          if ($item->stepName) {
            $impressionList[$key]['master_product_step_id'] =  $item->stepName->name;
          } else {
            $impressionList[$key]['master_product_step_id'] = null;
          }
          // Check if lead is loaded and not null
          if ($item->lead) {
            $impressionList[$key]['lead_id'] =  $item->lead->mobile_number;
          } else {
            $impressionList[$key]['lead_id'] = null;
          }
          unset($impressionList[$key]['stepName']);
          unset($impressionList[$key]['productName']);
          unset($impressionList[$key]['lead']);
          unset($impressionList[$key]['application']);
        }
      }
      $impressionData['totalLength'] =  $totalLength;
      $impressionData['dataList'] = $impressionList;
      return $impressionData;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("Impression list " . $throwable->__toString());
    }
  }
  /**
   * get current product step id
   *
   */
  public function getCurrentStepId($handle)
  {
    try {
      return HjMasterProductStep::where('handle', $handle)->value('id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getCurrentStepId " . $throwable->__toString());
    }
  }


  /**
   * fetch current Step Handle
   *
   */
  public function fetchCurrentStepHandle($request)
  {
    // get lead from quote id.
    $leadId = HjImpression::where('quote_id', $request->custom_string)->value('lead_id');
    try {
      return HjImpression::with(['stepName'])->where(
        'lead_id',
        $leadId
      )->where('quote_id', $request->custom_string)->orderBy('id', 'DESC')->first();
    } catch (Throwable |  HttpClientException $throwable) {
      Log::info("ImpressionRepository fetchCurrentStepHandle " . $throwable->__toString());
    }
  }


  /**
   * get Stage Percentage
   *
   */
  public function getStagePercentage($handle)
  {
    try {
      return HjMasterProductStep::where('handle', $handle)->value('percentage');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getStagePercentage " . $throwable->__toString());
    }
  }

  /**
   * get current product step id
   *
   */
  public function getPropertyDetails($masterProductId)
  {
    try {
      return HjMappingProductType::with('productType')->with('productDetails')->where('master_product_id', $masterProductId)->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getCurrentStepId " . $throwable->__toString());
    }
  }

  /**
   * get current product step id
   *
   */
  public function getProductDetails($masterProductId)
  {
    try {
      return HjMappingProductType::with('productType:name,id')->with('productDetails:id,code,display_name,handle,name')->where('master_product_id', $masterProductId)->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getProductDetails " . $throwable->__toString());
    }
  }

  /**
   * get product code by id
   *
   */
  public function getProductCode($masterProductId)
  {
    try {
      return HjMasterProduct::where('id', $masterProductId)->value('code');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getProductCode " . $throwable->__toString());
    }
  }

  /**
   * get product name
   *
   */
  public function getProductName($masterProductId)
  {
    try {
      return HjMasterProduct::where('id', $masterProductId)->value('display_name');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getProductName " . $throwable->__toString());
    }
  }


  /**
   * get product type
   *
   */
  public function getProductType($masterProductId)
  {
    try {
      return HjMappingProductType::with('productType:handle,id,name')->where('master_product_id', $masterProductId)->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getProductType " . $throwable->__toString());
    }
  }

  /**
   * get property identified
   *
   */
  public function getPropertyIdentified($quoteId)
  {
    try {
      return HjPersonalDetail::where('quote_id', $quoteId)->value('is_property_identified');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("ImpressionRepository getPropertyIdentified " . $throwable->__toString());
    }
  }

  /**
   * get current Impression id
   *
   */
  public function getCurrentImpId($request)
  {
    try {
      return HjImpression::where('quote_id', $request['quote_id'])->where('lead_id', $request['lead_id'])->orderBy('id', 'DESC')->value('id');
    } catch (Throwable |  HttpClientException $throwable) {
      Log::info("ImpressionRepository getCurrentImpId " . $throwable->__toString());
    }
  }

  /**
   * get latest impression
   *
   */
  public function getLastestImp($request)
  {
    try {
      return HjImpression::where('quote_id', $request['quote_id'])->where('lead_id', $request['lead_id'])->orderBy('id', 'DESC')->first();
    } catch (Throwable |  HttpClientException $throwable) {
      Log::info("ImpressionRepository getLastestImp " . $throwable->__toString());
    }
  }
}
