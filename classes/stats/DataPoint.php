<?php namespace PlanetaDelEste\Stats\Classes\Stats;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class DataPoint
 *
 * @package PlanetaDelEste\Stats\Classes\Stats
 */
class DataPoint implements Arrayable
{
    /** @var \Carbon\Carbon */
    public $start;

    /** @var \Carbon\Carbon */
    public $end;

    /** @var int */
    public $value;

    /** @var int */
    public $increments;

    /** @var int */
    public $decrements;

    /** @var int */
    public $difference;

    /** @var int */
    public $count;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return (array) $this;
    }
}
