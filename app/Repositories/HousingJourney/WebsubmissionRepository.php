<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\WebSubmission;

class WebSubmissionRepository
{
    public function save($request)
    {
        try {
            if (empty($request['otp'])) {
                $email = '';
                if (isset($request['email'])) {
                    $email = $request['email'];
                } elseif (isset($request['emailId'])) {
                    $email = $request['emailId'];
                }
                return WebSubmission::create([
                    'name' => $request['name'],
                    'mobile_number' => $request['mobile_number'],
                    'email' => $email,
                    'pincode_id' => $request['pincode_id'],
                    'master_product_id' => $request['master_product_id'],
                    'loan_amount' => $request['loan_amount'],
                    'utm_source' => $request['utm_source'],
                    'utm_medium' => $request['utm_medium'],
                    'utm_campaign' => $request['utm_campaign'],
                    'utm_term' => $request['utm_term'],
                    'utm_params' => json_encode($request['utm_params']),
                    'utm_content' => $request['utm_content'],
                    'source_page' => $request['source_page'],
                    'is_assisted' => $request['is_assisted'],
                    'partner_code' => $request['partner_code'],
                ], $request);
            } else {
                $record = WebSubmission::where('mobile_number', $request['mobile_number'])
                    ->where('source_page', $request['source_page'])
                    ->orderBy('id', 'desc')
                    ->first();
                return $record->update(['is_verified' => 1]);
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("WebSubmissionRepository save " . $throwable->__toString());
        }
    }
}
