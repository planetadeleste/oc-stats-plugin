<?php namespace PlanetaDelEste\Stats\Classes\Stats;

use Carbon\Carbon;
use Lovata\Buddies\Models\User;
use Model;
use October\Rain\Support\Traits\Singleton;
use PlanetaDelEste\Stats\Models\Stat;

/**
 * Class StatsBase
 *
 * @package PlanetaDelEste\Stats\Classes\Stats
 *
 * @method static void increase(int $number = 1, Carbon $timestamp = null)
 * @method static void decrease(int $number = 1, Carbon $timestamp = null)
 * @method static void set(int $value, Carbon $timestamp = null)
 */
abstract class StatsBase
{
    use Singleton;

    /** @var Model */
    protected $obElement;

    /** @var \Lovata\Buddies\Models\User|int */
    protected $obUser;

    /** @var bool */
    protected $bUpdate = false;

    public static function query(): StatsQuery
    {
        return new StatsQuery(static::class);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @throws \Exception
     */
    public static function __callStatic(string $name, array $arguments): void
    {
        self::validate($name, $arguments);

        $iNumber = array_get($arguments, 0, 1);
        $iNumber = is_int($iNumber) ? $iNumber : intval($iNumber);
        $obDate = array_get($arguments, 1, now());
        $sType = $name == 'set' ? Stat::TYPE_SET : Stat::TYPE_CHANGE;
        if ($name == 'decrease') {
            $iNumber = -$iNumber;
        }
        $obStats = new static;
        $obStats->createEvent($sType, $iNumber, $obDate);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @throws \Exception
     */
    protected static function validate(string $name, array $arguments)
    {
        if (!in_array($name, self::methods())) {
            throw new \Exception("Invalid method {$name}");
        }

        if ($name == 'set' && !is_numeric($arguments[0])) {
            throw new \Exception('First argument must be an integer');
        }
    }

    protected static function methods(): array
    {
        return ['set', 'decrease', 'increase'];
    }

    protected function createEvent($type, $value, ?Carbon $timestamp = null): Stat
    {
        $arSearch = [
            'name'       => $this->getName(),
            'item_id'    => $this->getItemId(),
        ];
        $arAttrs = [
            'code'       => $this->getCode(),
            'user_id'    => $this->getUserId(),
            'type'       => $type,
            'value'      => $value,
            'created_at' => $timestamp ?? now(),
        ];
        return $this->bUpdate
            ? Stat::updateOrCreate($arSearch, $arAttrs)
            : Stat::create($arSearch + $arAttrs);
    }

    public function getName(): string
    {
        return get_class($this);
    }

    public function getCode(): ?string
    {
        return null;
    }

    public function getItemId(): ?int
    {
        return $this->obElement ? $this->obElement->id : null;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->obUser && is_a($this->obUser, User::class) ? $this->obUser->id : $this->obUser;
    }

    /**
     * @param null|Model $obModel
     *
     * @return $this|Model
     */
    public function model($obModel = null)
    {
        if ($obModel) {
            $this->obElement = $obModel;

            return $this;
        }

        return $this->obElement;
    }

    /**
     * @param \Lovata\Buddies\Models\User|null $obUser
     *
     * @return int|\Lovata\Buddies\Models\User|$this
     */
    public function user(User $obUser = null)
    {
        if (!$obUser) {
            return $this->obUser;
        }

        $this->obUser = $obUser;

        return $this;
    }

    /**
     * @param bool $bUpdate
     *
     * @return $this
     */
    public function update(bool $bUpdate = true): self
    {
        $this->bUpdate = $bUpdate;

        return $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @throws \Exception
     */
    public function __call(string $name, array $arguments): void
    {
        self::validate($name, $arguments);

        $iNumber = array_get($arguments, 0, 1);
        $iNumber = is_int($iNumber) ? $iNumber : intval($iNumber);
        $obDate = array_get($arguments, 1, now());
        $sType = $name == 'set' ? Stat::TYPE_SET : Stat::TYPE_CHANGE;
        if ($name == 'decrease') {
            $iNumber = -$iNumber;
        }
        $this->createEvent($sType, $iNumber, $obDate);
    }
}
