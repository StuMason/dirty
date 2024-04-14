codebuild:
	aws codebuild start-build --project-name CodeBuildProject4B91CF3F-CfSYNNwU4vQw --profile speakcloud \
	--secondary-sources-override '[{"sourceIdentifier": "laravel","type": "S3","location": "arn:aws:s3:::missingu-deployment-artifacts/missingu_artifact.zip"},{"sourceIdentifier": "cdk","type": "S3","location": "arn:aws:s3:::missingu-deployment-artifacts/missingu_cdk.zip"}]' \
	--buildspec-override "arn:aws:s3:::missingu-deployment-artifacts/buildspec.yaml" \
	--environment-variables-override '[{"name": "APP_NAME", "value": "serverless-laravel"},{"name": "APP_ENV", "value": "dev"},{"name": "VPC_ID", "value": "vpc-05ce71fe4605909aa"},{"name": "DECRYPT_KEY", "value": "1234"}]' \
	--image-override "aws/codebuild/standard:7.0"

laravelzip:
	zip -r ./missingu_artifact.zip . -x "*node_modules*" -x "*vendor*" -x ".env" -x "*.cache" -x "*.git*" -x ".idea" -x ".DS_Store" -x "*storage/framework/*" -x "*storage/logs/*" -x "*public/build/*" -x "Dockerfile*" -x "*cdk/*"
	aws s3 cp missingu_artifact.zip s3://missingu-deployment-artifacts/missingu_artifact.zip --profile speakcloud

run: laravelzip codebuild