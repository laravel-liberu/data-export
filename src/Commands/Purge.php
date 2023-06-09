<?php

namespace LaravelEnso\DataExport\Commands;

use Illuminate\Console\Command;
use LaravelEnso\DataExport\Enums\Statuses;
use LaravelEnso\DataExport\Models\Export;

class Purge extends Command
{
    protected $signature = 'enso:data-export:purge';

    protected $description = 'Removes old exports';

    public function handle()
    {
        Export::expired()->notDeletable()
            ->update(['status' => Statuses::Cancelled]);

        Export::expired()->deletable()->get()->each->delete();
    }
}
