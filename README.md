# Serverless Laravel v2

The idea here is someone would do something like:

```bash
composer install lambdaize/lambdaize
php artisan lambdaize:install --profile whatever
php artisan lambdaize:deploy --profile whatever
```

and it would:

- Create lambda functions for Web, Worker and Artisan
- Create a Queue, S3 bucket, EFS volume

Then on deploy it would:

- Deploy the code to the lambda functions
- Create a shared SQLite DB and .env file in the EFS volume

Quick, dirty, easy, cheap.

## CDK

Using CDK at the moment as the majority of the work is already done.

I've added the CDK stuff I did previously including the docker files.

> Don't forget the .dockerignore file cos CDK dies

I've run `npm install -g aws-cdk`

I've also run `composer install && npm install && npm run build` in the laravel directory.

I've also run `npm install` in the cdk directory and the laravel directory.

```bash
cdk bootstrap aws://328601198472/eu-west-2 --profile speakcloud
cdk synth --profile speakcloud
cdk deploy --profile speakcloud
```

Can then run:

```bash
aws lambda invoke --function-name ServerlessLaravel-LaravelArtisan1CAD8727-C9kwvKS3jqrl \
--profile speakcloud --region eu-west-2 \
--cli-binary-format raw-in-base64-out \
--payload '"missingu:deploy base64:H4SgnkghNxAFJ3c6tBZKHhX4o8U6jBpBYgGozIEBAUc="' response.json
```

which will give us something like this:

```
Running post deploy commands for the first time...
ERROR  Encrypted environment file not found. !# BOLLOCKS SO
Failed to decrypt the env file...
rename(\/var\/task\/.env.serverless,\/mnt\/store\/.env): Operation not permitted
Checking database...Database already exists.
Migrating database..
INFO  Preparing database.
Creating migration table ...................................... 86.96ms DONE
INFO  Running migrations.
0001_01_01_000000_create_users_table ......................... 612.67ms DONE0001_01_01_000001_create_cache_table ......................... 223.28ms DONE  0001_01_01_000002_create_jobs_table .......................... 519.24ms DONE
Database created successfully.
```

Perhaps move away from CDK, run the build commands using PHP? I dunno, would be more laravel.
