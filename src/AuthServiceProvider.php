<?php

namespace LaravelLiberu\DataExport;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use LaravelLiberu\DataExport\Models\Export;
use LaravelLiberu\DataExport\Policies\Policy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Export::class => Policy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
