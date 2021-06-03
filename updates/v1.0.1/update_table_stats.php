<?php namespace PlanetaDelEste\Stats\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Class UpdateTableStats
 */
class UpdateTableStats extends Migration
{
    const TABLE = 'planetadeleste_stats_stats';

    public function up()
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::table(
                self::TABLE,
                function (Blueprint $obTable) {
                    $obTable->unsignedInteger('item_id')->nullable();
                }
            );
        }

    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE)) {
            return false;
        }

        $arCols = ['item_id'];
        if (Schema::hasColumns(self::TABLE, $arCols)) {
            Schema::table(
                self::TABLE,
                function (Blueprint $obTable) use ($arCols) {
                    $obTable->dropColumn($arCols);
                }
            );
        }
    }

}
