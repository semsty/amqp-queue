<?php

namespace common\core\queue;

use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpProducer;
use yii\queue\amqp_interop\Queue as BaseQueue;

class Queue extends BaseQueue
{
    const RETRY_DELAY = 'retry-delay';
    const RETRY_PROGRESSION = 'retry-progression';

    const ARITHMETIC_PROGRESSION = 'arithmetic';
    const GEOMETRIC_PROGRESSION = 'geometric';

    /**
     * @param AmqpMessage $message
     * @throws \Interop\Queue\DeliveryDelayNotSupportedException
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\InvalidDestinationException
     * @throws \Interop\Queue\InvalidMessageException
     */
    protected function redeliver(AmqpMessage $message)
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);
        $body = $this->serializer->unserialize($message->getBody());
        $newMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $newMessage->setDeliveryMode($message->getDeliveryMode());
        $producer = $this->context->createProducer();
        $this->processDelay($body, $producer, $newMessage, $attempt);
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);
        $producer->send(
            $this->context->createQueue($this->queueName),
            $newMessage
        );
    }

    /**
     * @param $body
     * @param AmqpProducer $producer
     * @param AmqpMessage $newMessage
     * @param $attempt
     * @throws \Interop\Queue\DeliveryDelayNotSupportedException
     */
    public function processDelay($body, AmqpProducer $producer, AmqpMessage $newMessage, $attempt)
    {
        if (array_key_exists('messageProperties', $body)) {
            $messageProperties = $body['messageProperties'];
            if ($retryDelay = $messageProperties[static::RETRY_DELAY]) {
                switch ($messageProperties[static::RETRY_PROGRESSION]) {
                    case static::ARITHMETIC_PROGRESSION:
                        $retryDelay = $retryDelay * $attempt;
                        break;
                    case static::GEOMETRIC_PROGRESSION:
                        $retryDelay = pow($retryDelay, $attempt);
                        break;
                }
                $newMessage->setProperty(self::DELAY, $retryDelay);
                $producer->setDeliveryDelay($retryDelay * 1000);
            }
        }
    }
}