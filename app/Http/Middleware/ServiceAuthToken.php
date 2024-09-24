<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Service;

class ServiceAuthToken extends Service
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
            $headerToken = $request->header('X-Auth-CC-Token');
            $envToken = env('SERVICE_AUTHTOKEN');

            if ($headerToken && $headerToken === $envToken) {
                return $next($request);
            } else {
                return $this->responseJson(
                    config('journey/http-status.unauthorized.status'),
                    config('journey/http-status.unauthorized.message'),
                    config('journey/http-status.unauthorized.code'),
                    []
                );
            }
        } catch (\Exception $e) {
            return $this->responseJson(
                config('journey/http-status.error.status'),
                config('journey/http-status.error.message'),
                config('journey/http-status.error.code'),
                []
            );
        }
    }
}
