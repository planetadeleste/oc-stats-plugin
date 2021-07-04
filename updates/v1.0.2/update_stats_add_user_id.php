<?php

namespace PlanetaDelEste\Stats\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class UpdateStatsAddUserId extends Migration
{
    const TABLE = 'planetadeleste_stats_stats';

    public function up()
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::table(self::TABLE, function (Blueprint $obTable) {
                $obTable->unsignedInteger('user_id')->nullable();
                $obTable->index('user_id');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        if (Schema::hasColumns(self::TABLE, ['user_id'])) {
            Schema::table(self::TABLE, function (Blueprint $obTable) {
                $obTable->dropColumn(['user_id']);
            });
        }
    }
}
