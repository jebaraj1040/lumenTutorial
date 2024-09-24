<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->createLogFile();
    }

    protected function createLogFile()
    {
        // Define the path for the new log file
        $todayDate = Carbon::now()->format('Y-m-d');
        $logFilePath = storage_path('logs/lumen-' . $todayDate . '.log');
        if (!File::exists($logFilePath)) {
            File::put($logFilePath, '');
            chmod($logFilePath, 0777);
        }
    }
}
