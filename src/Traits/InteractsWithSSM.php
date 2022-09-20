<?php

namespace Nandi95\LaravelEnvInAwsSsm\Traits;

use Aws\Credentials\Credentials;
use Aws\Ssm\SsmClient;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Dotenv\Dotenv;

trait InteractsWithSSM
{
    /**
     * The stage/environment of the app.
     *
     * @var string|null
     */
    protected string|null $stage;

    /**
     * The name of the app.
     *
     * @var string|null
     */
    private string|null $appName;

    /**
     * The region we're operating in.
     *
     * @var string|null
     */
    private string|null $region;

    /**
     * The Dotenv instance.
     *
     * @var Dotenv|null
     */
    private Dotenv|null $dotEnv;

    /**
     * @var Credentials|null
     */
    private Credentials|null $credentials;

    /**
     * @var SsmClient|null
     */
    private SsmClient|null $client;

    /**
     * Get the parameter name as a qualified path
     *
     * @link https://docs.aws.amazon.com/systems-manager/latest/APIReference/API_PutParameter.html#systemsmanager-PutParameter-request-Name
     *
     * @param string $name
     *
     * @return string
     */
    public function qualifyKey(string $name): string
    {
        return '/' . $this->getAppName() . '/' . $this->stage . '/' . $name;
    }

    /**
     * Get the key without the aws namespace.
     *
     * @param string $name
     *
     * @return string
     */
    public function unQualifyKey(string $name): string
    {
        return Str::afterLast($name, '/');
    }

    /**
     * @return Dotenv
     */
    public function getDotenv(): Dotenv
    {
        if (!isset($this->dotEnv)) {
            $this->dotEnv = new Dotenv;
        }

        return $this->dotEnv;
    }

    /**
     * @return Credentials
     */
    public function getCredentials(): Credentials
    {
        if (isset($this->credentials)) {
            return $this->credentials;
        }

        $env = $this->getEnvironmentVarsFromFile();
        $awsAccessKeyId = $this->option('accessKey') ?? $env->pull('AWS_ACCESS_KEY_ID');
        $awsSecretAccessKey = $this->option('secretKey') ?? $env->pull('AWS_SECRET_ACCESS_KEY');

        if (!$awsSecretAccessKey) {
            $awsSecretAccessKey = $this->secret('AWS_SECRET_ACCESS_KEY');
        }

        if (!$awsAccessKeyId) {
            $awsAccessKeyId = $this->secret('AWS_ACCESS_KEY_ID');
        }

        $this->credentials = new Credentials($awsAccessKeyId, $awsSecretAccessKey);

        return $this->credentials;
    }

    /**
     * Get the region we're operating in.
     *
     * @return string
     */
    public function getRegion(): string
    {
        if (isset($this->region)) {
            return $this->region;
        }

        if ($this->option('region')) {
            $this->region = $this->option('region');
            return $this->region;
        }

        if (file_exists('.env.' . $this->stage)) {
            $env = $this->getDotenv()->parse(file_get_contents('.env.' . $this->stage));

            if ($env['AWS_DEFAULT_REGION']) {
                $this->region = $env['AWS_DEFAULT_REGION'];
            }
        }

        if (!$this->region) {
            $this->region = $this->ask('AWS Region');
        }

        return $this->region;
    }

    /**
     * Get the SSM client.
     *
     * @return SsmClient
     */
    public function getClient(): SsmClient
    {
        if (!isset($this->client)) {
            $this->client = new SsmClient([
                'region'      => $this->getRegion(),
                'version'     => 'latest',
                'credentials' => $this->getCredentials(),
            ]);
        }

        return $this->client;
    }

    /**
     * Get the name of the app.
     *
     * @return string
     */
    public function getAppName(): string
    {
        if (isset($this->appName)) {
            return $this->appName;
        }

        $appName = $this->option('appName');

        if (!$appName) {
            $appName = $this->getEnvironmentVarsFromFile()->get('APP_NAME');
        }

        $this->appName = $appName ?? $this->ask('app name');

        return $this->appName;
    }

    /**
     * Get the environment variables as a collection.
     *
     * @return Collection<string, string>
     */
    private function getEnvironmentVarsFromFile(): Collection
    {
        $path = '.env.' . $this->stage;

        if (!file_exists($path)) {
            throw new InvalidArgumentException("'$path' doesn't exists.");
        }

        return collect($this->getDotenv()->parse(file_get_contents($path)));
    }

    /**
     * Get the environment variables from SSM parameter store.
     *
     * @param string|null $nextToken
     *
     * @return Collection<string, string>
     *
     * @throws Exception
     */
    public function getEnvironmentVarsFromRemote(string $nextToken = null): Collection
    {
        $arguments = ['Path' => '/' . $this->getAppName() . '/' . $this->stage];

        if ($nextToken) {
            $arguments['NextToken'] = $nextToken;
        }

        $awsResult = retry(
            [3000, 6000, 9000],
            fn () =>$this->getClient()->getParametersByPath($arguments)
        );

        $parameters = collect($awsResult['Parameters'])
            ->mapWithKeys(fn (array $parameter) => [$this->unQualifyKey($parameter['Name']) => $parameter['Value']]);

        if ($awsResult['NextToken']) {
            $this->getEnvironmentVarsFromRemote($awsResult['NextToken'])
                ->each(fn (string $val, string $key) => $parameters->put($key, $val));
        }

        return $parameters;
    }
}
