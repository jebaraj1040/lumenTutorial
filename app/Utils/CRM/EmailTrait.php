<?php

namespace App\Utils\CRM;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Exception\ClientException;
use App\Mail\ExportData;
use Throwable;
use Exception;

trait EmailTrait
{
    /**
     * CRM EmailTrait
     *
     * @param
     * @return mixed
     */
    public function __construct()
    {
    }
    public function sendMail($toMailId, $url,  $dateRange, $module)
    {
        try {
            $authuser = auth('crm')->user();
            $name       = $authuser->first_name . ' ' . $authuser->middle_name . ' ' . $authuser->last_name;
            Mail::to($toMailId)->send(new ExportData($url, $name, $dateRange, $module));
            Log::info("sendMail - Success" . $toMailId);
        } catch (Throwable   | ClientException $throwable) {
            Log::info("Service : EmailTrait , Method : sendMail : %s" . $throwable->__toString());
        }
    }
}
