<?php

namespace Nandi95\LaravelEnvInAwsSsm;

use Nandi95\LaravelEnvInAwsSsm\Console\EnvPull;
use Nandi95\LaravelEnvInAwsSsm\Console\EnvPush;
use Illuminate\Support\ServiceProvider;

class EnvInAwsSsmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EnvPush::class,
                EnvPull::class
            ]);
        }
    }
}
