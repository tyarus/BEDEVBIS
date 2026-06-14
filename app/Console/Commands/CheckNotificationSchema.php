<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckNotificationSchema extends Command
{
    protected $signature = 'check:notification-schema';

    protected $description = 'Check notifications table schema';

    public function handle()
    {
        $this->info('Checking notifications table schema...');
        $this->newLine();

        // Get column information
        $columns = DB::select('DESCRIBE notifications');

        foreach ($columns as $col) {
            if ($col->Field === 'type') {
                $this->line("Column: {$col->Field}");
                $this->line("Type: {$col->Type}");
                $this->line("Null: {$col->Null}");
                $this->line("Key: {$col->Key}");
                $this->line("Default: {$col->Default}");
                $this->line("Extra: {$col->Extra}");

                if (strpos($col->Type, 'varchar') !== false) {
                    $this->info('✓ Type column is VARCHAR - OK!');
                } else {
                    $this->error('✗ Type column is NOT VARCHAR - NEEDS FIXING!');
                    $this->line("Current type: {$col->Type}");
                }
            }
        }
    }
}
