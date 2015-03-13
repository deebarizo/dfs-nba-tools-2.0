<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAbbrEspnColumnToMlbTeamsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('mlb_teams', function($table) 
		{ 
			$table->string('abbr_espn')->after('abbr_dk'); 
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
		    $table->dropColumn('abbr_espn');
		});
	}

}
