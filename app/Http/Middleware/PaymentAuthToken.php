<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\PaymentLogRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\View;
use Exception;

class PaymentAuthToken extends Service
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $appRepo = new ApplicationRepository();
            $impressionRepo = new ImpressionRepository();
            $paymentLogRepo = new PaymentLogRepository();
            $res = $request->all();
            $sanctionRoute = "sanction-letter/";
            $offerRoute = "offer-details/";
            if (isset($res['CUSTOMVAR'])) {
                $customeVar = $res['CUSTOMVAR'];
                $cutomeArray = explode('-', $customeVar);
                $paymentTransactionID = $cutomeArray[1];
                $app['quote_id'] = $cutomeArray[2];
                $applicationData = $appRepo->getQuoteIdDetails($app);
                $redirectPage = $offerRoute;
                $mobile = "";
                $masterProductId = "";
                if ($applicationData && $applicationData != null) {
                    $redisAuthToken = Redis::get($applicationData['lead_id']);
                    if ($redisAuthToken != null && !empty($redisAuthToken)) {
                        //Payment Continue
                        Log::info("Payment Auth Verified");
                        return $next($request);
                    } else {
                        $stageData['quote_id'] = $applicationData['quote_id'];
                        $logData['lead_id'] = $stageData['lead_id'] = $applicationData['lead_id'];
                        $mobile = $applicationData['mobile_number'];
                        $masterProductId = $stageData['master_product_id'] = $applicationData['master_product_id'];
                        $stageData['master_product_step_id'] = $applicationData['master_product_step_id'];
                        if ($applicationData['master_product_step_id'] > 12) {
                            $redirectPage = $sanctionRoute;
                            $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.sanction-letter'));
                            $stageData['master_product_step_id'] = $currenStepId;
                        } else {
                            $redirectPage = $offerRoute;
                            $currenStepId = $impressionRepo->getCurrentStepId(config('constants/productStepHandle.document-upload'));
                            $stageData['master_product_step_id'] = $currenStepId;
                        }

                        $previousImpID = $impressionRepo->getCurrentImpId($stageData);
                        // Save Impression
                        $impData = $impressionRepo->save($stageData);
                        $currentImpressionID = $applicationData['current_impression_id'];
                        if ($impData) {
                            $currentImpressionID = $impData->id;
                        }
                        $stageData['current_impression_id'] =  $currentImpressionID;
                        $stageData['previous_impression_id'] =  ($previousImpID) ? $previousImpID : $applicationData['current_impression_id'];
                        $appRepo->save($stageData);
                    }
                }
                Log::info("Unauthorized - Auth Token mismatch!!!");
                $logData['api_source'] = config('constants/apiSource.WEB');
                $logData['api_source_page'] = config('constants/apiSourcePage.PAYMENT_CALL_BACK');
                $logData['api_header'] = "";
                $logData['api_url'] =  env('PAYTM_URL');
                $logData['api_request_type'] =  "response";
                $logData['payment_transaction_id'] = $paymentTransactionID;
                $logData['quote_id'] = $app['quote_id'];
                $logData['mobile_number'] = $mobile;
                $logData['master_product_id'] = $masterProductId;
                $logData['api_type'] =  config('constants/apiType.PAYMENT_FAILURE');
                $logData['api_status_message'] =  "Unauthorized - Auth Token mismatch";
                $logData['api_status_code'] =  "401";
                $logData['api_data'] = json_encode($res);
                $paymentLogRepo->save($logData);

                $redirectURL = env('PAYMENT_REDIRECT_URL') . $redirectPage;
                $publicPath = env('WEBSITE_URL');
                $successImg = env('WEBSITE_URL') . 'assets/housing-journey/images/success.gif';
                $failureImg = env('WEBSITE_URL') . 'assets/housing-journey/images/fail.gif';
                $websiteURL = env('WEBSITE_URL');
                // create view and redirect
                return View::make('payment/paymentRedirect', ['redirectURL' => $redirectURL,  'publicPath' => $publicPath, 'successImg' => $successImg, 'failureImg' => $failureImg, 'transactionCode' => "401", 'websiteURL' => $websiteURL]);
            } else {
                Log::info("CUSTOMVAR Missing " . json_encode($res));
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
                header('Refresh: 2; URL=' . env('PAYMENT_REDIRECT_URL') . "offer-details/");
            }
        } catch (Exception $throwable) {
            Log::info("PaymentAuthToken " . $throwable);
            return $this->responseJson(
                config('journey/http-status.unauthorized.status'),
                config('journey/http-status.unauthorized.message'),
                config('journey/http-status.unauthorized.code'),
                []
            );
        }
    }
}
