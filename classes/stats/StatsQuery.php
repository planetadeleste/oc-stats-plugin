<?php namespace PlanetaDelEste\Stats\Classes\Stats;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PlanetaDelEste\Stats\Models\Stat;

/**
 * Class StatsQuery
 *
 * @package PlanetaDelEste\Stats\Classes\Stats
 */
class StatsQuery
{
    /** @var StatsBase */
    protected $statistic;

    /** @var string */
    protected $period;

    /** @var \Illuminate\Support\Carbon */
    protected $start;

    /** @var \Illuminate\Support\Carbon */
    protected $end;

    /** @var string */
    protected $code;

    /**
     * StatsQuery constructor.
     *
     * @param string|StatsBase $statistic
     */
    public function __construct(string $statistic)
    {
        $this->statistic = $statistic::instance();
        $this->period = 'week';
        $this->start = now()->subMonth();
        $this->end = now();
    }

    public static function for(string $statistic): self
    {
        return new self($statistic);
    }

    public function groupByYear(): self
    {
        $this->period = 'year';

        return $this;
    }

    public function groupByMonth(): self
    {
        $this->period = 'month';

        return $this;
    }

    public function groupByWeek(): self
    {
        $this->period = 'week';

        return $this;
    }

    public function groupByDay(): self
    {
        $this->period = 'day';

        return $this;
    }

    public function groupByHour(): self
    {
        $this->period = 'hour';

        return $this;
    }

    public function byCode(string $sCode): self
    {
        $this->code = $sCode;

        return $this;
    }

    /**
     * @param Carbon $start
     *
     * @return $this
     */
    public function start(Carbon $start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @param Carbon $end
     *
     * @return $this
     */
    public function end(Carbon $end): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection|\October\Rain\Support\Collection
     */
    public function get()
    {
        $periods = $this->generatePeriods();
        $changes = $this->queryStats()
            ->whereType(Stat::TYPE_CHANGE)
            ->where('created_at', '>=', $this->start)
            ->where('created_at', '<', $this->end)
            ->get();
        $differencesPerPeriod = $this->getDifferencesPerPeriod();
        $latestSetPerPeriod = $this->getLatestSetPerPeriod();
        $lastPeriodValue = $this->getValue($this->start);

        return $periods->map(
            function (array $periodBoundaries) use (
                $latestSetPerPeriod,
                $changes,
                $differencesPerPeriod,
                &
                $lastPeriodValue
            ) {
                [$periodStart, $periodEnd, $periodKey] = $periodBoundaries;
                $setEvent = $latestSetPerPeriod->where('period', $periodKey)->first();
                $startValue = $setEvent['value'] ?? $lastPeriodValue;
                $applyChangesAfter = $setEvent['created_at'] ?? $periodStart;

                $difference = $changes
                    ->where('created_at', '>=', $applyChangesAfter)
                    ->where('created_at', '<', $periodEnd)
                    ->sum('value');

                $value = $startValue + $difference;
                $lastPeriodValue = $value;

                $obDataPoint = new DataPoint();
                $obDataPoint->start = $periodStart;
                $obDataPoint->end = $periodEnd;
                $obDataPoint->value = (int)$value;
                $obDataPoint->increments = (int)($differencesPerPeriod[$periodKey]['increments'] ?? 0);
                $obDataPoint->decrements = (int)($differencesPerPeriod[$periodKey]['decrements'] ?? 0);
                $obDataPoint->difference = (int)($differencesPerPeriod[$periodKey]['difference'] ?? 0);

                return $obDataPoint;
            }
        );
    }

    /**
     * @return \Illuminate\Support\Collection|\October\Rain\Support\Collection
     */
    public function generatePeriods()
    {
        $data = collect();
        $currentDateTime = (new Carbon($this->start))->startOf($this->period);

        do {
            $data->push(
                [
                    $currentDateTime->copy(),
                    $currentDateTime->copy()->add(1, $this->period),
                    $currentDateTime->format($this->getPeriodTimestampFormat()),
                ]
            );

            $currentDateTime->add(1, $this->period);
        } while ($currentDateTime->lt($this->end));

        return $data;
    }

    public function getPeriodTimestampFormat(): string
    {
        $sResponse = '';
        switch ($this->period) {
            case 'year':
                $sResponse = 'Y';
                break;
            case 'month':
                $sResponse = 'Y-m';
                break;
            case 'week':
                $sResponse = 'oW'; // see https://stackoverflow.com/questions/15562270/php-datew-vs-mysql-yearweeknow
                break;
            case 'day':
                $sResponse = 'Y-m-d';
                break;
            case 'hour':
                $sResponse = 'Y-m-d H';
                break;
            case 'minute':
                $sResponse = 'Y-m-d H:i';
                break;
        };

        return $sResponse;
    }

    /**
     * @return Builder|Stat
     */
    protected function queryStats()
    {
        $obQuery = Stat::query()
            ->where('name', $this->statistic->getName());

        if ($this->code) {
            $obQuery->where('code', $this->code);
        }

        return $obQuery;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|\October\Rain\Database\Builder[]|\PlanetaDelEste\Stats\Models\Stat[]
     */
    protected function getDifferencesPerPeriod()
    {
        return $this->queryStats()
            ->whereType(Stat::TYPE_CHANGE)
            ->where('created_at', '>=', $this->start)
            ->where('created_at', '<', $this->end)
            ->selectRaw('sum(case when value > 0 then value else 0 end) as increments')
            ->selectRaw('abs(sum(case when value < 0 then value else 0 end)) as decrements')
            ->selectRaw('sum(value) as difference')
            ->groupByPeriod($this->period)
            ->get()
            ->keyBy('period');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection|\October\Rain\Database\Builder[]|\PlanetaDelEste\Stats\Models\Stat[]
     */
    protected function getLatestSetPerPeriod()
    {
        $periodDateFormat = Stat::getPeriodDateFormat($this->period);
        $sTable = (new Stat)->table;

        $rankedSets = $this->queryStats()
            ->selectRaw(
                "ROW_NUMBER() OVER (PARTITION BY {$periodDateFormat} ORDER BY `id` DESC) AS rn, `{$sTable}`.*, {$periodDateFormat} as period"
            )
            ->whereType(Stat::TYPE_SET)
            ->where('created_at', '>=', $this->start)
            ->where('created_at', '<', $this->end)
            ->get();

        return $rankedSets->where('rn', 1);
    }

    /**
     * Gets the value at a point in time by using the previous
     * snapshot and the changes since that snapshot.
     *
     * @param Carbon $dateTime
     *
     * @return int
     */
    public function getValue(Carbon $dateTime): int
    {
        /** @var Stat|Builder $nearestSet */
        $nearestSet = $this->queryStats()
            ->whereType(Stat::TYPE_SET)
            ->where('created_at', '<', $dateTime)
            ->orderByDesc('created_at')
            ->first();

        $startId = optional($nearestSet)->id ?? 0;
        $startValue = optional($nearestSet)->value ?? 0;

        $differenceSinceSet = $this->queryStats()
            ->whereType(Stat::TYPE_CHANGE)
            ->where('id', '>', $startId)
            ->where('created_at', '<', $dateTime)
            ->sum('value');

        return $startValue + $differenceSinceSet;
    }

    public function getStatistic(): StatsBase
    {
        return $this->statistic;
    }
}
