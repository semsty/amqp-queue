<?php

namespace semsty\amqp\progression;

class DecimalLogarithmic extends BaseProgression
{
    const NAME = 'decimal-logarithmic';

    public static function calculate($delay, $attempt)
    {
        return log10($attempt) * $delay;
    }
}