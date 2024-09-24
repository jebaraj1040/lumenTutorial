<?php

namespace App\Services\HousingJourney;

use Illuminate\Support\Facades\Log;
use App\Entities\HousingJourney\HjApplication;
use App\Entities\HousingJourney\HjMasterCcSubStage;
use App\Entities\HousingJourney\HjMasterCcStage;
use App\Entities\HousingJourney\HjMasterProduct;
use App\Repositories\HousingJourney\FieldTrackingRepository;
use App\Utils\JourneyTrait;
use App\Entities\MongoLog\FieldTrackingLog;
use App\Entities\MongoLog\CCDispositionLog;
use App\Entities\MongoLog\CCPushLog;
use App\Entities\MongoLog\BRELog;
use App\Repositories\HousingJourney\ApplicationRepository;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use App\Services\Service;
use App\Repositories\HousingJourney\CCPushRepository;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\AddressRepository;
use App\Repositories\HousingJourney\PaymentTransactionRepository;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use App\Repositories\HousingJourney\DocumentRepository;
use App\Repositories\HousingJourney\BreLogRepository;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use Carbon\Carbon;
use App\Entities\HousingJourney\HjMappingCcStage;

define('API_TYPE_REQUEST', config('constants/apiType.REQUEST'));
define('API_STATUS_ERROR_CODE', config('journey/http-status.error.code'));
define('API_STATUS_ERROR_MESSAGE', config('journey/http-status.error.message'));
class CCPushService extends Service
{
  use CrmTrait;
  use JourneyTrait;
  public function pushAllRecords()
  {
    try {
      $ftr = new FieldTrackingRepository();
      $trackedQuoteIds = [];
      $allTrackedRecords = $ftr->getAllFieldTrackingRecords();
      if ($allTrackedRecords && count($allTrackedRecords) > 0) {
        foreach ($allTrackedRecords as $trackedValue) {
          $count = $ftr->getInActiveTrackingRecords($trackedValue);
          if ($count == 0) {
            $trackedQuoteIds[] = $trackedValue;
          }
        }
        // loop tracked mobile numbers.
        if (count($trackedQuoteIds) > 0) {
          foreach ($trackedQuoteIds as $trackedQuoteId) {
            // check mobile_number exists in application.
            $application = HjApplication::with(['mappingProductStepCcStage', 'personaldetail', 'AddressDetail'])->where('quote_id', $trackedQuoteId)->get();
            if ($application) {
              // get is_purchased 0 records.
              foreach ($application as $applicationData) {
                if ($applicationData->is_stp == 0) {
                  // Prepare data to push cc.
                  $ftr->updateCCPushTag($applicationData);
                  Log::info('pushApplicationCCData');
                  $ccDispositionFlag = false;
                  $this->pushApplicationCCData($applicationData, $ccDispositionFlag);
                }
              }
            }
          }
        } else {
          Log::info('No Bulk Records found for CC Push!!!');
        }
      } else {
        Log::info('No Bulk Records found for CC Push!!!');
      }
    } catch (\Throwable $throwable) {
      Log::info("CCPushService pushAllRecords" . $throwable->__toString());
    }
  }

  public function pushIndividualRecord($mobileNumber)
  {
    try {
      //code...

      $ftr = new FieldTrackingRepository();
      $trackedRecords = $ftr->getIndividualFieldTrackingRecords($mobileNumber);
      if ($trackedRecords > 0) {
        // check mobile_number exists in application.
        $application = HjApplication::with(['mappingProductStepCcStage', 'personaldetail', 'AddressDetail', 'lead'])->where('mobile_number', $mobileNumber)->whereNotNull('cc_quote_id')->get();
        if ($application) {
          // get is_purchased 0 records.
          foreach ($application as $applicationData) {
            if ($applicationData->is_stp == 0) {
              // Prepare data to push cc.
              $ccDispositionFlag = false;
              $this->pushApplicationCCData($applicationData, $ccDispositionFlag);
            }
          }
        }
      } else {
        Log::info('No Individual Record found for CC Push!!!');
      }
    } catch (\Throwable $throwable) {
      Log::info("CCPushService pushIndividualRecord" . $throwable->__toString());
    }
  }

