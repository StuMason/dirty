#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from 'aws-cdk-lib';
import { LayeredFunctionStack } from '../lib/layer-stack';

const envEU  = { account: '328601198472', region: 'eu-west-2' };

const app = new cdk.App();
const appName = app.node.tryGetContext('appName')
const appEnv = app.node.tryGetContext('appEnv')
const stackName = `${appName}-${appEnv}`
const appStack = new LayeredFunctionStack(app, 'ServerlessLaravel', { 
    stackName: stackName, 
    env: envEU,
});