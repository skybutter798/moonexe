<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckMemoryLimit extends Command
{
    protected $signature = 'check:memory';
    protected $description = 'Check current memory limit';

    public function handle()
    {
        $this->info('Memory limit: ' . ini_get('memory_limit'));
    }
}
