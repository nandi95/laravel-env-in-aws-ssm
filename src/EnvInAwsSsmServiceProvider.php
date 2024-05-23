<?php

declare(strict_types=1);

namespace Nandi95\LaravelEnvInAwsSsm;

use Illuminate\Support\ServiceProvider;
use Nandi95\LaravelEnvInAwsSsm\Console\EnvList;
use Nandi95\LaravelEnvInAwsSsm\Console\EnvPull;
use Nandi95\LaravelEnvInAwsSsm\Console\EnvPush;

class EnvInAwsSsmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EnvPush::class,
                EnvPull::class,
                EnvList::class
            ]);
        }
    }
}
