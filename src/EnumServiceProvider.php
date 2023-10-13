<?php

namespace LaravelLiberu\DataExport;

use LaravelLiberu\DataExport\Enums\Statuses;
use LaravelLiberu\Enums\EnumServiceProvider as ServiceProvider;

class EnumServiceProvider extends ServiceProvider
{
    public $register = [
        'exportStatuses' => Statuses::class,
    ];
}
