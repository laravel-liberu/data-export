<?php

namespace LaravelLiberu\DataExport\Contracts;

use LaravelLiberu\DataExport\Models\Export;

interface AfterHook
{
    public function after(Export $export): void;
}
