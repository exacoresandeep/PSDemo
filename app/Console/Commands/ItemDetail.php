<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ItemDetail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:item-detail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
	    dd("Out pt here");
    }
}
