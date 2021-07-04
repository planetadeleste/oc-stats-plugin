<?php namespace PlanetaDelEste\Stats\Models;

use Lovata\Buddies\Models\User;
use Model;
use Kharanenka\Scope\NameField;
use Kharanenka\Scope\TypeField;
use Kharanenka\Scope\CodeField;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Traits\Validation;
use Lovata\Toolbox\Traits\Helpers\TraitCached;

/**
 * Class Stat
 *
 * @package PlanetaDelEste\Stats\Models
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property integer                   $id
 * @property integer                   $item_id
 * @property integer                   $user_id
 * @property string                    $name
 * @property string                    $code
 * @property string                    $type
 * @property int                       $value
 * @property \October\Rain\Argon\Argon $created_at
 * @property \October\Rain\Argon\Argon $updated_at
 *
 * @property-read User                 $user
 * @method static BelongsTo|User user()
 *
 * @method static Builder|$this increments()
 * @method static Builder|$this decrements()
 * @method static Builder|$this groupByPeriod(string $period)
 * @method static Builder|$this whereType(string $sType)
 * @method static Builder|$this getByItem(int $iItemID)
 * @method static Builder|$this getByUser(int|User $obUser)
 */
class Stat extends Model
{
    use Validation;
    use NameField;
    use CodeField;
    use TypeField;
    use TraitCached;

    const TYPE_SET = 'set';
    const TYPE_CHANGE = 'change';

    /** @var string */
    public $table = 'planetadeleste_stats_stats';

    /** @var array */
    public $implement = [];

    /** @var array */
    public $translatable = [];

    /** @var array */
    public $attributeNames = [
        'slug' => 'lovata.toolbox::lang.field.slug',
    ];

    /** @var array */
    public $rules = [
        'name' => 'required',
    ];

    /** @var array */
    public $jsonable = [];

    /** @var string[] */
    public $fillable = [
        'name',
        'code',
        'type',
        'value',
        'item_id',
        'user_id',
    ];

    /** @var string[] */
    public $cached = [
        'id',
        'item_id',
        'user_id',
        'name',
        'code',
        'value',
        'type',
    ];

    /** @var string[] */
    public $dates = [
        'created_at',
        'updated_at',
    ];

    /** @var array */
    public $belongsTo = [
        'user' => [User::class]
    ];

    /** @var string[] */
    protected $casts = [
        'value' => 'integer',
    ];

    public function scopeGroupByPeriod(Builder $query, string $period): Builder
    {
        $periodGroupBy = static::getPeriodDateFormat($period);

        return $query->groupByRaw($periodGroupBy)
            ->selectRaw("{$periodGroupBy} as period");
    }

    public static function getPeriodDateFormat(string $period): string
    {
        $sResponse = "";
        switch ($period) {
            case 'year':
                $sResponse = "date_format(created_at,'%Y')";
                break;
            case 'month':
                $sResponse = "date_format(created_at,'%Y-%m')";
                break;
            case 'week':
                $sResponse = "yearweek(created_at, 3)"; // see https://stackoverflow.com/questions/15562270/php-datew-vs-mysql-yearweeknow
                break;
            case 'day':
                $sResponse = "date_format(created_at,'%Y-%m-%d')";
                break;
            case 'hour':
                $sResponse = "date_format(created_at,'%Y-%m-%d %H')";
                break;
            case 'minute':
                $sResponse = "date_format(created_at,'%Y-%m-%d %H:%i')";
                break;
        };

        return $sResponse;
    }

    public function scopeIncrements(Builder $query): Builder
    {
        return $query->where('value', '>', 0);
    }

    public function scopeDecrements(Builder $query): Builder
    {
        return $query->where('value', '<', 0);
    }

    public function scopeGetByItem(Builder $obQuery, int $iItemID): Builder
    {
        return $obQuery->where('item_id', $iItemID);
    }

    /**
     * @param \October\Rain\Database\Builder $obQuery
     * @param User|int                       $obUser
     *
     * @return \October\Rain\Database\Builder
     */
    public function scopeGetByUser(Builder $obQuery, $obUser): Builder
    {
        $iUserID = is_a($obUser, User::class) ? $obUser->id : $obUser;
        return $obQuery->where('user_id', $iUserID);
    }
}
