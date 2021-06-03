<?php namespace PlanetaDelEste\Stats\Updates;

use Schema;
use Illuminate\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Class CreateTableStats
 * @package PlanetaDelEste\Stats\Classes\Console
 */
class CreateTableStats extends Migration
{
    const TABLE = 'planetadeleste_stats_stats';

    /**
     * Apply migration
     */
    public function up()
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $obTable)
        {
            $obTable->engine = 'InnoDB';
            $obTable->increments('id')->unsigned();

            $obTable->string('name');
            $obTable->string('type', 50);
            $obTable->string('code')->nullable()->index();
            $obTable->bigInteger('value');

            $obTable->timestamps();
        });
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        Schema::dropIfExists(self::TABLE);
    }
}