  public function pushApplicationCCData($applicationData, $ccDispositionFlag)
  {
    try {
      $trackedValue = FieldTrackingLog::where('mobile_number', (string)$applicationData->mobile_number)
        ->where('quote_id', (string)$applicationData->quote_id)
        ->where('lead_id', $applicationData->lead_id)
        ->orderBy('created_at', 'desc')
        ->first();

      if ($trackedValue !== null) {
        $paymentTransRepo = new PaymentTransactionRepository();
        $paymentTrasactionData = $paymentTransRepo->getTransactionData($applicationData);

        $leadRepo = new LeadRepository();
        $leadData = $leadRepo->view($applicationData->lead_id);
        $carbonDate = Carbon::createFromTimestampMs($trackedValue->created_at);
        $uploadDate = $carbonDate->format('Y-m-d H:i:s');
        $returnData['Uploaddate'] = $uploadDate;
        $title = null;
        if (isset($applicationData->personaldetail->gender) && $applicationData->personaldetail->gender !== null) {
          $title = 'Mr';
          if ($applicationData->personaldetail->gender == 'Female') {
            $title = 'Mrs';
          }
        }
        $middleName = $lastName = $firstName = null;
        if ($applicationData->name) {
          $nameArray = explode(" ", $applicationData->name);
          $count = count($nameArray);
          switch ($count) {
            case 1:
              $firstName  = $nameArray[0];
              break;
            case 2:
              $firstName  = $nameArray[0];
              $lastName  = $nameArray[1];
              break;
            case 3:
            case 4:
              $firstName  = $nameArray[0];
              $middleName  = $nameArray[1];
              $lastName  = $nameArray[2];
              break;
            default:
              $lastName = $middleName = $firstName  = $applicationData->name;
              break;
          }
        }
        $pinCode =  $leadRepo->getPincode($applicationData->lead->pincode_id);
        if (!$ccDispositionFlag) {
          $priority = $this->getCCPriority($applicationData, $trackedValue);
        } else {
          $priority = "HF-1301";
        }
        $subStageData =  $this->getSubStageData($priority);
        $stageData =  $this->getStageData($subStageData->id);
        $stageID = $stageData ? $stageData->stage_id : null;
        $returnData['Title'] = $title;
        $returnData['FirstName'] = $firstName;
        $returnData['MiddleName'] = $middleName;
        $returnData['LastName'] = $lastName;
        $returnData['DOB'] = $applicationData->personaldetail->dob ?? null;
        $returnData['MobileNo'] = $applicationData->mobile_number;
        $returnData['LeadID'] = $applicationData->cc_quote_id;
        $returnData['EmailID'] = $applicationData->personaldetail->email ?? null;
        $returnData['Priority'] = $priority;
        $returnData['BlockForCalling'] = $subStageData ? $subStageData->block_for_calling : null;
        $returnData['Stage'] = '0' . $stageID;
        $returnData['Stag_Description'] = $subStageData ? $subStageData->name : null;
        $returnData["Digital_Transaction_Id"] = $applicationData->digital_transaction_no ?? null;
        $returnData["Source"] = $trackedValue->api_source;
        $returnData["Pincode"] = $pinCode ?? null;
        $returnData["UserToken"] = $trackedValue->cc_quote_id ?? null;
        $returnData["UTM_GCLID"] = null;
        $returnData["WebsiteUrl"] = env('WEBSITE_URL');
        $returnData["Lan"] = null;
        $returnData["Statename"] = $applicationData->AddressDetail->state ?? null;
        $returnData["StatusUpdDt"] = $uploadDate;
        $leadCreatedTime = date('Y-m-d H:i:s', strtotime($applicationData->created_at));
        $returnData["lead_created_time"] = $leadCreatedTime;
        $returnData["Lead_date_Time"] = $leadCreatedTime;
        $returnData["Payment_amount"] = $paymentTrasactionData->amount ?? null;
        $returnData["Agent_id"] = null;
        $paymentReceiptData = $paymentTrasactionData->created_at ?? null;
        $returnData["Payment_receipt_date"] = $paymentReceiptData != null ? date('Y-m-d H:i:s', strtotime($paymentReceiptData)) : null;
        $returnData["Payment_method"] = $paymentTrasactionData->mode ?? null;
        $returnData["Payment_Failure_reason"] = $paymentTrasactionData->reason ?? null;
        $returnData["Remedial_measure"] = null;
        $returnData["Transaction_Date_No"] = $paymentTrasactionData->transaction_time ?? null;
        $returnData["Bank_Name"] = $paymentTrasactionData->bank_name ?? null;
        $returnData["Transaction_reference_No"] = $paymentTrasactionData->neft_payment_transaction_id ?? null;
        $returnData["Customer_Type"] = $leadData->customer_type ?? null;
        $returnData["Product_Name"] = HjMasterProduct::where('id', $trackedValue->master_product_id)->value('display_name');
        $returnData["Sub_Stage"] = $stageData ? $stageData->name : null;
        $isDeviation = null;
        $breLogCount = BRELog::where('quote_id', $applicationData->quote_id)->orderBy('created_at', 'desc')->count();
        if ($breLogCount > 0) {
          $breLog = BRELog::where('quote_id', $applicationData->quote_id)->orderBy('created_at', 'desc')->first();
          $apiData = json_decode($breLog->api_data, true);
          if ($apiData && isset($apiData['Table1']) && count($apiData['Table1']) == 1) {
            $isDeviation = $apiData['Table1'][0]['IsDev'] ?? null;
          }
        }
        $paymentFlag = "N";
        if ($applicationData->is_paid == 1 && !empty($applicationData->payment_transaction_id)) {
          $paymentFlag = "Y";
        }
        $returnData["Payment_Status"] = $paymentFlag;
        $returnData["BRE_Status"] = $isDeviation;
        // update cc push sent stauts to 1 in field tracking log.
        FieldTrackingLog::where('_id', $trackedValue->_id)->update(['cc_push_status' => 1, 'cc_push_tag' => 0]);

        // Log entry in cc_push_log.
        // prepare log data.
        $logData['lead_id'] = $applicationData->lead_id ?? null;
        $logData['quote_id'] = $applicationData->quote_id ?? null;
        $logData['cc_quote_id'] = $applicationData->cc_quote_id ?? null;
        $logData['mobile_number'] = $applicationData->mobile_number ?? null;
        $logData['master_product_id'] = $applicationData->master_product_id ?? null;
        $logData['api_source'] = $trackedValue->api_source ?? null;
        $logData['api_source_page'] = $trackedValue->api_source_page ?? null;
        $logData['api_type'] = config('constants/apiType.CC_PUSH');
        $logData['api_header'] = $trackedValue->api_header ?? null;
        $logData['api_url'] = env('CC_PUSH_URL');
        $logData['cc_push_stage_id'] =   $stageID ?? null;
        $logData['cc_push_sub_stage_id'] = $subStageData->id ?? null;
        $logData['cc_push_sub_stage_priority'] = $priority ?? null;
        $logData['api_request_type'] = API_TYPE_REQUEST;
        $logData['api_data'] = $returnData;
        $ccData =  $this->saveCCPushLog($logData);

        // make post API Call.
        $method = 'POST';
        $payLoad['data'] = $returnData;
        $payLoad['api_url'] = env('CC_PUSH_URL');
        $payLoad['method'] = $method;
        $payLoad['api_source'] = $trackedValue->api_source ?? null;
        $response =  $this->clientApiCall($payLoad, [
          'Content-Type' => 'application/json',
          'X-Api-Source' => $payLoad['api_source'],
          'X-Api-Source-Page' => config('constants/apiSourcePage.CC_PUSH_PAGE'),
          'X-Api-Type' => config('constants/apiType.CC_PUSH')
        ]);
        $ccData['response'] = $response;
        Log::info('CC : Client Api Response ', [$response]);
        // findCCData
        if (!empty($response) && !empty($response['status']) && $ccData) {
          $ccData['quote_id'] = $applicationData->quote_id ?? null;
          $ccData['cc_quote_id'] = $applicationData->cc_quote_id ?? null;
          $ccData['master_product_id'] = $applicationData->master_product_id ?? null;
          $ccData['api_status_code'] = config('journey/http-status.success.code');
          $ccData['api_status_message'] =  config('journey/http-status.success.message');
          $this->updateCCPushLog($ccData);
          Log::info('CCPush Status Code ' . config('journey/http-status.success.code'));
        } else {
          $ccData['id'] = $ccData->id;
          $ccData['quote_id'] = $applicationData->quote_id ?? null;
          $ccData['cc_quote_id'] = $applicationData->cc_quote_id ?? null;
          $ccData['master_product_id'] = $applicationData->master_product_id ?? null;
          $ccData['api_status_code'] = config('journey/http-status.time-out.code');
          $ccData['api_status_message'] =  config('journey/http-status.time-out.message');
          $this->updateCCPushLog($ccData);
          Log::info('CCPush Status error  : ', [$response]);
        }
      } else {
        Log::info('No Application Record found for CC Push!!!');
      }
    } catch (\Throwable $throwable) {
      Log::info("CCPushService pushApplicationCCData" . $throwable->__toString());
    }
  }

