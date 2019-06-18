<?php

namespace tests\drivers;

use Yii;

abstract class TestCase extends \tests\TestCase
{
    abstract protected function getQueue();

    protected function tearDown(): void
    {
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}
