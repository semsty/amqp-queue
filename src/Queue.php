<?php

namespace semsty\amqp;

use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\AmqpQueue;
use semsty\amqp\progression\BaseProgression;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use yii\queue\amqp_interop\Queue as BaseQueue;
use yii\queue\ExecEvent;

/**
 * Class Queue
 * @package semsty\amqp
 */
class Queue extends BaseQueue
{
    const RETRY_DELAY = 'retry-delay';
    const RETRY_PROGRESSION = 'retry-progression';

    public $microseconds = false;

    public function init()
    {
        parent::init();
        $this->on(static::EVENT_BEFORE_EXEC, function (ExecEvent $event) {
            if ($event->job instanceof RetryableJob) {
                $event->job->id = $event->id;
            }
        });
    }

    /**
     * @return array
     */
    public static function getAvailableProgressions(): array
    {
        return BaseProgression::getTypes();
    }

    /**
     * @param RetryableJob $body
     * @param AmqpProducer $producer
     * @param AmqpMessage $newMessage
     * @param int $attempt
     * @throws ErrorException
     * @throws \Interop\Queue\DeliveryDelayNotSupportedException
     */
    public function processDelay(RetryableJob $body, AmqpProducer &$producer, AmqpMessage &$newMessage, int $attempt)
    {
        if ($retryDelay = $body->retryDelay) {
            $retryDelay = ceil($this->calculateRetryDelay($body->retryProgression, $retryDelay, $attempt));
            $newMessage->setProperty(static::DELAY, $retryDelay);
            $producer->setDeliveryDelay($retryDelay * ($this->microseconds ? 1 : 1000));
        }
    }

    public static function calculateRetryDelay($progression, $delay, $attempt)
    {
        $class = ArrayHelper::getValue(static::getAvailableProgressions(), $progression);
        if (!$class) {
            throw new ErrorException("progression $progression not available");
        }
        return $class::calculate($delay, $attempt);
    }

    public function count()
    {
        $this->open();
        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments(['x-max-priority' => $this->maxPriority]);
        return $this->context->declareQueue($queue);
    }

    /**
     * @param AmqpMessage $message
     * @param $queueName
     * @throws ErrorException
     * @throws \Interop\Queue\DeliveryDelayNotSupportedException
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\InvalidDestinationException
     * @throws \Interop\Queue\InvalidMessageException
     */
    protected function redeliverToQueue(AmqpMessage $message, $queueName)
    {
        $attempt = $message->getProperty(static::ATTEMPT, 1);
        $body = $this->serializer->unserialize($message->getBody());
        if ($body instanceof RetryableJob) {
            $body->currentAttempt = $attempt + 1;
        }
        $newMessage = $this->context->createMessage(
            $this->serializer->serialize($body),
            ArrayHelper::merge($message->getProperties(), ['previousId' => $message->getMessageId()]),
            $message->getHeaders()
        );
        $newMessage->setDeliveryMode($message->getDeliveryMode());
        $producer = $this->context->createProducer();
        if ($body instanceof RetryableJob) {
            $this->processDelay($body, $producer, $newMessage, $attempt);
        }
        $newMessage->setProperty(static::ATTEMPT, ++$attempt);
        $producer->send(
            $this->context->createQueue($queueName),
            $newMessage
        );
    }
}