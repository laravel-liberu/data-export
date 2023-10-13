<?php

namespace LaravelLiberu\DataExport\Contracts;

use LaravelLiberu\DataExport\Models\Export;

interface Notifies
{
    public function notify(Export $export): void;
}
