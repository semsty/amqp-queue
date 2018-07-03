<?php

namespace semsty\amqp;

use yii\base\Model;
use yii\queue\RetryableJobInterface;

/**
 * Class RetryableJob
 * @property $messageProperties
 * @property $ttr
 * @property $attempts
 * @property $retryDelay
 * @property $retryProgression
 * @package console\models\tasks
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

    protected $_attempts;
    protected $_ttr;
    protected $_retry_delay;
    protected $_retry_progression = Queue::ARITHMETIC_PROGRESSION;

    public function rules(): array
    {
        return [
            [['messageProperties', 'ttr', 'attempts', 'retryDelay', 'retryProgression'], 'safe'],
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
}