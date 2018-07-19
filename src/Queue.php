<?php

namespace semsty\amqp;

use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpProducer;
use yii\queue\amqp_interop\Queue as BaseQueue;

/**
 * Class Queue
 * @package semsty\amqp
 */
class Queue extends BaseQueue
{
    const RETRY_DELAY = 'retry-delay';
    const RETRY_PROGRESSION = 'retry-progression';

    const ARITHMETIC_PROGRESSION = 'arithmetic';
    const GEOMETRIC_PROGRESSION = 'geometric';

    /**
     * @return array
     */
    public static function getProgressionTypes(): array
    {
        return [
            static::ARITHMETIC_PROGRESSION,
            static::GEOMETRIC_PROGRESSION
        ];
    }

    /**
     * @param AmqpMessage $message
     * @throws \Interop\Queue\DeliveryDelayNotSupportedException
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\InvalidDestinationException
     * @throws \Interop\Queue\InvalidMessageException
     */
    protected function redeliver(AmqpMessage $message)
    {
        $attempt = $message->getProperty(static::ATTEMPT, 1);
        $body = $this->serializer->unserialize($message->getBody());
        if ($body instanceof RetryableJob) {
            $body->currentAttempt = $attempt + 1;
        }
        $newMessage = $this->context->createMessage(
            $this->serializer->serialize($body),
            $message->getProperties(),
            $message->getHeaders()
        );
        $newMessage->setDeliveryMode($message->getDeliveryMode());
        $producer = $this->context->createProducer();
        if ($body instanceof RetryableJob) {
            $this->processDelay($body, $producer, $newMessage, $attempt);
        }
        $newMessage->setProperty(static::ATTEMPT, ++$attempt);
        $producer->send(
            $this->context->createQueue($this->queueName),
            $newMessage
        );
    }

    /**
     * @param RetryableJob $body
     * @param AmqpProducer $producer
     * @param AmqpMessage $newMessage
     * @param int $attempt
     * @throws \Interop\Queue\DeliveryDelayNotSupportedException
     */
    public function processDelay(RetryableJob $body, AmqpProducer &$producer, AmqpMessage &$newMessage, int $attempt)
    {
        if ($retryDelay = $body->retryDelay) {
            switch ($body->retryProgression) {
                case static::ARITHMETIC_PROGRESSION:
                    $retryDelay = $retryDelay * $attempt;
                    break;
                case static::GEOMETRIC_PROGRESSION:
                    $retryDelay = pow($retryDelay, $attempt);
                    break;
            }
            $newMessage->setProperty(static::DELAY, $retryDelay);
            $producer->setDeliveryDelay($retryDelay * 1000);
        }
    }
    
    public function count()
    {
        $this->open();
        if ($this->setupBrokerDone) {
            return;
        }
        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments(['x-max-priority' => $this->maxPriority]);
        return $this->context->declareQueue($queue);

    }
}
