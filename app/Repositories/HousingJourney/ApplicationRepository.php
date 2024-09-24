<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\HousingJourney\HjApplication;
use App\Entities\HousingJourney\HjMappingProductType;
use App\Entities\HousingJourney\HjMasterProductStep;
use App\Utils\CrmTrait;
use App\Entities\HousingJourney\HjMappingCoApplicant;
use Carbon\Carbon;
use App\Entities\HousingJourney\HjMasterCcStage;
use App\Entities\HousingJourney\HjMasterCcSubStage;
use App\Entities\HousingJourney\HjMappingApplicantRelationship;
use App\Entities\HousingJourney\HjMappingProductStepCcStage;
use App\Entities\HousingJourney\HjMappingCcStage;
use App\Entities\HousingJourney\HjPersonalDetail;
use App\Entities\MongoLog\FieldTrackingLog;
use App\Entities\MongoLog\SmsLog;

// Define constant
define('MASTER_PRODUCT_STEP_FIELDS', 'masterproductstep:id,name,handle,percentage');

class ApplicationRepository
{
    use CrmTrait;
    /**
     * Insert ApplicationRepository.
     *
     */
    public function save($request)
    {
        try {
            return HjApplication::updateOrCreate(['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id']], $request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository save " . $throwable->__toString());
        }
    }
    /**
     * Get the applications details.
     *
     * @param $quoteId
     */
    public function getApplication($quoteId)
    {
        try {
            return HjApplication::select('lead_id', 'quote_id', 'loan_amount', 'payment_transaction_id', 'digital_transaction_no', 'cibil_score', 'bre1_updated_loan_amount', 'bre1_loan_amount', 'bre2_loan_amount', 'offer_amount', 'is_paid', 'is_traversed', 'is_bre_execute', 'master_product_id')->with('masterproduct:id,code,display_name,handle,name,processing_fee,product_id')->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getApplication " . $throwable->__toString());
        }
    }

    /**
     * Get the applications data.
     *
     * @param $quoteId
     */
    public function getApplicationData($quoteId)
    {
        try {
            return HjApplication::select('cc_quote_id', 'auth_token', 'mobile_number', 'lead_id', 'quote_id', 'payment_transaction_id', 'digital_transaction_no', 'bre1_updated_loan_amount', 'bre1_loan_amount', 'is_paid', 'master_product_id')->with('masterproduct:id,code,display_name,handle,name,processing_fee,product_id')->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getApplicationData " . $throwable->__toString());
        }
    }