  public function getCCPriority($applicationData, $fieldTrackData)
  {
    try {
      $ftr = new FieldTrackingRepository();
      $ccStagePriority = $fieldTrackData->cc_push_sub_stage_priority;
      switch ($fieldTrackData->cc_push_sub_stage_priority) {
        case 'HF-1004':
          if ($applicationData->is_paid == 1 && $applicationData->payment_transaction_id && $ftr->checkPriorityExist($applicationData, "HF-1201") > 0) {
            $ccStagePriority = "HF-1303";
          }
          break;
        case 'HF-1101':
          if ($applicationData->is_paid == 1 && $applicationData->payment_transaction_id) {
            $ccStagePriority = "HF-1302";
          }
          break;
        case 'HF-1201':
          if ($applicationData->is_paid == 1 && $applicationData->payment_transaction_id && $applicationData->is_bre_execute == 1) {
            $ccStagePriority = "HF-1303";
          } elseif ($applicationData->is_bre_execute == 0) {
            $ccStagePriority = "HF-1304";
          }
          break;
        case 'HF-1202':
          $ccStagePriority = "HF-1305";
          break;
        default:
          $ccStagePriority = $fieldTrackData->cc_push_sub_stage_priority;
          break;
      }
      return $ccStagePriority;
    } catch (Throwable  | ClientException $throwable) {
      Log::info("CCPushService getCCPriority" . $throwable->__toString());
    }
  }


