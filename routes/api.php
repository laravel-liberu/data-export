<?php

use Illuminate\Support\Facades\Route;
use LaravelLiberu\DataExport\Http\Controllers\Cancel;

Route::middleware(['api', 'auth', 'core'])
    ->prefix('api/export')->as('export.')
    ->group(fn () => Route::patch('{export}/cancel', Cancel::class)
        ->name('cancel'));
