<?php

namespace tests\drivers\amqp_interop;

use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;

class QueueTest extends CliTestCase
{
    public function testRetry()
    {
        $this->startProcess('php yii queue/listen');
        $job = new RetryJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);

        sleep(6);
        $this->assertFileExists($job->getFileName());
        $this->assertEquals('a', file_get_contents($job->getFileName()));
    }

    public function testDelayedRetry()
    {
        $this->startProcess('php yii queue/listen');
        $job = new RetryJob([
            'uid' => uniqid(),
            'retryDelay' => 10,
            'attempts' => 2,
            'first_run_failure' => true
        ]);
        $this->getQueue()->push($job);

        sleep(6);
        $this->assertFileNotExists($job->getFileName());

        sleep(10);
        $this->assertFileExists($job->getFileName());
        $this->assertEquals('a', file_get_contents($job->getFileName()));
    }


    protected function getQueue()
    {
        return Yii::$app->amqpInteropQueue;
    }
}
