<?php

namespace LaravelLiberu\DataExport\Contracts;

use LaravelLiberu\DataExport\Models\Export;

interface BeforeHook
{
    public function before(Export $export): void;
}
