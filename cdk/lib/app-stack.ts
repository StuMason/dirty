import { HttpLambdaIntegration } from "@aws-cdk/aws-apigatewayv2-integrations-alpha";
import { HttpApi } from "@aws-cdk/aws-apigatewayv2-alpha";
import { Construct } from "constructs";
import {
    Duration,
    Stack,
    StackProps,
    CfnOutput,
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

        const vpc = new aws_ec2.Vpc(this, "ServerlessVPC", {
            maxAzs: 1,
            subnetConfiguration: [
                {
                    cidrMask: 24,
                    name: "Public",
                    subnetType: aws_ec2.SubnetType.PUBLIC,
                },
                {
                    cidrMask: 24,
                    name: "Private",
                    subnetType: aws_ec2.SubnetType.PRIVATE_ISOLATED,
                },
            ],
            ipAddresses: aws_ec2.IpAddresses.cidr("10.0.0.0/16"),
            natGateways: 0,
        });

        const bucket = new aws_s3.Bucket(this, "StorageBucket", {
            blockPublicAccess: aws_s3.BlockPublicAccess.BLOCK_ALL,
        });

        const fileSystem = new aws_efs.FileSystem(this, "ServerlessFileSystem", {vpc: vpc});

        const accessPoint = fileSystem.addAccessPoint("ServerlessEfsAccessPoint", {
            createAcl: {
                ownerGid: "1001",
                ownerUid: "1001",
                permissions: "750",
            },
            path: "/export/lambda",
            posixUser: {
                gid: "1001",
                uid: "1001",
            },
        });

        const lambdaSecGroup = new aws_ec2.SecurityGroup(
            this,
            "LambdaSecurityGroup",
            {
                vpc: vpc,
                allowAllOutbound: true,
            }
        );

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
                timeout: Duration.seconds(20),
                filesystem: aws_lambda.FileSystem.fromEfsAccessPoint(accessPoint, '/mnt/store'),
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
                timeout: Duration.seconds(20),
                filesystem: aws_lambda.FileSystem.fromEfsAccessPoint(accessPoint, '/mnt/store'),
            }
        );

        const queue = new aws_sqs.Queue(this, "DefaultSqs");

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
                timeout: Duration.seconds(20),
                filesystem: aws_lambda.FileSystem.fromEfsAccessPoint(accessPoint, '/mnt/store'),
            }
        );

        queue.grantSendMessages(laravelWeb);
        queue.grantSendMessages(laravelArtisan);
        queue.grantSendMessages(laravelWorker);

        bucket.grantReadWrite(laravelWeb);

        const webIntegration = new HttpLambdaIntegration(
            "LaravelWebIntegration",
            laravelWeb
        );

        const endpoint = new HttpApi(this, "ApiGateway", {
            defaultIntegration: webIntegration,
        });

        const endpointUrl =
            endpoint.url?.replace(/(^\w+:|^)\/\//, "").slice(0, -1) ??
            "No URL available";

        new CfnOutput(this, "EndpointURL", { value: endpointUrl });

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
                    origin: new aws_cloudfront_origins.HttpOrigin(endpointUrl),
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

        laravelArtisan.addEnvironment("QUEUE_CONNECTION", "sqs");
        laravelArtisan.addEnvironment(
            "SQS_PREFIX",
            `https://sqs.${this.region}.amazonaws.com/${this.account}`
        );
        laravelArtisan.addEnvironment("SQS_QUEUE", queue.queueName);

        laravelWeb.addEnvironment("QUEUE_CONNECTION", "sqs");
        laravelWeb.addEnvironment(
            "SQS_PREFIX",
            `https://sqs.${this.region}.amazonaws.com/${this.account}`
        );
        laravelWeb.addEnvironment("SQS_QUEUE", queue.queueName);

        laravelWorker.addEnvironment("QUEUE_CONNECTION", "sqs");
        laravelWorker.addEnvironment(
            "SQS_PREFIX",
            `https://sqs.${this.region}.amazonaws.com/${this.account}`
        );
        laravelWorker.addEnvironment("SQS_QUEUE", queue.queueName);

        laravelWeb.addEnvironment("ASSET_URL", `https://${distro.domainName}`);
        laravelWeb.addEnvironment("AWS_BUCKET", bucket.bucketName);
        laravelWeb.addEnvironment("FILESYSTEM_DISK", "s3");
        laravelWeb.addEnvironment("APP_CF_URL", `https://${distro.domainName}`);
        laravelWeb.addEnvironment("LOG_CHANNEL", "stderr");

        laravelArtisan.addEnvironment(
            "ASSET_URL",
            `https://${distro.domainName}`
        );
        laravelArtisan.addEnvironment("AWS_BUCKET", bucket.bucketName);
        laravelArtisan.addEnvironment("FILESYSTEM_DISK", "s3");
        laravelArtisan.addEnvironment(
            "APP_CF_URL",
            `https://${distro.domainName}`
        );
        laravelArtisan.addEnvironment("LOG_CHANNEL", "stderr");

        laravelWorker.addEnvironment(
            "ASSET_URL",
            `https://${distro.domainName}`
        );
        laravelWorker.addEnvironment("AWS_BUCKET", bucket.bucketName);
        laravelWorker.addEnvironment("FILESYSTEM_DISK", "s3");
        laravelWorker.addEnvironment(
            "APP_CF_URL",
            `https://${distro.domainName}`
        );
        laravelWorker.addEnvironment("LOG_CHANNEL", "stderr");

        new CfnOutput(this, "DistributionURL", { value: distro.domainName });
    }
}
