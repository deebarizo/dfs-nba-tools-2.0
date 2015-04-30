<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNameFgColumnToMlbTeamsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('mlb_teams', function($table) 
		{ 
			$table->string('name_fg')->after('abbr_espn'); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('mlb_teams', function($table)
		{
		    $table->dropColumn('name_fg');
		});
	}

}
