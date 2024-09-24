<?php

namespace App\Repositories;

use App\Entities\MongoLog\LoginAttemptFailed;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoginAttemptFailedRepository
{
    public function checkLoginAttemptFailed($request)
    {
        try {
            $getRecord = LoginAttemptFailed::where('user_name', $request->user_name)->where('created_at', '>=', carbon::now()->subMinutes(1))->first();
            if ($getRecord) {
                $getRecord->count += 1;
                $getRecord->updated_at = Carbon::now();
                $getRecord->save();
            }
            return $getRecord;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("checkLoginAttemptFailed " . $throwable->__toString());
        }
    }
    public function insertLoginAttemptFailed($request)
    {
        try {
            LoginAttemptFailed::create($request);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("insertLoginAttemptFailed " . $throwable->__toString());
        }
    }
    public function getLoginAttemptFailed($request)
    {
        try {
            return LoginAttemptFailed::where('updated_at', '>=',  carbon::now()->subMinutes(30))
                ->where('user_name', $request->user_name)
                ->where('count', '>=', 5)
                ->pluck('updated_at');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("getLoginAttemptFailed " . $throwable->__toString());
        }
    }
}
