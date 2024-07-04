# laravel-env-in-aws-ssm
> Manage your environment variables in in AWS' SSM Parameter store

Download or upload your .env files into the free [AWS's SSM](https://eu-west-2.console.aws.amazon.com/systems-manager/parameters) store. This allows you to store up to 10,000 keys over your aws account in each region. More keys are available subject to your [quota](https://docs.aws.amazon.com/general/latest/gr/ssm.html).

This provides a good companion to referencing env values in cloudformation, serverless framework or to download within runners in other forms of Continuous Deployment processes.

```shell
composer require nandi95/laravel-env-in-aws-ssm
```

This package provides two commands:
```shell
php artisan env:push
php artisan env:pull
php artisan env:list
```

### Arguments:
 - `stage` - this is something the equivalent of `production|staging|develop|...`) which identifies what environment the variables are used in.

 - `--appName=`(optional) - this is the name of the current app (equivalent the APP_NAME in the `.env` file normally). If not given, or cannot be found, it will prompt the user for it.

 - `--secretKey=`(optional) - The secret key for the user with the required permissions. If not given, or cannot be found, it will prompt the user for it.

 - `--accessKey=`(optional) - The access key id for the user with the required permissions. If not given, or cannot be found, it will prompt the user for it.

 - `--region=`(optional) - The region the infrastructure resides in. If not given, or cannot be found, it will prompt the user for it.

 - `--decrypt`(optional | Default: false) - Decrypt the values before pulling them.
    > See more details about encrypt in the [AWS documentation](https://docs.aws.amazon.com/kms/latest/developerguide/services-parameter-store.html)
---

Both commands will use the env file respective to the stage argument. For example: with stage argument `production` it will work with the `.env.production` file. If the file exists when pulling, it will back up the existing file.

### Parameter <-> environment variable
Keys are transformed in the following manner:
`DB_PASSWORD` => `{appName}/{stage}/DB_PASSWORD`
