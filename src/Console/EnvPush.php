<?php

namespace Nandi95\LaravelEnvInAwsSsm\Console;

use Exception;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Nandi95\LaravelEnvInAwsSsm\Traits\InteractsWithSSM;

class EnvPush extends Command
{
    use InteractsWithSSM;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:push {stage} {--appName=} {--secretKey=} {--accessKey=} {--region=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the environment variables for the given stage in the SSM parameter store.';

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $this->stage = $this->argument('stage');

        if (!file_exists('.env.' . $this->stage)) {
            throw new InvalidArgumentException("'.env.$this->stage' doesn't exists.");
        }

        $localEnvs = $this->getEnvironmentVarsFromFile();
        $bar = $this->getOutput()->createProgressBar($localEnvs->count() + 1);
        $remoteEnvs = $this->getEnvironmentVarsFromRemote();
        $bar->advance();

        $remoteKeysNotInLocal = $remoteEnvs->diffKeys($localEnvs);

        // user deleted some keys, remove from remote too
        if ($remoteKeysNotInLocal->isNotEmpty()) {
            $this->info($remoteKeysNotInLocal->count() . ' variables found not present in .env.' . $this->stage . '. Deleting removed keys.');
            if ($localEnvs->isEmpty()) {
                $this->warn('There are no environment variables set locally.');
                $this->confirm('This will remove all variables in SSM, are you sure you want to proceed?');
            }

            $qualifiedKeys = $remoteKeysNotInLocal
                ->keys()
                ->map(fn (string $key) => $this->qualifyKey($key))
                ->toArray();

            $this->getClient()->deleteParameters(['Names' => $qualifiedKeys]);
        }

        $localEnvs->each(function (string $val, string $key) use ($bar) {
            retry(
                [3000, 6000, 9000],
                fn () => $this->getClient()->putParameter([
                    'Name' => $this->qualifyKey($key),
                    'Value' => $val,
                    'Overwrite' => true,
                    'Type' => 'String'
                ])
            );
            $bar->advance();
        });

        return 0;
    }
}
