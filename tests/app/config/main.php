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
            'driver' => \semsty\amqp\Queue::ENQUEUE_AMQP_LIB,
            'as log' => \yii\queue\LogBehavior::class,
            'readTimeout' => 60,
            'writeTimeout' => 60,
            'heartbeat' => 30
        ]
    ],
];

return $config;
