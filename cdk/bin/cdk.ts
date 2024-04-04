#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from 'aws-cdk-lib';
import { AppStack } from '../lib/app-stack';

const envEU  = { account: '328601198472', region: 'eu-west-2' };

const app = new cdk.App();
const appStack = new AppStack(app, 'ServerlessLaravel', { env: envEU });