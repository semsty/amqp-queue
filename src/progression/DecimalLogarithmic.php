<?php

namespace semsty\amqp\progression;

class DecimalLogarithmic extends BaseProgression
{
    const NAME = 'decimal-logarithmic';

    public static function calculate($delay, $attempt): int
    {
        return (integer)ceil(log10($attempt) * $delay);
    }
}