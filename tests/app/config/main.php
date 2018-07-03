<?php
$config = [
    'id' => 'queue-app',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => [
        'amqpInteropQueue'
    ],
    'components' => [
        'amqpInteropQueue' => [
            'class' => \semsty\amqp\Queue::class,
            'host' => getenv('RABBITMQ_HOST') ?: 'localhost',
            'port' => getenv('RABBITMQ_PORT') ?: 5672,
            'user' => getenv('RABBITMQ_USER') ?: 'guest',
            'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
            'queueName' => 'queue-interop',
            'exchangeName' => 'exchange-interop',
        ]
    ],
];

return $config;
