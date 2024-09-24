<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Utils\CoreTrait;
use Carbon\Carbon;

class DropoffSms extends Command
{
    use CoreTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dropoff:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dropoff sms';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    private $apRepo;
    public function __construct(ApplicationRepository $apRepo)
    {
        $this->apRepo = $apRepo;
        parent::__construct();
    }
    public function handle()
    {
        try {
            Log::info("Dropoff sms sent start");
            $this->getInactiveRecord();
        } catch (Throwable | ClientException $e) {
            Log::info("DropoffSms " . $e->getMessage());
        }
    }
    /**
     * Get inactive records
     */
    public function getInactiveRecord()
    {
        try {
            $inActiveRecord = $this->apRepo->getAllInActiveRecords();
            $productCode = null;
            $trackedQuoteIds = array();
            if ($inActiveRecord->isNotEmpty()) {
                foreach ($inActiveRecord as $quoteId) {
                    $count = $this->apRepo->getInActiveTrackingRecords($quoteId);
                    if ($count == 0) {
                        $trackedQuoteIds[] = $quoteId;
                    }
                }

                if (count($trackedQuoteIds) > 0) {
                    foreach ($trackedQuoteIds as $quote) {
                        $getApplicationData = $this->apRepo->getAppDataByQuoteId($quote);
                        $smsLogRecords = $this->apRepo->getsmsData($getApplicationData);
                        $currentDate = Carbon::now()->format('Y-m-d');
                        $carbonDate = Carbon::createFromTimestampMs($smsLogRecords->created_at);
                        $createdDate = $carbonDate->format('Y-m-d');
                        $to = Carbon::parse($currentDate);
                        $from = Carbon::parse($createdDate);
                        $days = $to->diffInDays($from);
                        if ($days != 0) {
                            $productCode = $getApplicationData->masterProductData->code;
                            $getApplicationData['product_name'] = $getApplicationData->masterProductData->display_name;
                            $payLoad['api_type'] = config('constants/apiType.STAGE_CHECK');
                            $payLoad['api_source'] = config('constants/apiSource.HOUSING_JOURNEY');
                            $payLoad['type'] =  config('constants/apiSourcePage.STAGE_CHECK');
                            $payLoad['api_data'] =  $getApplicationData;
                            $payLoad['url'] =  $this->getProductNameUrl($productCode);
                            $payLoad['sms_template_handle'] = config('constants/productStepHandle.drop-off');
                            $payLoad['user_name'] =  config('journey/sms.cc_username');
                            $payLoad['password'] =  config('journey/sms.cc_password');
                            $payLoad['app_data'] =  null;
                            $payLoad['mobile_number'] =  $getApplicationData['mobile_number'];
                            $payLoad['is_short_url_required'] = true;
                            $payLoad['is_email_required'] = false;
                            $payLoad['email'] = null;
                            $payLoad['email_template_handle'] =  null;
                            $this->sendEmailWithSMS($payLoad);
                            // insert into log
                            $logData['mobile_number'] = $getApplicationData['mobile_number'];
                            $logData['master_product_id'] = $getApplicationData['master_product_id'];
                            $logData['api_source'] = config('constants/apiSource.HOUSING_JOURNEY');
                            $logData['api_source_page'] = config('constants/apiSourcePage.STAGE_CHECK');
                            $logData['api_type'] = config('constants/apiType.DROP_OFF');
                            $logData['api_status_code'] = config('journey/http-status.success.code');
                            $logData['api_status_message'] = config('journey/http-status.success.message');
                            $this->apRepo->saveSmsLog($logData);
                            Log::info("Dropoff sms sent end");
                        } else {
                            Log::info("Dropoff sms already sent today");
                        }
                    }
                } else {
                    Log::info("Dropoff sms not sent");
                }
            } else {
                Log::info("Dropoff sms not sent");
            }
        } catch (Throwable | ClientException $e) {
            Log::info("DropoffSms getInactiveRecord " . $e->getMessage());
        }
    }
}
