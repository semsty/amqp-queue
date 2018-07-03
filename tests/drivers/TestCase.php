<?php

namespace tests\drivers;

use Yii;
use yii\queue\Queue;

abstract class TestCase extends \tests\TestCase
{
    abstract protected function getQueue();

    protected function tearDown()
    {
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}
