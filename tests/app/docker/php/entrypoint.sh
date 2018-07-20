#!/bin/sh

composer install --prefer-dist --no-interaction \
&& tests/app/docker/wait-for-it.sh $RABBITMQ_HOST:$RABBITMQ_PORT \
&& exec "$@"
