<?php

namespace App\Services;

use App\Services\Service;

class LanguageService extends Service
{
    public function setLanguage()
    {
        return $this->responseJson(
            config('journey/http-status.success.status'),
            config('journey/http-status.success.message'),
            config('journey/http-status.success.code'),
            ['info' => trans('message.title')]
        );
    }
}
