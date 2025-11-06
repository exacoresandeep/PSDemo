<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LogHourlyPing extends Command
{
    protected $signature = 'log:hourly-ping';
    protected $description = 'Log message to test hourly cron';

    public function handle()
    {
	    Log::info('🕐 HOURLY CRON TRIGGERED at: ' . now());
	    $this->info('🕐 HOURLY CRON TRIGGERED at: ' . now());
    }
}
