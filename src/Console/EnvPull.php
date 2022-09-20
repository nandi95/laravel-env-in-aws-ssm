<?php

namespace Nandi95\LaravelEnvInAwsSsm\Console;

use Exception;
use Illuminate\Console\Command;
use Nandi95\LaravelEnvInAwsSsm\Traits\InteractsWithSSM;

class EnvPull extends Command
{
    use InteractsWithSSM;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:pull {stage} {--appName=} {--secretKey=} {--accessKey=} {--region=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve environment variables for the given stage from the SSM parameter store.';

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

        // todo group by the first part before `_` and add extra line in between in the output
        $resolvedEnv = $this->getEnvironmentVarsFromRemote()
            ->sortKeys()
            ->reduce(function (string $resolvedGroup, string $val, string $key) {
                    return $resolvedGroup . $key . '=' . $val . "\n";
                    },
                ''
            );

        if (file_exists('.env.' . $this->stage)) {
            $this->backupEnvFile();
        }

        file_put_contents('.env.' . $this->stage, $resolvedEnv);

        return 0;
    }

    /**
     * @return void
     */
    public function backupEnvFile(): void
    {
        $this->line('Backing up \'' . '.env.' . $this->stage . '\'');
        $backupFile = '.env.' . $this->stage . '.backup';

        if (file_exists($backupFile)) {
            $this->info("Skipping backing up, $backupFile already exists.");
            return;
        }

        copy('.env.' . $this->stage, $backupFile);
    }
}
