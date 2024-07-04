<?php

declare(strict_types=1);

namespace Nandi95\LaravelEnvInAwsSsm\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nandi95\LaravelEnvInAwsSsm\Traits\InteractsWithSSM;

class EnvList extends Command
{
    use InteractsWithSSM;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:list 
                            {stage : The environment of the app}
                            {--key= : Display only this key}
                            {--appName=}
                            {--secretKey=}
                            {--accessKey=}
                            {--region=}
                            {--decrypt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the environment variables for the given stage from the SSM parameter store.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $this->stage = $this->argument('stage');

        if ($this->option('decrypt')) {
            $this->decrypt = true;
        }

        $keyValues = $this->unifySplitValues($this->getEnvironmentVarsFromRemote()->sortKeys());

        if ($this->option('key')) {
            $keyValues = $keyValues->filter(fn ($value, $key): bool => $key === $this->option('key'));
        }

        $this->table(
            ['Key', 'Value'],
            $keyValues->map(fn ($value, $key): array => [$key, $value])->toArray()
        );

        return 0;
    }
}
