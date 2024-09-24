<?php

namespace App\Services\Payment;

use App\Services\Service;
use Exception;
use Illuminate\Http\Request;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\PincodeRepository;
use App\Repositories\HousingJourney\MasterProductRepository;
use App\Repositories\HousingJourney\PaymentTransactionRepository;
use App\Repositories\HousingJourney\PaymentLogRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Entities\HousingJourney\HjMasterProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use Throwable;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Utils\JourneyTrait;
use App\Utils\CommonTrait;

class PaymentService extends Service
{

    /**
     * payment response handling.
     * @param  Request $request
     * @return mixed
     */

    use JourneyTrait;
    use CrmTrait;
    use CommonTrait;

    /**
     * Payment Inititate
     * @param  Request $request
     * @return mixed
     */
    public function paymentInititate(Request $request)
    {
        try {
            $appRepo = new ApplicationRepository();
            $impRepo = new ImpressionRepository();
            $paymentRepo = new PaymentTransactionRepository();
            $paymentLogRepo = new PaymentLogRepository();
            $eligRepo = new EligibilityRepository();
            $applicationData = $appRepo->getApplicationDetails($request['quote_id']);
            $paymentTransactionId = $this->generateRandomString("paymentTransactionId");
            $digitalTransactionID = $this->generateRandomString('digitalTransactionID');
            //save BRE 2 Loan amount on application table
            if (isset($request->bre2_loan_amount)) {
                $reqData['quote_id'] = $request['quote_id'];
                $reqData['lead_id'] = $request['lead_id'];
                $reqData['loan_amount'] = $request['loan_amount'];
                $reqData['tenure'] = $request['tenure'];
                $reqData['type'] = 'BRE2';
                $eligRepo->save($reqData);
                $requestData['bre2_loan_amount'] = $request->bre2_loan_amount;
                $requestData['offer_amount'] = $request->loan_amount;
                $stageData['lead_id'] = $requestData['lead_id'] = $request->lead_id;
                $stageData['quote_id'] = $requestData['quote_id'] = $request->quote_id;
                $appRepo->save($requestData);
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.paymentL1'));
                if ($currenStepId) {
                    $stageData['master_product_id'] =  $applicationData['master_product_id'];
                    $stageData['master_product_step_id'] = $currenStepId;
                    $impRepo->save($stageData);
                    $appRepo->save($stageData);
                }
            } else {
                $currenStepId = $impRepo->getCurrentStepId(config('constants/productStepHandle.paymentL2'));
                $stageData['lead_id'] = $applicationData['lead_id'];
                $stageData['quote_id'] = $request->quote_id;
                if ($currenStepId) {
                    $stageData['master_product_id'] =  $applicationData['master_product_id'];
                    $stageData['master_product_step_id'] = $currenStepId;
                    $impRepo->save($stageData);
                    $appRepo->save($stageData);
                }
            }
            $simpleTransactionData = $this->simpleTransactionDataPreparation($applicationData, $paymentTransactionId);
            $pt['payment_transaction_id'] = $logData['payment_transaction_id'] = $paymentTransactionId;
            $pt['lead_id'] = $logData['lead_id'] = $applicationData['lead_id'];
            $pt['quote_id'] = $logData['quote_id'] = $request['quote_id'];
            $logData['mobile_number'] = $applicationData['mobile_number'];
            $logData['master_product_id'] = $applicationData['master_product_id'];
            $logData['digital_transaction_no'] = $applicationData['digital_transaction_no'];
            $pt['digital_transaction_no'] = $applicationData['digital_transaction_no'];
            $pt['amount'] = $simpleTransactionData['amount'];
            $pt['mode'] = "";
            $pt['customer_var'] = $simpleTransactionData['customvar'];
            $pt['status'] = config('constants/paymentStatus.INIT');
            $pt['request'] = json_encode($simpleTransactionData);
            $pt['payment_gateway_id'] = "1";
            $logData['api_source'] = $request->header('X-Api-Source');
            $logData['api_source_page'] = $request->header('X-Api-Source-Page');
            $logData['api_type'] =  config('constants/apiType.PAYMENT_INIT');
            $logData['api_header'] =  $request->header('X-Api-Type');
            $logData['api_url'] =  env('PAYTM_URL');
            $logData['api_request_type'] =  "request";
            $logData['api_data'] = json_encode($pt);
            $paymentLogRepo->save($logData);

            $paymentData = $paymentRepo->upsertPaymentData($pt);

            $logPushData = $request;
            $logPushData['cc_stage_handle'] = 'payment';
            $logPushData['cc_sub_stage_handle'] = 'payment-attempted';
            $this->pushDataFieldTrackingLog($logPushData);
            $logPushData['cc_sub_stage_handle'] = 'payment-pending';
            $this->pushDataFieldTrackingLog($logPushData);

            if ($paymentData) {
                return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $simpleTransactionData);
            } else {
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
            }
        } catch (Exception  $throwable) {
            Log::info("Payment Service -  paymentInititate " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }

    /**
     * payment response handling.
     * @param  Request $request
     * @return mixed
     */
    public function responseHandle($request)
    {
        try {
            if (isset($request) && !empty($request)) {
                if ($request['response']['success']) {
                    return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $request);
                } else {
                    return $this->responseJson(
                        config('journey/http-status.failure.status'),
                        config('journey/http-status.failure.message'),
                        config('journey/http-status.failure.code'),
                        $request
                    );
                }
            } else {
                return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
                );
            }
        } catch (Exception  $throwable) {
            Log::info("Payment Service -  responseHandle " . $throwable);
        }
    }

    /**
     * payment redirect
     * @param  Request $request
     * @return mixed
     */

    public function paymentRedirect(Request $request)
    {
        try {
            $res = $request->all();
            $appRepo = new ApplicationRepository();
            $paymentRepo = new PaymentTransactionRepository();
            $paymentLogRepo = new PaymentLogRepository();
            $impressionRepo = new ImpressionRepository();
            $productRepo = new MasterProductRepository();
            $sanctionRoute = "sanction-letter/";
            $offerRoute = "offer-details/";
            $downloadRoute = "document-upload/";
            if (!isset($res['CUSTOMVAR'])) {
                $this->savePaymentLogData($res);
            } else {
                $cutomeArray = explode('-', $res['CUSTOMVAR']);
                $digitalTransactionID = $cutomeArray[0];
                $paymentTransactionID = $cutomeArray[1];
                $app['quote_id'] = $quoteID = $cutomeArray[2];
                $applicationData = $appRepo->getQuoteIdDetails($app);
                // get payment details by invoice id
                $logData['api_source'] = config('constants/apiSource.WEB');
                $logData['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                $logData['api_header'] = "";
                $logData['api_url'] =  env('PAYTM_URL');
                $logData['api_request_type'] =  "response";
                $logData['payment_transaction_id'] = $paymentTransactionID;
                $logData['lead_id'] = $applicationData['lead_id'];
                $logData['quote_id'] = $quoteID;
                $logData['mobile_number'] = $applicationData['mobile_number'];
                $logData['master_product_id'] = $applicationData['master_product_id'];
                $requestAmount = (int)$productRepo->getProcessingFee($applicationData['master_product_id']);
                $paidAmount =  (int)$res['AMOUNT'];

                $masterProductId = $applicationData['master_product_id'];
                $masterProduct = HjMasterProduct::find($masterProductId);
                $code = $masterProduct['code'];
                $url = $this->getProductNameUrl($code);
                $userEmail = $appRepo->getEmailID($applicationData['quote_id']);
                $isEmailRequired = false;
                if (empty($userEmail)) {
                    $isEmailRequired = false;
                }
                $payLoad['api_type'] = $request['api_type'];
                $payLoad['api_source'] = $request['api_source_page'];
                $payLoad['type'] =  $request['api_source'];
                $payLoad['api_data'] =  $request;
                $payLoad['url'] =  $url;
                // Amount mismatch condition handled
                if (round($requestAmount) != round($paidAmount)) {
                    $logData['api_status_message'] = $request->filled('MESSAGE') ? $res['MESSAGE'] : "";
                    $logData['api_data'] = json_encode($res);
                    $paymentLogRepo->save($logData);
                    if ($applicationData['master_product_step_id'] > 12) {
                        $redirectPage = $sanctionRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.sanction-letter'));
                        $stageData['lead_id'] =  $applicationData['lead_id'];
                        $stageData['quote_id'] =  $applicationData['quote_id'];
                        $stageData['master_product_id'] =  $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $currenStepId;
                        $this->stageUpdate($applicationData, $stageData);
                    } else {
                        $redirectPage = $offerRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.offer-details'));
                        $stageData['lead_id'] =  $applicationData['lead_id'];
                        $stageData['quote_id'] =  $applicationData['quote_id'];
                        $stageData['master_product_id'] =  $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $currenStepId;
                        $this->stageUpdate($applicationData, $stageData);
                    }

                    $redirectURL = env('PAYMENT_REDIRECT_URL') . $redirectPage;
                    $publicPath = env('WEBSITE_URL');
                    $successImg = env('WEBSITE_URL') . 'assets/housing-journey/images/success.gif';
                    $failureImg = env('WEBSITE_URL') . 'assets/housing-journey/images/fail.gif';
                    $websiteURL = env('WEBSITE_URL');
                    // create view and redirect
                    return View::make('payment/paymentRedirect', ['redirectURL' => $redirectURL,  'publicPath' => $publicPath, 'successImg' => $successImg, 'failureImg' => $failureImg, 'transactionCode' => "422", 'websiteURL' => $websiteURL]);
                }

                if ($res['TRANSACTIONSTATUS'] == 200 || $res['MESSAGE'] == "Success") {
                    $request['quote_id'] = $app['quote_id'];
                    $request['api_source'] =  config('constants/apiSource.HOUSING_JOURNEY');
                    $request['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                    $request['api_type'] = config('constants/apiType.PAYMENT_CALL_BACK');
                    $logPushData = $request;
                    $logPushData['cc_stage_handle'] = 'payment';
                    $logPushData['cc_sub_stage_handle'] = 'payment-success';
                    $this->pushDataFieldTrackingLog($logPushData);

                    $logData['api_status_message'] = $request->filled('MESSAGE') ? $res['MESSAGE'] : "";
                    $logData['api_status_code'] = $request->filled('TRANSACTIONSTATUS') ? $res['TRANSACTIONSTATUS'] : "";
                    $logData['api_type'] = config('constants/apiType.PAYMENT_SUCCESS');
                    if ($applicationData['master_product_step_id'] > 12) {
                        $redirectPage = $sanctionRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.sanction-letter'));
                        $stageData['master_product_step_id'] = $currenStepId;
                    } else {
                        $redirectPage = $downloadRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.document-upload'));
                        $logPushData = $request;
                        $logPushData['cc_stage_handle'] = 'document-upload';
                        $logPushData['cc_sub_stage_handle'] = 'document-upload-pending';
                        $this->pushDataFieldTrackingLog($logPushData);
                        $stageData['master_product_step_id'] = $currenStepId;
                    }

                    $paymentTransactionData = array(
                        "lead_id" => $applicationData['lead_id'] ?? "",
                        "digital_transaction_no" => $applicationData['digital_transaction_no'] ?? "",
                        "payment_transaction_id" => $paymentTransactionID ?? "",
                        "customer_var" => $request->filled('CUSTOMVAR') ? $res['CUSTOMVAR'] : "",
                        "mode" => $request->filled('CHMOD') ? $res['CHMOD'] : "",
                        "neft_bank_name" => $request->filled('BANKNAME') ? $res['BANKNAME'] : "",
                        "risk" => $request->filled('RISK') ? $res['RISK'] : "",
                        "transaction_type" => $request->filled('TRANSACTIONTYPE') ? $res['TRANSACTIONTYPE'] : "",
                        "status" => $request->filled('TRANSACTIONPAYMENTSTATUS') ? $res['TRANSACTIONPAYMENTSTATUS'] : "",
                        "transaction_time" => $request->filled('TRANSACTIONTIME') ? $res['TRANSACTIONTIME'] : "",
                        "merchant_id" => $request->filled('MERCID') ? $res['MERCID'] : "",
                        "amount" => $request->filled('AMOUNT') ? $res['AMOUNT'] : "",
                        "billed_amount$paymentLogRepo->save($logData);
            // // $paymentData = $paymentRepo->upsertPaymentData($pt);
            // // if ($paymentData) {
                
            //     return $this->responseJson(config('http-status.success.status'), config('http-status.success.message'), config('http-status.success.code'), $simpleTransactionData);
            // // } else {
            // //     return $this->responseJson(
            // //         config('http-status.failure.status'),
            // //         config('http-status.failure.message'),
            // //         config('http-status.failure.code'),
            // //         []
            // //     );
            // // }" => $request->filled('BILLEDAMOUNT') ? $res['BILLEDAMOUNT'] : "",
                        "currency_code" => $request->filled('CURRENCYCODE') ? $res['CURRENCYCODE'] : "",
                        "gateway_transaction_id" => $request->filled('TRANSACTIONID') ? $res['TRANSACTIONID'] : "",
                        "retrieval_reference_number" => $request->filled('APTRANSACTIONID') ? $res['APTRANSACTIONID'] : "",
                        "transaction_mode" => $request->filled('TXN_MODE') ? $res['TXN_MODE'] : "",
                        "gateway_status_code" => $request->filled('TRANSACTIONSTATUS') ? $res['TRANSACTIONSTATUS'] : "",
                        "gateway_msg" => $request->filled('MESSAGE') ? $res['MESSAGE'] : "",
                        "response" => json_encode($res),
                        "reason" => $request->filled('REASON') ? $res['REASON'] : "",
                        "merchant_name" => $request->filled('MERCHANT_NAME') ? $res['MERCHANT_NAME'] : ""
                    );

                    $appData['lead_id'] = $stageData['lead_id'] =  $applicationData['lead_id'];
                    $appData['quote_id'] =  $stageData['quote_id'] = $quoteID;
                    $stageData['master_product_id'] =  $applicationData['master_product_id'];
                    $stageData['payment_transaction_id'] =  $paymentTransactionID;
                    $stageData['is_paid'] =  "1";
                    $this->stageUpdate($applicationData, $stageData);
                    // SMS Send
                    $payLoad['sms_template_handle'] = 'payment-success';
                    $payLoad['user_name'] =  config('journey/sms.cc_username');
                    $payLoad['password'] =  config('journey/sms.cc_password');
                    $payLoad['app_data'] =  $applicationData;
                    $payLoad['mobile_number'] =  $applicationData['mobile_number'];
                    $payLoad['is_short_url_required'] = true;
                    $payLoad['is_email_required'] = $isEmailRequired;
                    $payLoad['email'] = $userEmail;
                    $payLoad['payment_amount'] = $request->filled('AMOUNT') ? $res['AMOUNT'] : "";
                    $payLoad['payment_refence'] = $applicationData['digital_transaction_no'];
                    $payLoad['email_template_handle'] =  config('constants/smsAndEmailTemplateCode.PAYMENT_SUCCESS');
                    // $apiResponse = $this->sendEmailWithSMS($payLoad);

                } elseif ($res['TRANSACTIONSTATUS'] == 400 || $res['MESSAGE'] == "Failed") {
                    $request['quote_id'] = $app['quote_id'];
                    $request['api_source'] =  config('constants/apiSource.HOUSING_JOURNEY');
                    $request['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                    $request['api_type'] = config('constants/apiType.PAYMENT_CALL_BACK');
                    $logPushData = $request;
                    $logPushData['cc_stage_handle'] = 'payment';
                    $logPushData['cc_sub_stage_handle'] = 'payment-failure';
                    $this->pushDataFieldTrackingLog($logPushData);

                    if ($applicationData['master_product_step_id'] > 12) {
                        $redirectPage = $sanctionRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.sanction-letter'));
                        $stageData['lead_id'] =  $applicationData['lead_id'];
                        $stageData['quote_id'] =  $applicationData['quote_id'];
                        $stageData['master_product_id'] =  $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $currenStepId;
                        $this->stageUpdate($applicationData, $stageData);
                    } else {
                        $redirectPage = $offerRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.offer-details'));
                        $stageData['lead_id'] =  $applicationData['lead_id'];
                        $stageData['quote_id'] =  $applicationData['quote_id'];
                        $stageData['master_product_id'] =  $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $currenStepId;
                        $this->stageUpdate($applicationData, $stageData);
                    }
                    $logData['api_type'] =  config('constants/apiType.PAYMENT_FAILURE');
                    $logData['api_status_message'] = $request->filled('MESSAGE') ? $res['MESSAGE'] : "";
                    $logData['api_status_code'] = $request->filled('TRANSACTIONSTATUS') ? $res['TRANSACTIONSTATUS'] : "";
                    $paymentStatus = "FAIL";
                    if (isset($res['TRANSACTIONPAYMENTSTATUS'])) {
                        $paymentStatus = $res['TRANSACTIONPAYMENTSTATUS'];
                    }
                    $paymentTransactionData = array(
                        "lead_id" => $applicationData['lead_id'] ?? "",
                        "digital_transaction_no" => $applicationData['digital_transaction_no'] ?? "",
                        "payment_transaction_id" => $paymentTransactionID ?? "",
                        "customer_var" => $request->filled('CUSTOMVAR') ? $res['CUSTOMVAR'] : "",
                        "amount" => $request->filled('AMOUNT') ? $res['AMOUNT'] : "",
                        "currency_code" => $request->filled('CURRENCY') ? $res['CURRENCY'] : "",
                        "gateway_transaction_id" => $request->filled('TRANSACTIONID') ? $res['TRANSACTIONID'] : "",
                        "status" => $paymentStatus,
                        "retrieval_reference_number" => $request->filled('APTRANSACTIONID') ? $res['APTRANSACTIONID'] : "",
                        "gateway_status_code" => $request->filled('TRANSACTIONSTATUS') ? $res['TRANSACTIONSTATUS'] : "",
                        "gateway_msg" => $request->filled('MESSAGE') ? $res['MESSAGE'] : "",
                        "transaction_mode" => $request->filled('TXN_MODE') ? $res['TXN_MODE'] : "",
                        "mode" => $request->filled('CHMOD') ? $res['CHMOD'] : "",
                        "currency_code" => $request->filled('CURRENCYCODE') ? $res['CURRENCYCODE'] : "",
                        "risk" => $request->filled('RISK') ? $res['RISK'] : "",
                        "transaction_time" => $request->filled('TRANSACTIONTIME') ? $res['TRANSACTIONTIME'] : "",
                        "billed_amount" => $request->filled('BILLEDAMOUNT') ? $res['BILLEDAMOUNT'] : "",
                        "neft_bank_name" => $request->filled('BANKNAME') ? $res['BANKNAME'] : "",
                        "reason" => $request->filled('REASON') ? $res['REASON'] : "",
                        "response" => json_encode($res),
                    );
                    // SMS Send
                    $payLoad['sms_template_handle'] = config('constants/productStepHandle.paymentL1');
                    $payLoad['user_name'] =  config('journey/sms.cc_username');
                    $payLoad['password'] =  config('journey/sms.cc_password');
                    $payLoad['app_data'] =  $applicationData;
                    $payLoad['mobile_number'] =  $applicationData['mobile_number'];
                    $payLoad['is_short_url_required'] = true;
                    $payLoad['is_email_required'] = $isEmailRequired;
                    $payLoad['email'] = $userEmail;
                    $payLoad['email_template_handle'] =  config('constants/smsAndEmailTemplateCode.PAYMENT_SUCCESS');
                    //   $apiResponse = $this->sendEmailWithSMS($payLoad);

                } elseif ($res['TRANSACTIONSTATUS'] == 502  || $res['TRANSACTIONSTATUS'] == 402  || $res['TRANSACTIONSTATUS'] == 401  ||  $res['TRANSACTIONSTATUS'] == 403  || $res['TRANSACTIONSTATUS'] == 405  ||  $res['TRANSACTIONSTATUS'] == 503) {

                    $request['quote_id'] = $app['quote_id'];
                    $request['api_source'] =  config('constants/apiSource.HOUSING_JOURNEY');
                    $request['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                    $request['api_type'] = config('constants/apiType.PAYMENT_CALL_BACK');
                    $logPushData = $request;
                    $logPushData['cc_stage_handle'] = 'payment';
                    $logPushData['cc_sub_stage_handle'] = 'payment-failure';
                    $this->pushDataFieldTrackingLog($logPushData);

                    // Handle : Cancel - 502, No Records - 503, Bounced - 405, Incomplete - 403, Cancel - 402, Dropped - 401
                    if ($applicationData['master_product_step_id'] > 12) {
                        $redirectPage = $sanctionRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.sanction-letter'));
                        $stageData['lead_id'] =  $applicationData['lead_id'];
                        $stageData['quote_id'] =  $applicationData['quote_id'];
                        $stageData['master_product_id'] =  $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $currenStepId;
                        $this->stageUpdate($applicationData, $stageData);
                    } else {
                        $redirectPage = $offerRoute;
                        $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.offer-details'));
                        $stageData['lead_id'] =  $applicationData['lead_id'];
                        $stageData['quote_id'] =  $applicationData['quote_id'];
                        $stageData['master_product_id'] =  $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $currenStepId;
                        $this->stageUpdate($applicationData, $stageData);
                    }
                    $logData['api_type'] =  config('constants/apiType.PAYMENT_FAILURE');
                    $logData['api_status_message'] = $request->filled('MESSAGE') ? $res['MESSAGE'] : "";
                    $logData['api_status_code'] = $request->filled('TRANSACTIONSTATUS') ? $res['TRANSACTIONSTATUS'] : "";
                    $paymentStatus = "FAIL";
                    if (isset($res['TRANSACTIONPAYMENTSTATUS'])) {
                        $paymentStatus = $res['TRANSACTIONPAYMENTSTATUS'];
                    }
                    $paymentTransactionData = array(
                        "lead_id" => $applicationData['lead_id'] ?? "",
                        "digital_transaction_no" => $applicationData['digital_transaction_no'] ?? "",
                        "payment_transaction_id" => $paymentTransactionID ?? "",
                        "customer_var" => $request->filled('CUSTOMVAR') ? $res['CUSTOMVAR'] : "",
                        "amount" => $request->filled('AMOUNT') ? $res['AMOUNT'] : "",
                        "currency_code" => $request->filled('CURRENCY') ? $res['CURRENCY'] : "",
                        "reason" => $request->filled('REASON') ? $res['REASON'] : "",
                        "gateway_transaction_id" => $request->filled('TRANSACTIONID') ? $res['TRANSACTIONID'] : "",
                        "status" => $paymentStatus,
                        "retrieval_reference_number" => $request->filled('APTRANSACTIONID') ? $res['APTRANSACTIONID'] : "",
                        "gateway_status_code" => $request->filled('TRANSACTIONSTATUS') ? $res['TRANSACTIONSTATUS'] : "",
                        "gateway_msg" => $request->filled('MESSAGE') ? $res['MESSAGE'] : "",
                        "response" => json_encode($res),
                    );
                    // SMS Send
                    $payLoad['sms_template_handle'] = config('constants/productStepHandle.paymentL1');
                    $payLoad['user_name'] =  config('journey/sms.cc_username');
                    $payLoad['password'] =  config('journey/sms.cc_password');
                    $payLoad['app_data'] =  $applicationData;
                    $payLoad['mobile_number'] =  $applicationData['mobile_number'];
                    $payLoad['is_short_url_required'] = true;
                    $payLoad['is_email_required'] = $isEmailRequired;
                    $payLoad['email'] = $userEmail;
                    $payLoad['email_template_handle'] =  config('constants/smsAndEmailTemplateCode.PAYMENT_SUCCESS');
                    //   $apiResponse = $this->sendEmailWithSMS($payLoad);
                }

                // update payment transaction log data
                $logData['api_data'] = json_encode($paymentTransactionData);
                $paymentLogRepo->save($logData);

                // update payment transaction data
                $paymentRepo->upsertPaymentData($paymentTransactionData);
                $redirectURL = env('PAYMENT_REDIRECT_URL') . $redirectPage;

                $publicPath = env('WEBSITE_URL');
                $successImg = env('WEBSITE_URL') . 'assets/housing-journey/images/success.gif';
                $failureImg = env('WEBSITE_URL') . 'assets/housing-journey/images/fail.gif';
                $websiteURL = env('WEBSITE_URL');
                // create view and redirect
                return View::make('payment/paymentRedirectPage', ['redirectURL' => $redirectURL,  'publicPath' => $publicPath, 'successImg' => $successImg, 'failureImg' => $failureImg, 'transactionCode' => $res['TRANSACTIONSTATUS'], 'websiteURL' => $websiteURL]);
            }
        } catch (Exception  $throwable) {
            Log::info("Payment Service -  paymentRedirect " . $throwable);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }

    public function stageUpdate($applicationData, $reqData)
    {
        $impressionRepo = new ImpressionRepository();
        $appRepo = new ApplicationRepository();
        $stageData['quote_id'] = $reqData['quote_id'];
        $stageData['lead_id'] = $reqData['lead_id'];
        $stageData['master_product_id'] = $reqData['master_product_id'];
        $stageData['master_product_step_id'] = $reqData['master_product_step_id'];
        $previousImpID = $impressionRepo->getCurrentImpId($stageData);
        $impData = $impressionRepo->getLastestImp($stageData);
        // Save Impression
        if ($impData['master_product_step_id'] != $reqData['master_product_step_id']) {
            $impData = $impressionRepo->save($stageData);
        }
        $currentImpressionID = $applicationData['current_impression_id'];
        if ($impData) {
            $currentImpressionID = $impData->id;
        }
        $stageData['current_impression_id'] =  $currentImpressionID;
        $stageData['previous_impression_id'] =  ($previousImpID) ? $previousImpID : $applicationData['current_impression_id'];
        // Check payment_transaction_id exist
        if (isset($reqData['payment_transaction_id'])) {
            $stageData['payment_transaction_id'] = $reqData['payment_transaction_id'];
            $stageData['is_paid'] = "1";
        }
        $appRepo->save($stageData);
    }

    // Prepare Airpay payment Transaction Data
    public function simpleTransactionDataPreparation($appData, $paymentTransactionId)
    {
        $productRepo = new MasterProductRepository();
        $smData["chmod"] = env('PAYMENT_CHMOD');
        $smData["buyerEmail"] = config('constants/payment.email');
        $smData["buyerPhone"] = config('constants/payment.mobile_number');
        $smData["buyerFirstName"] = $appData['personalDetail']['full_name'];
        $smData["buyerLastName"] = $appData['personalDetail']['full_name'];
        $smData["buyerAddress"] = config('constants/payment.address');
        $smData["buyerCity"] =  config('constants/payment.city');
        $smData["buyerState"] = config('constants/payment.state');
        $smData["buyerCountry"] = config('constants/payment.country');
        $smData["buyerPinCode"] = config('constants/payment.pincode');
        $smData["orderid"] = $this->generateRandomString("orderId");
        $smData["amount"] =  $productRepo->getProcessingFee($appData['master_product_id']);
        $smData["currency"] = config('constants/payment.currency');
        $smData["isocurrency"] = config('constants/payment.isocurrency');
        $smData["code"] = env('PAYTM_MERCHANT_ID');
        $allData = $smData["buyerEmail"] . $smData["buyerFirstName"] . $smData["buyerLastName"] . $smData["buyerAddress"] . $smData["buyerCity"] . $smData["buyerState"] . $smData["buyerCountry"] . $smData["amount"] . $smData["orderid"] . date("Y-m-d");
        // Private Key
        $udata = env('PAYTM_USERNAME') . ':|:' . env('PAYTM_PASSWORD');
        $privatekey = hash('SHA256', env('PAYTM_SECRETS_KEY') . '@' . $udata);
        //checksum
        $key = hash('SHA256',  env('PAYTM_USERNAME') . '~:~' . env('PAYTM_PASSWORD'));
        $checksum = hash('SHA256', $key . '@' . $allData);
        $smData["privatekey"] = $privatekey;
        $smData["checksum"] = $checksum;
        $smData["customvar"] = $appData['digital_transaction_no'] . '-' . $paymentTransactionId . '-' . $appData['quote_id'];
        return $smData;
    }

    public function savePaymentLogData($res)
    {
        try {
            $paymentLogRepo = new PaymentLogRepository();
            $logData['api_source'] = config('constants/apiSource.WEB');
            $logData['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
            $logData['api_header'] = "";
            $logData['api_url'] =  env('PAYTM_URL');
            $logData['api_request_type'] =  "response";
            $logData['payment_transaction_id'] = "";
            $logData['lead_id'] = "";
            $logData['quote_id'] = "";
            $logData['mobile_number'] = "";
            $logData['master_product_id'] = "";
            $logData['api_type'] =  config('constants/apiType.PAYMENT_FAILURE');
            $logData['api_status_message'] =  "Some fields is missing";
            $logData['api_status_code'] =  "201";
            $logData['api_data'] = json_encode($res);
            $paymentLogRepo->save($logData);
            header('Refresh: 10; URL=' . env('PAYMENT_REDIRECT_URL') . "offer-details/");
        } catch (Exception  $throwable) {
            Log::info("Payment Service -  savePaymentLogData " . $throwable);
        }
    }

    public function getPaymentLogList(Request $request, PaymentLogRepository $paymentLogRepo): mixed
    {
        try {
            $paymentLogData = $paymentLogRepo->getPaymentLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $paymentLogData
            );
        } catch (Throwable   | ClientException $throwable) {
            Log::info("Payment Service -  getPaymentLogList " . $throwable);
        }
    }

    public function exportPaymentLog(Request $request)
    {
        try {
            $rules = [
                "fromDate" => "required",
                "toDate"  => "required",
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $repository = new PaymentLogRepository();
            $data['methodName'] = 'getPaymentLog';
            $data['fileName'] = 'Payment-Log-Report-';
            $data['moduleName'] = 'Payment-Log';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("PaymentLogExport " . $throwable->__toString());
            return $this->responseJson(
                config('crm/http-status.error.status'),
                config('crm/http-status.error.message'),
                config('crm/http-status.error.code'),
                []
            );
        }
    }
}
