<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjAddress as HjAddress;
use App\Entities\HousingJourney\HjMasterPincode;
use App\Entities\MongoLog\KarzaLog;
use App\Utils\CrmTrait;

class AddressRepository
{
  use CrmTrait;
  /**
   * Insert AddressRepository.
   *
   */
  public function save($request)
  {
    try {
      return HjAddress::create($request);
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("addressDetaiLRepository save " . $throwable->__toString());
    }
  }
  /**
   * get address
   *
   */
  public function view($request)
  {
    try {
      return HjAddress::where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])
        ->first();
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("addressDetaiLRepository view" . $throwable->__toString());
    }
  }
  /**
   * get address details
   *
   */
  public function getAddressDetail($request, $type)
  {
    try {
      $query = HjAddress::query();
      $query->with('pincodeDetail')->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id']);
      if ($type == "permanent_address") {
        $query->where('is_permanent_address', 1);
      } elseif ($type == "current_address") {
        $query->where('is_current_address', 1);
      }
      $addressData = $query->first();
      return $addressData;
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("Get Address Detail" . $throwable->__toString());
    }
  }
  /**
   * remove the existing address
   */
  public function delete($request)
  {
    try {
      HjAddress::where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id'])->delete();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressRepository delete" . $throwable->__toString());
    }
  }
  /**
   * save pan details into karza history table
   */
  public function saveKarzaHistroy($request)
  {
    try {
      KarzaLog::create($request);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressRepository saveKarzaHistroy " . $throwable->__toString());
    }
  }

  /**
   * get pan details from karza history table
   */
  public function viewKarzaHistroy($request)
  {
    try {
      return KarzaLog::select('api_data', 'created_at')->where('pan', $request['pan'])->where('api_status_code', 200)->whereNotNull('api_data')
        ->orderBy('created_at', 'DESC')->first();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressRepository viewKarzaHistroy " . $throwable->__toString());
    }
  }

  /**
   * get address data from address table
   */
  public function getAddressData($request)
  {
    try {
      return  HjAddress::select(
        'address1',
        'address2',
        'area',
        'city',
        'is_current_address',
        'is_permanent_address',
        'pincode_id',
        'state'
      )->with('pincodeDetail:id,code')
        ->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id'])->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressRepository getAddressData " . $throwable->__toString());
    }
  }

  /**
   * get pincode id using pincode
   */
  public function getPincodeId($pincode)
  {
    try {
      return HjMasterPincode::where('code', $pincode)->value('id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("AddressRepository getPincodeId " . $throwable->__toString());
    }
  }
  /**
   * list address from Table
   * @param $request
   */
  public function list($request, $offset = null)
  {
    try {
      $query = HjAddress::query();
      $query = $this->applyFilter($query, $request);
      if (isset($request->search) && $request->search != '' && $request->search != 'null') {
        $keyword = $request->search;
        $query->where('quote_id', $keyword);
        $query->orWhere('lead_id', $keyword);
        $query->orWhereHas('lead', function ($subquery) use ($keyword) {
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
      $addressData = $query->select('*')
        ->with('lead:id,mobile_number')
        ->with('pincodeDetail:id,code')
        ->orderBy('id', 'desc')
        ->get();

      if ($request->action == 'download') {
        foreach ($addressData as $key => $item) {
          // Check if pincodeDetail is loaded and not null
          if ($item->pincodeDetail) {
            $addressData[$key]['pincode_id'] =  $item->pincodeDetail->code;
          } else {
            $addressData[$key]['pincode_id'] = null;
          }
          // Check if lead is loaded and not null
          if ($item->lead) {
            $addressData[$key]['lead_id'] =  $item->lead->mobile_number;
          } else {
            $addressData[$key]['lead_id'] = null;
          }
          unset($addressData[$key]['pincodeDetail']);
          unset($addressData[$key]['lead']);
        }
      }
      $addressDetailData['totalLength'] =  $totalLength;
      $addressDetailData['dataList'] = $addressData;
      return $addressDetailData;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("AddressRepository list " . $throwable->__toString());
    }
  }
}
