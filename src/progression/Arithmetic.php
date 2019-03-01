<?php

namespace semsty\amqp\progression;

class Arithmetic extends BaseProgression
{
    const NAME = 'arithmetic';

    public static function calculate($delay, $attempt)
    {
        return $delay * $attempt;
    }
}