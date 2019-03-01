<?php

namespace semsty\amqp\progression;

class Geometric extends BaseProgression
{
    const NAME = 'geometric';

    public static function calculate($delay, $attempt): int
    {
        return pow($delay, $attempt);
    }
}