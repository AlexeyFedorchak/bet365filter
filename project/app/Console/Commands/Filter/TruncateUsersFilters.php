<?php

namespace App\Console\Commands\Filter;

use Illuminate\Console\Command;
use App\UserFilters;

class TruncateUsersFilters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truncate:filters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'truncate all filters';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        UserFilters::truncate();

        $this->info('Done');
    }
}
