<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Services\HousingJourney\CCPushService;

class CCPushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CCPush:Request';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CC Push Data Process';
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(CCPushService $ccPushService)
    {
        try {
            $this->info("CCPush:Request Process Start...");
            Log::info("CCPush:Request Process Start...");
            $ccPushService->pushAllRecords();
            $this->info("CCPush:Request Command executed successfully!");
            Log::info("CCPush:Request Command executed successfully!");
        } catch (Throwable | ClientException $lead) {
            $this->info("CCPush:Request Command " . $lead->getMessage());
            Log::info("CCPush:Request Command " . $lead->getMessage());
        }
    }
}
