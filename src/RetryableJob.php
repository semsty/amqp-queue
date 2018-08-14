<?php

namespace semsty\amqp;

use yii\base\ErrorException;
use yii\base\Model;
use yii\queue\RetryableJobInterface;

/**
 * Class RetryableJob
 * @property $messageProperties
 * @property $ttr
 * @property $attempts
 * @property $currentAttempt
 * @property $retryDelay
 * @property $retryProgression
 * @package semsty\amqp
 */
class RetryableJob extends Model implements RetryableJobInterface
{
    /**
     * Default attempts
     */
    const ATTEMPTS = 1;

    /**
     * Default TTR
     */
    const TTR = 60;

    /**
     * Default retry delay
     */
    const RETRY_DELAY = 0;

    const EVENT_BEFORE_PROCESS = 'beforeProcess';
    const EVENT_AFTER_PROCESS = 'afterProcess';

    protected $_current_attempt;
    protected $_attempts;
    protected $_ttr;
    protected $_retry_delay;
    protected $_retry_progression = Queue::ARITHMETIC_PROGRESSION;
    protected $_priority = 0;
    protected $_delay = 0;

    public function rules(): array
    {
        return [
            [['messageProperties', 'ttr', 'attempts', 'retryDelay', 'retryProgression', 'currentAttempt'], 'safe'],
            ['retryProgression', 'in', 'range' => Queue::getProgressionTypes()],
        ];
    }

    public function init()
    {
        parent::init();
        if (!$this->_attempts) {
            $this->_attempts = static::ATTEMPTS;
        }
        if (!$this->_ttr) {
            $this->_ttr = static::TTR;
        }
        if (!$this->_retry_delay) {
            $this->_retry_delay = static::RETRY_DELAY;
        }
        if (!$this->_current_attempt) {
            $this->_current_attempt = 1;
        }
        $this->on(static::EVENT_BEFORE_PROCESS, [$this, 'beforeProcess']);
        $this->on(static::EVENT_AFTER_PROCESS, [$this, 'afterProcess']);
    }

    public function getMessageProperties(): array
    {
        return [
            Queue::RETRY_DELAY => $this->retryDelay,
            Queue::RETRY_PROGRESSION => $this->retryProgression
        ];
    }

    public function execute($queue)
    {
        $this->trigger(static::EVENT_BEFORE_PROCESS);
        $this->process();
        $this->trigger(static::EVENT_AFTER_PROCESS);
    }

    public function process()
    {

    }

    public function setTtr(int $value)
    {
        $this->_ttr = $value;
    }

    public function getTtr(): int
    {
        return $this->_ttr;
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < $this->getAttempts();
    }

    public function getAttempts(): int
    {
        return $this->_attempts;
    }

    public function setAttempts(int $value)
    {
        $this->_attempts = $value;
    }

    public function getCurrentAttempt(): int
    {
        return $this->_current_attempt;
    }

    public function setCurrentAttempt(int $value)
    {
        $this->_current_attempt = $value;
    }

    public function isFirstExecute(): bool
    {
        return $this->currentAttempt == 1;
    }

    public function getRetryDelay(): int
    {
        return $this->_retry_delay;
    }

    public function setRetryDelay(int $value)
    {
        $this->_retry_delay = $value;
    }

    public function getRetryProgression(): string
    {
        return $this->_retry_progression;
    }

    public function setRetryProgression(string $value)
    {
        $this->_retry_progression = $value;
    }

    public function beforeProcess()
    {

    }

    public function afterProcess()
    {

    }

    public function getQueueName()
    {
        return 'queue';
    }

    public function priority(int $value)
    {
        $this->_priority = $value;
        return $this;
    }

    public function delay(int $value)
    {
        $this->_delay = $value;
        return $this;
    }

    public function push($queueName = null)
    {
        $queueComponent = $queueName ? $queueName : static::getQueueName();
        if (!isset(\Yii::$app->$queueComponent)) {
            throw new ErrorException("$queueComponent does not set");
        }
        $id = \Yii::$app->$queueComponent->delay($this->_delay)->priority($this->_priority)->push($this);
        return $id;
    }
}