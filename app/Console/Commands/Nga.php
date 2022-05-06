<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Nga extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nga';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nga 帖子缓存';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
