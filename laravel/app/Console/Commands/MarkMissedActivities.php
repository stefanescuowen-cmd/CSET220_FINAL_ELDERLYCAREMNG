<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkMissedActivities extends Command
{
    protected $signature = 'care:mark-missed';
    protected $description = 'Mark scheduled care activities as missed after their scheduled time';

    public function handle(): int
    {
        $count = DB::table('activity_records')->where('status', 'Pending')->where('scheduled_at', '<', now())->update(['status' => 'Missed', 'updated_at' => now()]);
        $this->info("Marked {$count} activities as missed.");
        return self::SUCCESS;
    }
}
