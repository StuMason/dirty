import { Construct } from "constructs";
import {
    Duration,
    Stack,
    StackProps,
    CfnOutput,
    CfnParameter,
    Fn,
    aws_lambda,
    aws_s3,
    aws_s3_deployment,
    aws_cloudfront,
    aws_cloudfront_origins,
    aws_ec2,
    aws_sqs,
    aws_lambda_event_sources,
    aws_efs,
} from "aws-cdk-lib";

export class AppStack extends Stack {
    constructor(scope: Construct, id: string, props?: StackProps) {
        super(scope, id, props);

        const vpcId = this.node.tryGetContext('vpcId');
        const appName = this.node.tryGetContext('appName');
        const appEnv = this.node.tryGetContext('appEnv');
        
        const efsIdExport = Fn.importValue("missinguEFSID");
        const efsSecurityGroupExport = Fn.importValue("missinguEFSSG");

        // const vpcExport = Fn.importValue("missinguVPC");
        const vpc = aws_ec2.Vpc.fromLookup(this, "Vpc", {
            vpcId: vpcId,
        });

        const fileSystem = aws_efs.FileSystem.fromFileSystemAttributes(
            this,
            "ServerlessEfs",
            {
                fileSystemId: efsIdExport,
                securityGroup: aws_ec2.SecurityGroup.fromSecurityGroupId(
                    this,
                    "EfsSecurityGroup",
                    efsSecurityGroupExport
                ),
            }
        );

        const accessPoint = new aws_efs.AccessPoint(this, "AccessPoint", {
            fileSystem: fileSystem,
            posixUser: {
                gid: "1000",
                uid: "1000",
            },
            createAcl: {
                ownerGid: "1000",
                ownerUid: "1000",
                permissions: "755",
            },
            path: `/stacks/lambda/${appName}/${appEnv}`
        });

        const lambdaSecGroup = new aws_ec2.SecurityGroup(
            this,
            "LambdaSecurityGroup",
            {
                vpc: vpc,
                allowAllOutbound: true,
            }
        );

        const functions = [
            {"LaravelWeb":{},
            {"LaravelArtisan":{},
            {"LaravelWorker":{}}
        ];

        const laravelArtisan = new aws_lambda.DockerImageFunction(
            this,
            "LaravelArtisan",
            {
                vpc: vpc,
                vpcSubnets: {
                    subnetType: aws_ec2.SubnetType.PRIVATE_ISOLATED,
                },
                securityGroups: [lambdaSecGroup],
                code: aws_lambda.DockerImageCode.fromImageAsset("../", {
                    file: "Dockerfile-artisan",
                }),
                memorySize: 1024,
                timeout: Duration.seconds(900),
                filesystem: aws_lambda.FileSystem.fromEfsAccessPoint(
                    accessPoint,
                    "/mnt/store"
                ),
            }
        );

        const laravelWorker = new aws_lambda.DockerImageFunction(
            this,
            "LaravelWorker",
            {
                vpc: vpc,
                vpcSubnets: {
                    subnetType: aws_ec2.SubnetType.PRIVATE_ISOLATED,
                },
                securityGroups: [lambdaSecGroup],
                code: aws_lambda.DockerImageCode.fromImageAsset("../", {
                    file: "Dockerfile-worker",
                }),
                memorySize: 1024,
                timeout: Duration.seconds(900),
                filesystem: aws_lambda.FileSystem.fromEfsAccessPoint(
                    accessPoint,
                    "/mnt/store"
                ),
            }
        );

        const queue = new aws_sqs.Queue(this, "DefaultSqs", {
            visibilityTimeout: Duration.seconds(900),
        });

        const eventSource = new aws_lambda_event_sources.SqsEventSource(queue);
        laravelWorker.addEventSource(eventSource);

        const laravelWeb = new aws_lambda.DockerImageFunction(
            this,
            "LaravelWeb",
            {
                vpc: vpc,
                vpcSubnets: {
                    subnetType: aws_ec2.SubnetType.PRIVATE_ISOLATED,
                },
                securityGroups: [lambdaSecGroup],
                code: aws_lambda.DockerImageCode.fromImageAsset("../"),
                memorySize: 1024,
                timeout: Duration.seconds(180),
                filesystem: aws_lambda.FileSystem.fromEfsAccessPoint(
                    accessPoint,
                    "/mnt/store"
                ),
            }
        );

        const lambdaUrl = laravelWeb.addFunctionUrl({
            authType: aws_lambda.FunctionUrlAuthType.NONE,
            cors: {
                allowedOrigins: ["*"],
                allowedHeaders: ["*"],
                allowCredentials: true,
            },
        });

        queue.grantSendMessages(laravelWeb);
        queue.grantSendMessages(laravelArtisan);
        queue.grantSendMessages(laravelWorker);

        laravelArtisan.addEnvironment("APP_NAME", appName);
        laravelArtisan.addEnvironment("APP_ENV", appEnv);
        laravelWorker.addEnvironment("APP_NAME", appName);
        laravelWorker.addEnvironment("APP_ENV", appEnv);
        laravelWeb.addEnvironment("APP_NAME", appName);
        laravelWeb.addEnvironment("APP_ENV", appEnv);




        const bucket = new aws_s3.Bucket(this, "StorageBucket", {
            blockPublicAccess: aws_s3.BlockPublicAccess.BLOCK_ALL,
        });

        bucket.grantReadWrite(laravelWeb);
        bucket.grantReadWrite(laravelArtisan);
        bucket.grantReadWrite(laravelWorker);

        const oai = new aws_cloudfront.OriginAccessIdentity(
            this,
            "CloudfrontOAI"
        );

        const headers = [
            "Accept",
            "Accept-Language",
            "Origin",
            "Referer",
            "x-inertia",
            "x-inertia-version",
            "x-requested-with",
            "x-xsrf-token",
            "x-csrf-token",
        ];

        const laravelOriginRequestPolicy =
            new aws_cloudfront.OriginRequestPolicy(
                this,
                "LaravelOriginRequestPolicy",
                {
                    cookieBehavior:
                        aws_cloudfront.OriginRequestCookieBehavior.all(),
                    headerBehavior:
                        aws_cloudfront.OriginRequestHeaderBehavior.allowList(
                            ...headers
                        ),
                    queryStringBehavior:
                        aws_cloudfront.OriginRequestQueryStringBehavior.all(),
                }
            );

        const distro = new aws_cloudfront.Distribution(
            this,
            "CloudfrontDistro",
            {
                defaultBehavior: {
                    origin: new aws_cloudfront_origins.HttpOrigin(
                        Fn.select(2, Fn.split("/", lambdaUrl.url))
                    ),
                    allowedMethods: aws_cloudfront.AllowedMethods.ALLOW_ALL,
                    viewerProtocolPolicy:
                        aws_cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
                    originRequestPolicy: laravelOriginRequestPolicy,
                },
                priceClass: aws_cloudfront.PriceClass.PRICE_CLASS_100,
            }
        );

        distro.addBehavior(
            "/build/*",
            new aws_cloudfront_origins.S3Origin(bucket, {
                originAccessIdentity: oai,
            }),
            {
                allowedMethods:
                    aws_cloudfront.AllowedMethods.ALLOW_GET_HEAD_OPTIONS,
                viewerProtocolPolicy:
                    aws_cloudfront.ViewerProtocolPolicy.REDIRECT_TO_HTTPS,
                originRequestPolicy:
                    aws_cloudfront.OriginRequestPolicy.CORS_S3_ORIGIN,
            }
        );

        new aws_s3_deployment.BucketDeployment(this, "StaticAssetsDeployment", {
            sources: [aws_s3_deployment.Source.asset("../public/build")],
            destinationBucket: bucket,
            destinationKeyPrefix: "build",
            exclude: ["*.php"],
        });

        new CfnOutput(this, "DistributionURL", { value: distro.domainName });
        new CfnOutput(this, "BucketName", { value: bucket.bucketName });
        new CfnOutput(this, "QueueName", { value: queue.queueName });

        // Function names
        new CfnOutput(this, "LaravelWebFunctionName", {
            value: laravelWeb.functionName,
        });

        new CfnOutput(this, "LaravelArtisanFunctionName", {
            value: laravelArtisan.functionName,
        });

        new CfnOutput(this, "LaravelWorkerFunctionName", {
            value: laravelWorker.functionName,
        });
    }
}
