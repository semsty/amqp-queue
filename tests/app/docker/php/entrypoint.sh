#!/bin/sh

tests/app/docker/wait-for-it.sh $RABBITMQ_HOST:$RABBITMQ_PORT \
&& exec "$@"
