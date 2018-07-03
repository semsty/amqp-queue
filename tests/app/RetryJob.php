<?php

namespace tests\app;

use semsty\amqp\RetryableJob;
use Yii;

class RetryJob extends RetryableJob
{
    const ATTEMPTS = 2;

    public $uid;

    public $first_run_failure = false;

    public function process()
    {
        if ($this->first_run_failure && $this->attempts > 1) {
            throw new \Exception('Planned error.');
        }
        file_put_contents($this->getFileName(), 'a', FILE_APPEND);
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }
}
