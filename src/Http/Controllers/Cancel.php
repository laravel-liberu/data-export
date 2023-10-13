<?php

namespace LaravelLiberu\DataExport\Http\Controllers;

use Illuminate\Routing\Controller;
use LaravelLiberu\DataExport\Models\Export;

class Cancel extends Controller
{
    public function __invoke(Export $export)
    {
        $export->cancel();

        return ['message' => __('The export was cancelled successfully')];
    }
}
