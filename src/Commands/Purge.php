<?php

namespace LaravelLiberu\DataExport\Commands;

use Illuminate\Console\Command;
use LaravelLiberu\DataExport\Enums\Statuses;
use LaravelLiberu\DataExport\Models\Export;

class Purge extends Command
{
    protected $signature = 'liberu:data-export:purge';

    protected $description = 'Removes old exports';

    public function handle()
    {
        Export::expired()->notDeletable()
            ->update(['status' => Statuses::Cancelled]);

        Export::expired()->deletable()->get()->each->delete();
    }
}