    /**
     * Get the applications details by cc quote ID.
     *
     * @param $quoteId
     */
    public function getCCQuoteData($quoteId)
    {
        try {
            return HjApplication::with('masterproduct')->where('cc_quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getCCQuoteData " . $throwable->__toString());
        }
    }

    /**
     * Get the Lead details.
     *
     * @param $quoteId
     */
    public function getEntireLeadDataByCCQuoteID($quoteId)
    {
        try {
            return HjApplication::with('personalDetails', 'eligibilityData', 'productData')->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getEntireLeadDataByCCQuoteID " . $throwable->__toString());
        }
    }

    /**
     * Get the Lead Relationship Data.
     *
     * @param $leadId
     */
    public function getLeadRelationship($leadData)
    {
        try {
            return HjMappingApplicantRelationship::with('relationship')->where('lead_id', $leadData['lead_id'])->where('quote_id', $leadData['quote_id'])->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getLeadRelationship " . $throwable->__toString());
        }
    }

    /**
     * Get quote Id details.
     *
     * @param $reqData
     * @return object
     */
    public function getQuoteIdDetails($reqData)
    {
        try {
            return  HjApplication::where('quote_id', $reqData['quote_id'])->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }

    /**
     * Get Email ID details.
     *
     * @param $quoteId
     */
    public function getEmailID($quoteId)
    {
        try {
            return  HjPersonalDetail::where('quote_id', $quoteId)->value('email');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }


    /**
     * Get quote Id details.
     *
     * @param $reqData
     * @return object
     */
    public function getBREAmount($reqData)
    {
        try {
            return  HjApplication::select('bre1_loan_amount', 'bre1_updated_loan_amount')->where('quote_id', $reqData['quote_id'])->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }

    /**
     * Get Master Step Id
     *
     * @param $reqData
     * @return object
     */
    public function getMasterProductStepId($handle)
    {
        try {
            return HjMasterProductStep::where('handle', $handle)->value('id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getMasterProductStepId " . $throwable->__toString());
        }
    }

    /**
     * Get Payment Data By QuoteId
     *
     * @param $reqData
     * @return object
     */
    public function getPaymentDataByQuoteId($reqData)
    {
        try {
            return  HjApplication::select('quote_id', 'is_paid', 'is_traversed', 'payment_transaction_id')->with('paymentTransaction:payment_transaction_id,amount,quote_id')->where('quote_id', $reqData['quote_id'])->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }

    /**
     * Get Co Applicant Id By QuoteId
     *
     * @param $reqData
     * @return object
     */
    public function getCoApplicantId($reqData)
    {
        try {
            return  HjMappingCoApplicant::where('quote_id', $reqData['quote_id'])->value('co_applicant_id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }

    /**
     * Get application data by token details.
     *
     * @param $reqData
     * @return object
     */
    public function getAuthTokenByToken($authToken)
    {
        try {
            return  HjApplication::with(MASTER_PRODUCT_STEP_FIELDS)->where('auth_token', $authToken)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }


    /**
     * Get  Application Details
     *
     * @param $reqData
     * @return mixed
     */
    public function getApplicationDetails($quoteId)
    {
        try {
            return HjApplication::with('productData')->with('personalDetail')->with('AddressDetail')->with('lead')->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info($throwable->__toString());
        }
    }

    /* * Fetch the Application data
     *
     */
    public function list($request, $offset = null)
    {
        try {
            $query = HjApplication::query();
            $query = $this->applyFilter($query, $request);
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('quote_id', $keyword);
                    $query->orWhere('cc_quote_id', $keyword);
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('lead_id', $keyword);
                    $query->orWhere('mobile_number', $keyword);
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
            $applicationList = $query->with('lead:id,name,mobile_number')
                ->with('masterproduct:id,name,code')->with('masterproductstep:id,name')->with('masterProductOrigin:id,name,code')
                ->with('coApplicant:quote_id,co_applicant_id,lead_id')
                ->orderBy('updated_at', 'desc')
                ->get();

            if ($request->action == 'download') {
                foreach ($applicationList as $key => $item) {
                    // Check if masterproduct is loaded and not null
                    if ($item->masterproduct) {
                        $applicationList[$key]['master_product_id'] =  $item->masterproduct[0]->name;
                    } else {
                        $applicationList[$key]['master_product_id'] = null;
                    }
                    // Check if masterproductOrgin is loaded and not null      
                    if ($item->masterProductOrigin) {
                        $applicationList[$key]['master_origin_product_id'] =  $item->masterProductOrigin[0]->name;
                    } else {
                        $applicationList[$key]['master_origin_product_id'] = null;
                    }
                    // Check if masterproductstep is loaded and not null
                    if ($item->masterproductstep) {
                        $applicationList[$key]['master_product_step_id'] =  $item->masterproductstep[0]->name;
                    } else {
                        $applicationList[$key]['master_product_step_id'] = null;
                    }
                    // Check if lead is loaded and not null
                    if ($item->lead) {
                        $applicationList[$key]['lead_id'] =  $item->lead->mobile_number;
                    } else {
                        $applicationList[$key]['lead_id'] = null;
                    }
                    unset($applicationList[$key]['masterproduct']);
                    unset($applicationList[$key]['masterproductstep']);
                    unset($applicationList[$key]['lead']);
                    unset($applicationList[$key]['coApplicant']);
                    unset($applicationList[$key]['masterProductOrigin']);
                }
            }

            $applicationData['totalLength'] = $totalLength;
            $applicationData['dataList'] = $applicationList;

            return $applicationData;
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository list " . $throwable->__toString());
        }
    }
    /* * fetch mobile number
     *
     */
    public function getAppDataByQuoteId($quoteId)
    {
        try {
            return HjApplication::with('masterproductstep', 'masterProductData')->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getMobileNumberByQuoteId " . $throwable->__toString());
        }
    }

    /* * fetch cc quote data
     *
     */
    public function getDataByCCQuoteId($quoteId)
    {
        try {
            return HjApplication::where('cc_quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getDataByCCQuoteId " . $throwable->__toString());
        }
    }
    /* * fetch quoteId Based on Mobilenumber
     *
     */
    public function getAllProducts($appData, $action)
    {
        try {
            if ($action == 'inactive-application') {
                $applications =   HjApplication::where('mobile_number', $appData['mobile_number'])
                    ->where('master_origin_product_id', $appData['master_origin_product_id'])
                    ->where('is_paid', 0)
                    ->where('is_purchased', 0)
                    ->where(function ($query) {
                        $query->where('updated_at', '<', Carbon::now()->subDays(30))
                            ->orWhere('disposition_status', 'Not Interested');
                    })
                    ->orderBy('id', 'DESC')->get();
                if ($applications->isNotEmpty()) {
                    HjApplication::where('mobile_number', $appData['mobile_number'])
                        ->where('master_origin_product_id', $appData['master_origin_product_id'])
                        ->where('is_paid', 0)
                        ->where('is_purchased', 0)
                        ->where(function ($query) {
                            $query->where('updated_at', '<', Carbon::now()->subDays(30))
                                ->orWhere('disposition_status', 'Not Interested');
                        })
                        ->delete();
                }
            } elseif ($action == 'completed-application') {
                return HjApplication::where('mobile_number', $appData['mobile_number'])
                    ->where('master_origin_product_id', $appData['master_origin_product_id'])
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where('is_purchased', 1)
                                ->where('is_paid', 1);
                        })->orWhere(function ($q) {
                            $q->where('disposition_status', 'Interested')
                                ->where('disposition_sub_status', 'Forward to Branch/Field - Home Pickup');
                        });
                    })
                    ->orderBy('id', 'DESC')->count();
            } else {
                return  HjApplication::select('master_origin_product_id', 'quote_id', 'master_product_step_id', 'name', 'loan_amount', 'auth_token', 'is_paid', 'is_purchased', 'created_at')->with(MASTER_PRODUCT_STEP_FIELDS, 'masterProductOrigin')
                    ->where('mobile_number', $appData['mobile_number'])
                    ->where('master_origin_product_id', $appData['master_origin_product_id'])
                    ->orderBy('id', 'DESC')->get();
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getAllProducts " . $throwable->__toString());
        }
    }
    /* * get products based on mobile number
     *
     */
    public function getProductBasedMobile($appData, $action, $pageType)
    {
        try {
            if ($action == 'inactive-application') {
                $applications =   HjApplication::where('mobile_number', $appData['mobile_number'])
                    ->where(function ($query) {
                        $query->where('updated_at', '<', Carbon::now()->subDays(30))
                            ->orWhere('disposition_status', 'Not Interested');
                    })
                    ->orderBy('id', 'DESC')->get();
                if ($applications->isNotEmpty()) {
                    HjApplication::where('mobile_number', $appData['mobile_number'])
                        ->where(function ($query) {
                            $query->where('updated_at', '<', Carbon::now()->subDays(30))
                                ->orWhere('disposition_status', 'Not Interested');
                        })
                        ->delete();
                }
            } elseif ($action == 'completed-application') {
                $queryData = HjApplication::where('mobile_number', $appData['mobile_number']);
                if ($pageType == 'track_application_id') {
                    $queryData->where('quote_id', $appData['quote_id']);
                }
                $queryData->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('is_purchased', 1)
                            ->where('is_paid', 1);
                    })->orWhere(function ($q) {
                        $q->where('disposition_status', 'Interested')
                            ->where('disposition_sub_status', 'Forward to Branch/Field - Home Pickup');
                    });
                })->orderBy('id', 'DESC');
                return $queryData->count();
            } else {
                $queryData = HjApplication::select('master_origin_product_id', 'quote_id', 'master_product_step_id', 'name', 'loan_amount', 'auth_token', 'is_paid', 'is_purchased', 'created_at')->with(MASTER_PRODUCT_STEP_FIELDS, 'masterProductOrigin')
                    ->where('mobile_number', $appData['mobile_number']);
                if ($pageType == 'track_application_id') {
                    $queryData->where('quote_id', $appData['quote_id']);
                }
                $queryData->orderBy('id', 'DESC');
                return  $queryData->get();
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getProductBasedMobile " . $throwable->__toString());
        }
    }

    /* * check application exist based on mobile number
     *
     */
    public function checkApplicationExisting($request)
    {
        try {
            $dateFrom = Carbon::now()->subDays(30);
            $dateTo = Carbon::now();
            $query = HjApplication::query();
            $query->where('mobile_number', $request->mobile_number);
            $query->whereBetween(
                'updated_at',
                [$dateFrom, $dateTo]
            );
            $query->where('is_purchased', 0);
            if (empty($request->custom_string) === false) {
                $query->where('quote_id', $request->custom_string);
            }
            return $query->orderBy('id', 'DESC')->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository checkApplicationExisting " . $throwable->__toString());
        }
    }

    /* * check application Based on product id and mobile number
     *
     */
    public function checkProductExisting($productId, $mobileNumber)
    {
        try {

            $dateFrom = Carbon::now()->subDays(30);
            $dateTo = Carbon::now();
            $existDataCount =  HjApplication::whereBetween(
                'updated_at',
                [$dateFrom, $dateTo]
            )->where('mobile_number', $mobileNumber)
                ->where('master_origin_product_id', $productId)->count();
            if ($existDataCount > 0) {
                return  HjApplication::with('masterProductOrigin')
                    ->where('mobile_number', $mobileNumber)
                    ->where('master_origin_product_id', $productId)
                    ->orderBy('id', 'DESC')
                    ->first();
            }
            return null;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository checkProductExisting " . $throwable->__toString());
        }
    }

    /* * get productid from quoteId
     *
     */
    public function getMasterProductId($quoteId)
    {
        try {
            return HjApplication::where('quote_id', $quoteId)->value('master_product_id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getMasterProductId " . $throwable->__toString());
        }
    }
    /**
     * get product type.
     *
     */
    public function getProductName($request)
    {
        try {
            return HjApplication::with('productName')->where('quote_id', $request['quote_id'])
                ->where('lead_id', $request['lead_id'])->first();
        } catch (Throwable |  HttpClientException $throwable) {
            Log::info("ImpressionRepository getProductName " . $throwable->__toString());
        }
    }

    /**
     * Get the applications details.
     *
     * @param $quoteId
     */
    public function getAppData($quoteId)
    {
        try {
            return HjApplication::select(
                'master_product_id',
                'master_product_step_id',
                'master_origin_product_id',
                'loan_amount',
                'bre1_loan_amount',
                'bre1_updated_loan_amount',
                'bre2_loan_amount',
                'is_purchased',
                'is_paid',
                'is_bre_execute'
            )->with(
                'originMasterProductData:id,name,handle,code,display_name',
                MASTER_PRODUCT_STEP_FIELDS
            )->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getAppData " . $throwable->__toString());
        }
    }
    /**
     * Get the eligibility applications details.
     *
     * @param $quoteId
     */
    public function getEligibilityAppData($quoteId)
    {
        try {
            return HjApplication::select(
                'master_product_id',
                'master_product_step_id',
                'loan_amount',
                'bre1_loan_amount',
                'bre1_updated_loan_amount',
                'bre2_loan_amount',
                'is_purchased',
                'is_paid',
                'is_bre_execute'
            )->with(
                MASTER_PRODUCT_STEP_FIELDS
            )->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository getEligibilityAppData " . $throwable->__toString());
        }
    }

    /**
     * remove existing application
     *
     */
    public function removeExistApplication($quoteId)
    {
        try {
            HjApplication::where('quote_id', $quoteId)->delete();
        } catch (Throwable |  HttpClientException $throwable) {
            Log::info("ApplicationRepository removeExistApplication " . $throwable->__toString());
        }
    }

    /**
     * get inactive applications
     *
     */
    public function getInactiveApplications()
    {
        try {

            $dateFrom = Carbon::now()->subDays(30);
            $dateTo = Carbon::now();
            return HjApplication::whereNotBetween(
                'updated_at',
                [$dateFrom, $dateTo]
            )->where('is_paid', 0)->where('is_purchased', 0)->get();
        } catch (Throwable |  HttpClientException $throwable) {
            Log::info("ApplicationRepository getInactiveApplications " . $throwable->__toString());
        }
    }

    /**
     * Get property details
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
     * Get property details
     *
     */
    public function getProductDetails($masterProductId)
    {
        try {
            return HjMappingProductType::with('productType:id,handle,name')->with('productDetails:id,handle,name,display_name,code')->where('master_product_id', $masterProductId)->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ImpressionRepository getProductDetails " . $throwable->__toString());
        }
    }
    /**
     * get cc stage id using handle
     * @param $handle
     */
    public function getCCStage($handle)
    {
        try {
            return HjMasterCcStage::where('handle', $handle)->value('stage_id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository  getCCstage " . $throwable->__toString());
        }
    }
    /**
     * get cc stage id using handle
     * @param $handle
     */
    public function getCCSubStage($handle)
    {
        try {
            return HjMasterCcSubStage::select('id', 'priority', 'block_for_calling')->where('handle', $handle)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository  getCCSubStage " . $throwable->__toString());
        }
    }
    /**
     * get cc stage id using product step id
     * @param $handle
     */
    public function getCCStageId($masterProductStepId)
    {
        try {
            return HjMappingProductStepCcStage::where('master_product_step_id', $masterProductStepId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository  getCCStageId " . $throwable->__toString());
        }
    }
    /**
     * get cc sub stage id using cc stage
     * @param $handle
     */
    public function getCCSubStageId($ccStageId)
    {
        try {
            return HjMappingCcStage::with(['ccStage', 'ccSubStage'])->where('master_cc_stage_id', $ccStageId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("ApplicationRepository  getCCSubStageId " . $throwable->__toString());
        }
    }
    public function getMobileNumberByCCQuoteId($ccquoteId)
    {
        try {
            return HjApplication::where('cc_quote_id', $ccquoteId)->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository getMobileNumberByCCQuoteId " . $throwable->__toString());
            return null;
        }
    }
    /**
     * get all five minutes inactive records
     */
    public function getAllInActiveRecords()
    {
        try {
            $fiveMinutesAgo = Carbon::now()->subMinutes(5)->timestamp;
            $tenMinutesAgo = Carbon::now()->subMinutes(10)->timestamp;
            return  FieldTrackingLog::where('created_timestamp', '<=', Carbon::now()->timestamp)
                ->whereBetween('created_timestamp', [$tenMinutesAgo, $fiveMinutesAgo])->whereNotNull('quote_id')
                ->where('cc_push_status', 0)
                ->groupBy('quote_id')->pluck('quote_id');
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository getFiveMinutesInActiveRecords " . $throwable->__toString());
        }
    }
    /**
     * get five minutes inactive records
     */
    public function getInActiveTrackingRecords($quoteId)
    {
        try {
            $fiveMinutesAgo = Carbon::now()->subMinutes(5)->timestamp;
            $currentTimeStamp = Carbon::now()->timestamp;
            return  FieldTrackingLog::where('created_timestamp', '<=', Carbon::now()->timestamp)
                ->whereBetween(
                    'created_timestamp',
                    [$fiveMinutesAgo, $currentTimeStamp]
                )->where('quote_id', $quoteId)->count();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository getInActiveTrackingRecords " . $throwable->__toString());
        }
    }
    /**
     * save into sms log
     */
    public function saveSmsLog($logData)
    {
        try {
            return SmsLog::create($logData);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository saveSmsLog " . $throwable->__toString());
        }
    }
    /**
     * get sms log data
     */
    public function getsmsData($apData)
    {
        try {
            return SmsLog::where('mobile_number', $apData['mobile_number'])->where('master_product_id', $apData['master_product_id'])->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository getsmsData " . $throwable->__toString());
        }
    }
    /**
     * get quoteid based on mobile and product id
     */
    public function getQuoteByMobileProduct($mobileNumber, $masterProductId)
    {
        try {
            return HjApplication::where('mobile_number', $mobileNumber)->where('master_origin_product_id', $masterProductId)->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ApplicationRepository getQuoteByMobileProduct " . $throwable->__toString());
        }
    }
}
