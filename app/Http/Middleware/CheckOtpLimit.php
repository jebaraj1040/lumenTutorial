<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;
use App\Entities\MongoLog\OtpAttempt;
use App\Repositories\HousingJourney\OtpLogRepository;
// Assume OtpAttempt is your MongoDB model for storing OTP attempts

class CheckOtpLimit extends Service
{
    public function handle($request, Closure $next)
    {
        $otpLogRepository = new OtpLogRepository();
        $request['source_page'] = $request->header('X-Api-Source-Page');
        $request['master_product_id'] = $request->product_code;
        // Get the user's last OTP verification status
        $unsuccessfulAttemptsCount = $otpLogRepository->getOtpAttempt($request);
        if (empty($unsuccessfulAttemptsCount) === false && empty($unsuccessfulAttemptsCount->count) === false) {
            if ($unsuccessfulAttemptsCount->count >= 5) {
                $msg = "You've reached maximum attempts. Please try again after sometimes.";
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    $msg,
                    config('journey/http-status.failure.code'),
                    []
                );
            }
        }
        $log['mobile_number'] = $request->mobile_number;
        $log['status'] = 'send';
        $log['count'] = '1';
        $log['api_source_page'] = $request->source_page;
        $log['master_product_id'] = $request->master_product_id;
        $otpAttempts = $otpLogRepository->createOtpAttempts($log);
        if ($otpAttempts) {
            return $next($request);
        }
    }
}
