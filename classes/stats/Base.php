<?php namespace PlanetaDelEste\Stats\Classes\Stats;

use Carbon\Carbon;
use PlanetaDelEste\Stats\Models\Stat;

/**
 * Class Base
 *
 * @package PlanetaDelEste\Stats\Classes\Stats
 */
abstract class Base
{
    public static function query(): StatsQuery
    {
        return new StatsQuery(static::class);
    }

    public static function increase($number = 1, ?Carbon $timestamp = null)
    {
        $number = is_int($number) ? $number : 1;
        $stats = new static;
        $stats->createEvent(Stat::TYPE_CHANGE, $number, $timestamp);
    }

    protected function createEvent($type, $value, ?Carbon $timestamp = null): Stat
    {
        return Stat::create(
            [
                'name'       => $this->getName(),
                'code'       => $this->getCode(),
                'type'       => $type,
                'value'      => $value,
                'created_at' => $timestamp ?? now(),
            ]
        );
    }

    public function getName(): string
    {
        return class_basename($this);
    }

    public function getCode(): ?string
    {
        return null;
    }

    public static function decrease($number = 1, ?Carbon $timestamp = null)
    {
        $number = is_int($number) ? $number : 1;
        $stats = new static;
        $stats->createEvent(Stat::TYPE_CHANGE, -$number, $timestamp);
    }

    public static function set(int $value, ?Carbon $timestamp = null)
    {
        $stats = new static;
        $stats->createEvent(Stat::TYPE_SET, $value, $timestamp);
    }
}
