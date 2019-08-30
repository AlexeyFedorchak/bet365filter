<?php

namespace App\Console\Commands;

use App\TelegramUser;
use Illuminate\Console\Command;

class TruncateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'truncate:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all users';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        TelegramUser::truncate();
        $this->info('Done');
    }
}