  public function getSubStageData($priority)
  {
    try {
      return  HjMasterCcSubStage::where('priority', $priority)->first();
    } catch (Throwable  | ClientException $throwable) {
      Log::info("CCPushService getSubStageData" . $throwable->__toString());
    }
  }

  /**
   * Get Stage Data.
   *
   * @param $stageId
   */
  public function getStageData($stageId)
  {
    try {

      $ccMapData = HjMappingCcStage::with('masterCcStage')
        ->where('master_cc_sub_stage_id', $stageId)
        ->first();
      return $ccMapData ? $ccMapData->masterCcStage : null;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("CCDispositionLog getStageData" . $throwable->__toString());
    }
  }

  public function pushNonApplicationCCData($trackedMobileNumber)
  {
    try {
      $trackedValue = FieldTrackingLog::where('mobile_number', $trackedMobileNumber)->orderBy('created_at', 'desc')->first();
      $carbonDate = Carbon::createFromTimestampMs($trackedValue->created_at);
      $uploadDate = $carbonDate->format('Y-m-d H:i:s');
      $returnData['Uploaddate'] = $uploadDate;
      $returnData['Stage'] = $trackedValue->cc_push_stage_id;
      $returnData['Title'] = null;
      $returnData['FirstName'] = $trackedValue->full_name ?? null;
      $returnData['MiddleName'] = null;
      $returnData['LastName'] = null;
      $returnData['DOB'] = null;
      $returnData['MobileNo'] = $trackedValue->mobile_number ?? null;
      $returnData['LeadID'] = null;
      $returnData['EmailID'] = null;
      $returnData['Priority'] = $trackedValue->cc_push_sub_stage_priority;
      $returnData['Stag_Description'] = HjMasterCcSubStage::where('priority', $trackedValue->cc_push_sub_stage_priority)->value('name');
      $returnData["Digital_Transaction_Id"] = null;
      $returnData["Source"] = $trackedValue->api_source;
      $returnData["Pincode"] = $trackedValue->pincode ?? null;
      $returnData["UserToken"] = null;
      $returnData["UTM_GCLID"] = null;
      $returnData["WebsiteUrl"] = env('WEBSITE_URL');
      $returnData["Lan"] = null;
      $returnData["Statename"] = null;
      $returnData["StatusUpdDt"] = null;
      $returnData["lead_created_time"] = null;
      $returnData["Lead_date_Time"] = null;
      $returnData["Payment_amount"] = null;
      $returnData["Agent_id"] = null;
      $returnData["Payment_receipt_date"] = null;
      $returnData["Payment_method"] = null;
      $returnData["Payment_Failure_reason"] = null;
      $returnData["Transaction_Date_No"] = null;
      $returnData["Bank_Name"] = null;
      $returnData["Transaction_reference_No"] = null;
      $returnData["Customer_Type"] = null;
      $returnData["Product_Name"] = HjMasterProduct::where('id', $trackedValue->master_product_id)->value('display_name');
      $returnData["Sub_Stage"] =  HjMasterCcStage::where('id', $trackedValue->cc_push_stage_id)->value('name');

      // update cc push sent stauts to 1 in field tracking log.
      FieldTrackingLog::where('_id', $trackedValue->_id)->update(['cc_push_status' => 1]);

      // Log entry in cc_push_log.
      // prepare log data.
      $logData['lead_id'] =  null;
      $logData['quote_id'] = null;
      $logData['mobile_number'] = $trackedValue->mobile_number ?? null;
      $logData['master_product_id'] = $trackedValue->master_product_id ?? null;
      $logData['api_source'] = $trackedValue->api_source ?? null;
      $logData['api_source_page'] = $trackedValue->api_source_page ?? null;
      $logData['api_type'] = $trackedValue->api_type ?? null;
      $logData['api_header'] = $trackedValue->api_header ?? null;
      $logData['api_url'] = env('CC_PUSH_URL');
      $logData['api_request_type'] = API_TYPE_REQUEST;
      $logData['api_data'] = $returnData;
      $logData['api_status_code'] = config('journey/http-status.success.code');
      $logData['api_status_message'] = config('journey/http-status.success.message');
      $this->saveCCPushLog($logData);

      // make post API Call.
      $method = 'POST';
      $payLoad['data'] = $returnData;
      $payLoad['api_url'] = env('CC_PUSH_URL');
      $payLoad['method'] = $method;
      $payLoad['api_source'] = $trackedValue->api_source  ?? null;

      return $this->clientApiCall($payLoad, [
        'Content-Type' => 'application/json',
        'X-Api-Source' => $payLoad['api_source'],
        'X-Api-Source-Page' => config('apiSourcePage.CC_PUSH_PAGE'),
        'X-Api-Type' => config('apiSourcePage.CC_PUSH')
      ]);
    } catch (Throwable  | ClientException $throwable) {
      Log::info("CCPushService pushNonApplicationCCData" . $throwable->__toString());
    }
  }

