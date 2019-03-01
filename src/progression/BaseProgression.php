<?php

namespace semsty\amqp\progression;

use yii\base\BaseObject;

abstract class BaseProgression extends BaseObject
{
    const NAME = 'base';

    public static function getTypes(): array
    {
        return array_keys(static::getClassesByName());
    }

    public static function getClassesByName(): array
    {
        $result = [];
        foreach (static::getClasses() as $class) {
            $result[$class::NAME] = $class;
        }
        return $result;
    }

    public static function getClasses(): array
    {
        return [
            Arithmetic::class,
            Geometric::class,
            NaturalLogarithmic::class,
            DecimalLogarithmic::class,
            Randomic::class
        ];
    }

    public static function getProgression($name)
    {
        return static::getClassesByName()[$name];
    }

    public static function getMap($delay, $attempts)
    {
        $retries = [$delay];
        foreach (range(1, $attempts) as $attempt) {
            $retries[] = static::calculate($delay, $attempt);
        }
        return $retries;
    }

    public static function calculate($delay, $attempt)
    {
        return $delay;
    }
}