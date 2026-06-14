<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckCancellationSchema extends Command
{
    protected $signature = 'check:cancellation-schema';

    protected $description = 'Check cancellation_requests table schema';

    public function handle()
    {
        $this->info('Checking cancellation_requests table schema...');
        $this->newLine();

        $columns = DB::select('DESCRIBE cancellation_requests');

        foreach ($columns as $col) {
            $this->line("{$col->Field}: {$col->Type} ".($col->Null === 'YES' ? '(nullable)' : '(NOT NULL)'));
        }
    }
}
