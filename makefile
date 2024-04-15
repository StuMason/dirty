codebuild:
	aws codebuild start-build --project-name CodeBuildProject4B91CF3F-CfSYNNwU4vQw --profile speakcloud \
	--secondary-sources-override '[{"sourceIdentifier": "laravel","type": "S3","location": "arn:aws:s3:::missingu-deployment-artifacts/missingu_artifact.zip"},{"sourceIdentifier": "cdk","type": "S3","location": "arn:aws:s3:::missingu-deployment-artifacts/missingu_cdk.zip"}]' \
	--buildspec-override "arn:aws:s3:::missingu-deployment-artifacts/buildspec.yaml" \
	--environment-variables-override '[{"name": "APP_NAME", "value": "serverless-laravel"},{"name": "APP_ENV", "value": "dev"},{"name": "VPC_ID", "value": "vpc-05ce71fe4605909aa"},{"name": "DECRYPT_KEY", "value": "1234"}]' \
	--image-override "aws/codebuild/standard:7.0" \
	--compute-type-override "BUILD_GENERAL1_SMALL" \
	--cache-override '{"type": "S3","location": "arn:aws:s3:::missingu-deployment-artifacts/codebuild-cache"}' \
	--logs-config-override '{"cloudWatchLogs": {"status": "ENABLED", "groupName": "/aws/codebuild/serverless-laravel/dev"}}'

laravelzip:
	zip -r ./missingu_artifact.zip . -x "*node_modules*" -x "*vendor*" -x ".env" -x "*.cache" -x "*.git*" -x ".idea" -x ".DS_Store" -x "*storage/framework/*" -x "*storage/logs/*" -x "*public/build/*" -x "Dockerfile*" -x "*cdk/*"
	aws s3 cp missingu_artifact.zip s3://missingu-deployment-artifacts/missingu_artifact.zip --profile speakcloud

run: laravelzip codebuild

tail:
	aws logs tail /aws/codebuild/CodeBuildProject4B91CF3F-CfSYNNwU4vQw --profile speakcloud --follow

migrate:
	aws lambda invoke --function-name serverless-laravel-dev-LaravelArtisan1CAD8727-eNw31i1RUaxE \
	--profile speakcloud --region eu-west-2 \
	--cli-binary-format raw-in-base64-out \
	--payload '"migrate:fresh --force"' response.json