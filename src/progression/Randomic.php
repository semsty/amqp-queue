<?php

namespace semsty\amqp\progression;

class Randomic extends BaseProgression
{
    const NAME = 'randomic';

    public static function calculate($delay, $attempt): int
    {
        return rand(0, $delay);
    }
}