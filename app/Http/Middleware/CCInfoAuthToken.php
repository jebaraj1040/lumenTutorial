<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class CCInfoAuthToken extends Service
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $knownRequest = false;
            // cc request.
            if ($request->header('X-Auth-Token') && $request->header('X-Api-Source') && $request->header('X-Api-Source-Page') && $request->header('X-Api-Type')) {
                if (in_array($request->header('X-Api-Source'), config('constants/apiSource')) && in_array($request->header('X-Api-Source-Page'), config('constants/apiSourcePage')) && in_array($request->header('X-Api-Type'), config('constants/apiType'))) {
                    $knownRequest = true;
                } else {
                    return $this->responseJson(
                        config('journey/http-status.bad-request.status'),
                        config('journey/http-status.bad-request.message'),
                        config('journey/http-status.bad-request.code'),
                        []
                    );
                }
            } else {
                return $this->responseJson(
                    config('journey/http-status.unauthorized.status'),
                    config('journey/http-status.unauthorized.message'),
                    config('journey/http-status.unauthorized.code'),
                    []
                );
            }

            // Known request progress.
            if ($knownRequest) {
                $csrfToken = $request->header('X-Auth-Token');
                $tokenKey = "SHFL_CC_QUOTE_INFO_333";
                $decryptedTokenKey = Crypt::decrypt($csrfToken);
                if (isset($csrfToken) && $csrfToken !== '' &&  $tokenKey == $decryptedTokenKey) {
                    return $next($request);
                } else {
                    return $this->responseJson(
                        config('journey/http-status.unauthorized.status'),
                        config('journey/http-status.unauthorized.message'),
                        config('journey/http-status.unauthorized.code'),
                        []
                    );
                }
            } else {
                return $this->responseJson(
                    config('journey/http-status.bad-request.status'),
                    config('journey/http-status.bad-request.message'),
                    config('journey/http-status.bad-request.code'),
                    []
                );
            }
        } catch (Throwable) {
            return $this->responseJson(
                config('journey/http-status.unauthorized.status'),
                config('journey/http-status.unauthorized.message'),
                config('journey/http-status.unauthorized.code'),
                []
            );
        }
    }
}
