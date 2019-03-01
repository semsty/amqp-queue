<?php

namespace semsty\amqp\progression;

class Arithmetic extends BaseProgression
{
    const NAME = 'arithmetic';

    public static function calculate($delay, $attempt): int
    {
        return $delay * $attempt;
    }
}