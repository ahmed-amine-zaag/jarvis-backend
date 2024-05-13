#!/bin/bash -xe

APPLICATION=ws-jarvis-dev-docker
REGION=eu-west-1
ENVIRONMENT=ws-jarvis-dev-docker
#PLATFORM=python-3.8 

# get identity
#aws sts get-caller-identity

# intialize the application
eb init "${APPLICATION}"  --region "${REGION}"

# select the environment
eb use "${ENVIRONMENT}"

# deploy the application
eb deploy

# get the deployment's health and status information
eb health
eb status