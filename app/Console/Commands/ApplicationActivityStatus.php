<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use App\Repositories\HousingJourney\ApplicationRepository;
use Carbon\Carbon;


class ApplicationActivityStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ApplicationActivity:Records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Application Activity';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $apRepo;
    public function __construct(ApplicationRepository $apRepo)
    {
        $this->apRepo = $apRepo;
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->info("Application Activity Process Start...");
            Log::info("Application Activity Process Start...");
            $allApplication = $this->apRepo->getInactiveApplications();
            if ($allApplication) {
                foreach ($allApplication as $data) {
                    $this->apRepo->removeExistApplication($data->quote_id);
                }
            }
            $this->info("Application Activity Process End...");
            Log::info("Application Activity Process End...");
        } catch (Throwable | ClientException $e) {
            $this->info("ApplicationActivityStatus " . $e->getMessage());
            Log::info("ApplicationActivityStatus " . $e->getMessage());
        }
    }
}