  /**
   * Insert log to cc disposition log table.
   *
   * @param $request
   */
  public function saveCCDispositionLog($requestData)
  {
    try {
      return CCDispositionLog::create($requestData);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("CCDispositionLog " . $throwable->__toString());
    }
  }

  /**
   * Insert log to cc push log table.
   *
   * @param $request
   */
  public function saveCCPushLog($requestData)
  {
    try {
      return CCPushLog::create($requestData);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("saveCCPushLog " . $throwable->__toString());
    }
  }

  /**
   * update log to cc push log table.
   *
   * @param $request
   */
  public function updateCCPushLog($requestData)
  {
    try {
      $ccData =  CCPushLog::where('quote_id', $requestData['quote_id'])->where('cc_quote_id', $requestData['cc_quote_id'])->orderBy('created_at', 'desc')->first();
      if (!empty($ccData)) {
        $ccData->api_status_code = $requestData['api_status_code'];
        $ccData->api_status_message = $requestData['api_status_message'];
        $ccData->response = $requestData['response'];
        return $ccData->save();
      }
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("updateCCPushLog " . $throwable->__toString());
    }
  }

  public function receiveRecord(Request $request, CCPushRepository $ccpr)
  {
    try {
      // check lead id exists.
      if (isset($request['LeadID']) && $request['LeadID'] !== '') {
        // check status and prepare final submit.
        // get quote id from LeadID.
        $apRepo = new ApplicationRepository();
        $ccData = $apRepo->getDataByCCQuoteId($request['LeadID']);
        if (empty($ccData) || $ccData == null) {
          return $this->responseJson(
            config('journey/http-status.failure.status'),
            config('journey/http-status.failure.message'),
            config('journey/http-status.failure.code'),
            []
          );
        }
        if (isset($request['Status']) && $request['Status'] !== '' && $request['Status'] == 'Interested' && isset($request['SubStatus']) && $request['SubStatus'] !== '' && $request['SubStatus'] == 'Forward to Branch/Field - Home Pickup') {
          // call final submit.
          $finalArray['lead_id'] = $ccData->lead_id;
          $finalArray['quote_id'] = $ccData->quote_id ?? null;
          $finalArray['X-Auth-CC-Token'] = $request->header('X-Auth-CC-Token');
          $finalArray['api_source_page'] = $request->header('X-Api-Source-Page');
          $ccpr->ccFinalSubmit($finalArray);
          $ccDispositionFlag = true;
          $this->pushApplicationCCData($ccData, $ccDispositionFlag);
        }
        $request['SubSource'] = $request['Sub Source'];
        $insertCC = CCDispositionLog::create($request->all());
        if ($ccData) {
          $requestData['lead_id'] = $ccData->lead_id;
          $requestData['quote_id'] = $ccData->quote_id;
          $requestData['disposition_status'] = $request['Status'];
          $requestData['disposition_sub_status'] = $request['SubStatus'];
          $requestData['disposition_date'] = $request['StatusUpdDt'];
          $apRepo->save($requestData);
        }
        if ($insertCC) {
          return $this->responseJson(
            config('journey/http-status.success.status'),
            config('journey/http-status.success.message'),
            config('journey/http-status.success.code'),
            [$insertCC]
          );
        } else {
          return $this->responseJson(
            config('journey/http-status.error.status'),
            API_STATUS_ERROR_MESSAGE,
            API_STATUS_ERROR_CODE,
            []
          );
        }
      } else {
        return $this->responseJson(
          config('journey/http-status.error.status'),
          API_STATUS_ERROR_MESSAGE,
          API_STATUS_ERROR_CODE,
          []
        );
      }
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("receiveRecord " . $throwable->__toString());
      return $this->responseJson(
        config('journey/bad-request.status'),
        config('journey/bad-request.message'),
        config('journey/bad-request.code'),
        []
      );
    }
  }
  /**
   * getCCPushLog
   *
   * @param  Request $request
   * @return mixed
   */
  public function getCCPushLog(Request $request, CCPushRepository $ccPushRepo): mixed
  {
    try {
      $logsList = $ccPushRepo->getCCPushLog($request);
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $logsList
      );
    } catch (Throwable   | ClientException $throwable) {
      throw new Throwable(
        Log::info("Service : CCPushService , Method : getLogList : %s", $throwable->__toString())
      );
    }
  }
  /**
   * getFilterData
   *
   * @param Empty
   * @return mixed
   */
  public function getFilterData()
  {
    try {
      $apiSource = $this->getFilterDatas('apiSource');
      $apiSourcePage = $this->getFilterDatas('apiSourcePage');
      $apiType = $this->getFilterDatas('apiType');
      $filterList['api_source'] =  $this->convertFilterData($apiSource, 'api_source');
      $filterList['api_source_page'] =  $this->convertFilterData($apiSourcePage, 'api_source_page');
      $filterList['api_type'] =  $this->convertFilterData($apiType, 'api_type');
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $filterList
      );
    } catch (Throwable  | ClientException $throwable) {
      throw new Throwable(
        Log::info("Service : CCPushService , Method : getFilterData : %s", $throwable->__toString())
      );
    }
  }

  /**
   * Get Lead data based on Lead cc_quote_id
   *
   * @param $request
   * @return mixed
   */
  public function getCCQuoteIDData(Request $request)
  {
    try {
      if (!isset($request->lead_id)) {
        return $this->responseJson(
          config('journey/http-status.bad-request.status'),
          config('journey/http-status.bad-request.message'),
          config('journey/http-status.bad-request.code'),
          []
        );
      }
      $leadRepo = new LeadRepository();
      $appRepo = new ApplicationRepository();
      $empRepo = new EmploymentDetailRepository();
      $addressRepo = new AddressRepository();
      $propertyRepo = new PropertyLoanDetailRepository();
      $docRepo = new DocumentRepository();
      $paymentRepo = new PaymentTransactionRepository();
      $breLogRepo = new BreLogRepository();
      $apRepo = new ApplicationRepository();


      $appData = $appRepo->getCCQuoteData($request->lead_id);

      if (!$appData) {
        return $this->responseJson(
          config('journey/http-status.bad-request.status'),
          config('journey/http-status.bad-request.message'),
          config('journey/http-status.bad-request.code'),
          []
        );
      }
      $reqData['quote_id'] = $appData['quote_id'];
      $reqData['lead_id'] = $appData['lead_id'];
      $leadData = $appRepo->getEntireLeadDataByCCQuoteID($appData['quote_id']);
      if (count($leadData['personalDetails'])  > 1) {
        $leadReqData['lead_id'] = $leadData['personalDetails'][1]['lead_id'];
        $leadReqData['quote_id'] = $leadData['personalDetails'][1]['quote_id'];
        $leadData['personalDetails'][1]['relation'] = $appRepo->getLeadRelationship($leadReqData);
      }
      $leadData['lead'] = $leadRepo->view($reqData['lead_id']);
      $leadData['employment_details'] = $empRepo->getEmploymentDetail($reqData);
      $leadData['address_details'] = $addressRepo->getAddressData($reqData);
      $leadData['property_details'] = $propertyRepo->view($reqData);
      $breOneData = $breLogRepo->fetchBreOneData($reqData);
      if (isset($breOneData) && $breOneData != '' && isset($breOneData->api_data)) {
        $breResponseData = json_decode($breOneData->api_data, true);
        $leadData['is_deviations'] = $breResponseData['Table1'][0]['IsDev'] ?? null;
      }
      if ($leadData['is_deviations'] == "N") {
        $breData = $breLogRepo->fetchBreTwoData($reqData);
        if (isset($breData) && $breData != '' && isset($breData->api_data)) {
          $breResponseData = json_decode($breData->api_data, true);
          $leadData['is_deviations'] = $breResponseData['Table1'][0]['IsDev'] ?? null;
        }
      }

      $leadData['document_details'] = $docRepo->getDocument($reqData);
      $leadData['payment_details'] = $paymentRepo->getPaymentTransactionData($reqData);
      return $this->responseJson(
        config('crm/http-status.success.status'),
        config('crm/http-status.success.message'),
        config('crm/http-status.success.code'),
        $leadData
      );
    } catch (Throwable  | ClientException $throwable) {
      throw new Throwable(
        Log::info("Service : CCPushService , Method : getCCQuoteIDData : %s", $throwable->__toString())
      );
    }
  }

  /**
   * Export Log.
   *
   * @param $request
   *
   */

  public function exportLog(Request $request, CCPushRepository $repository)
  {
    try {
      $datas['methodName'] = 'getCCPushLog';
      $datas['fileName'] = 'CC-Push-Log-Report-';
      $datas['moduleName'] = 'CC-Push-Log';
      return $this->exportData($request, $repository, $datas);
    } catch (Throwable  | ClientException $throwable) {
      Log::info("CCPushExport " . $throwable->__toString());
      return $this->responseJson(
        config('crm/http-status.error.status'),
        config('crm/http-status.error.message'),
        config('crm/http-status.error.code'),
        []
      );
    }
  }
}
