<?php

namespace App\Repositories\HousingJourney;

use App\Entities\MongoLog\OtpLog;
use App\Entities\MongoLog\OtpAttempt;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Services\Service;
use App\Utils\CrmTrait;

class OtpLogRepository extends Service
{
  use CrmTrait;
  /**
   * Save the lead otp stages
   *
   * @param $request
   * @return mixed
   */
  public function save($request)
  {
    try {
      return OtpLog::create($request);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLog save: " . $throwable->__toString());
    }
  }



  /**
   * get product id.
   *
   * @param $mobileNumber
   * @return mixed
   */
  public function getProductId($mobileNumber)
  {
    try {
      $mobile = (string)$mobileNumber;
      return OtpLog::where('mobile_number', $mobile)->where('otp_flag',  config('constants/apiType.OTP_SENT'))->orderBy('created_at', 'DESC')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLog getProductId: " . $throwable->__toString());
    }
  }

  /**
   * verify otp.
   *
   * @param $request
   * @return mixed
   */
  public function verifyOtp($request)
  {
    try {
      $mobileNumber = (string)$request->mobile_number;
      return OtpLog::where('mobile_number', $mobileNumber)->where('is_otp_sent', 1)->where('otp_flag', '!=', 'OTP_INVALID')->orderBy('created_at', 'DESC')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLog verifyOtp: " . $throwable->__toString());
    }
  }
  /**
   * get resend otp data.
   *
   * @param $request
   * @return mixed
   */
  public function getResendOtp($request)
  {
    try {
      $currentDate = date('Y-m-d H:i:s');
      $startDate = date('Y-m-d H:i:s', strtotime('-30 minutes'));
      $endDate = date('Y-m-d H:i:s', strtotime($currentDate));
      $query = OtpLog::query();
      $query->where('mobile_number', $request->mobile_number);
      $query->where('is_otp_sent', 1);
      $query->where('otp_flag', '!=', config('constants/otpMessage.OTP_INVALID'));
      $query->where('api_source_page', $request['source_page']);
      if ($request->header('X-Api-Source-Page') != "TRACK_APPLICATION") {
        $query->where('master_product_id', $request['master_product_id']);
      }
      $query->whereBetween(
        'created_at',
        array(
          Carbon::createFromDate($startDate),
          Carbon::createFromDate($endDate)
        )
      );
      return  $query->orderBy('created_at', 'DESC')->limit(5)->get();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLog getResendOtp: " . $throwable->__toString());
    }
  }


  /**
   * get resend otp data.
   *
   * @param $request
   * @return mixed
   */
  public function getOtpAttempt($request)
  {
    try {
      $currentDate = date('Y-m-d H:i:s');
      $default_start_date = date('Y-m-d H:i:s', strtotime('-30 minutes'));
      $default_end_date = date('Y-m-d H:i:s', strtotime($currentDate));
      return  OtpAttempt::where('mobile_number', $request->mobile_number)
        ->where('api_source_page', $request['source_page'])
        ->where('master_product_id', $request['master_product_id'])
        ->whereBetween(
          'created_at',
          array(
            Carbon::createFromDate($default_start_date),
            Carbon::createFromDate($default_end_date)
          )
        )->orderBy('created_at', 'DESC')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpAttempt getOtpAttempt: " . $throwable->__toString());
    }
  }
  /**
   * get invalid otp data.
   *
   * @param $request
   * @return mixed
   */
  public function checkInvalidOtp($request)
  {
    try {
      $currentDate = date('Y-m-d H:i:s');
      $startDate = date('Y-m-d H:i:s', strtotime('-30 minutes'));
      $endDate = date('Y-m-d H:i:s', strtotime($currentDate));
      $query = OtpLog::query();
      $query->where('mobile_number', (string)$request->mobile_number);
      $query->where('is_otp_sent', 1);
      $query->where('api_source_page', $request['source_page']);
      if ($request->header('X-Api-Source-Page') != "TRACK_APPLICATION") {
        $query->where('master_product_id', $request['master_product_id']);
      }
      $query->whereBetween(
        'created_at',
        array(
          Carbon::createFromDate($startDate),
          Carbon::createFromDate($endDate)
        )
      );
      return  $query->orderBy('created_at', 'DESC')->limit(4)->get();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLog getResendOtp: " . $throwable->__toString());
    }
  }
  /**
   * get latest loan amount
   *
   * @param $request
   * @return mixed
   */
  public function getLatestLoanAmount($appData)
  {
    try {
      $mobileNumber = (string)$appData['mobile_number'];
      $masterOriginProductId = (int)$appData['master_origin_product_id'];
      return OtpLog::where('mobile_number', $mobileNumber)->where(
        'api_type',
        config('constants/apiType.OTP_SENT')
      )->where('api_source_page', config('constants/apiSourcePage.HOME_PAGE'))->where('master_product_id', $masterOriginProductId)->orderBy('created_at', 'DESC')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLogRepository getLatestLoanAmount: " . $throwable->__toString());
    }
  }
  /**
   *Get logs from Table
   * @param $request
   */
  public function getLog($request, $offset = null)
  {
    try {
      $query = OtpLog::query();
      $query = $this->applyFilter($query, $request);
      if ($request->search != '' && $request->search != 'null') {
        $keyword = $request->search;
        $query->where(function ($query) use ($keyword) {
          $query->orWhere('mobile_number', $keyword);
          $query->orWhere('quote_id', 'LIKE', '%' . $keyword . '%');
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
      $query->options(['allowDiskUse' => true]);
      $otpLogList = $query->orderby('created_at', 'DESC')->get();
      $logList = [];
      $logList['totalLength'] =  $totalLength;
      $logList['dataList'] = $otpLogList;
      return $logList;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLogRepository  getLog" . $throwable->__toString());
    }
  }

  /**
   *Get logs from Table
   * @param $request
   */
  public function getOtpAttempts($request)
  {
    try {
      return OtpAttempt::where('mobile_number', $request['mobile_number'])->orderBy('created_at', 'DESC')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLogRepository  getOtpAttempts" . $throwable->__toString());
    }
  }

  /**
   *Get logs from Table
   * @param $request
   */
  public function removeOtpAttempts($request, $offset = null)
  {
    try {
      return OtpAttempt::where('mobile_number', $request['mobile_number'])
        ->where('api_source_page', $request['api_source_page'])
        ->where('master_product_id', $request['product_code'])
        ->delete();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLogRepository  removeOtpAttempts" . $throwable->__toString());
    }
  }


  /**
   * Save the lead otp stages
   *
   * @param $request
   * @return mixed
   */
  public function createOtpAttempts($request)
  {
    try {
      $getRecord = OtpAttempt::where('mobile_number', $request['mobile_number'])
        ->where('api_source_page', $request['api_source_page'])
        ->where('master_product_id', $request['master_product_id'])->where('created_at', '>=', carbon::now()->subMinutes(30))->first();
      if ($getRecord) {
        $getRecord->count += 1;
        $getRecord->updated_at = Carbon::now();
        $getRecord->save();
      } else {
        $getRecord = OtpAttempt::create($request);
      }
      return $getRecord;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("OtpLog save: " . $throwable->__toString());
    }
  }
}
