<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatFptsColumnToDkMlbPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('dk_mlb_players', function($table) 
		{ 
			$table->decimal('bat_fpts', 5, 2)->after('salary');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('dk_mlb_players', function($table)
		{
		    $table->dropColumn('bat_fpts');
		});
	}

}
