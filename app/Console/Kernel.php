<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

	protected $commands = [
   	   \App\Console\Commands\FetchOutstandingPayments::class,
	   \App\Console\Commands\FetchCreditNotes::class,
	   \App\Console\Commands\FetchDealerData::class,
	   \App\Console\Commands\FetchItemDetails::class,
	   \App\Console\Commands\FetchOutstandingNew::class,
	   \App\Console\Commands\LogHourlyPing::class,
	    \App\Console\Commands\FetchInvoiceLayouts::class,
	];

	protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
	
	{$schedule->command('log:hourly-ping')->everyFiveMinutes();
		$schedule->call(function () {
			file_put_contents(
				storage_path('logs/cron-test.log'),
				"Cron ran at " . now() . PHP_EOL,
				FILE_APPEND
			);
		})->everyMinute();
		$schedule->command('app:fetch-outstanding-new')->hourly();
		$schedule->command('app:fetch-dealer-data')->hourly();
	   	$schedule->command('app:fetch-outstanding-payments')->hourly();
		$schedule->command('app:fetch-credit-notes')->hourly();
		$schedule->command('app:fetch-item-details')->hourly();
		$schedule->command('log:hourly-ping')->everyFiveMinutes();
		$schedule->command('app:fetch-invoice-layouts')->hourly();
	}

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
