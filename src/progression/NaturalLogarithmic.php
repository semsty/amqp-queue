<?php

namespace semsty\amqp\progression;

class NaturalLogarithmic extends BaseProgression
{
    const NAME = 'natural-logarithmic';

    public static function calculate($delay, $attempt): int
    {
        return (integer)ceil(log($attempt) * $delay);
    }
}