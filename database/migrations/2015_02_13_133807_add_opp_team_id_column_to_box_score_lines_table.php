<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOppTeamIdColumnToBoxScoreLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('box_score_lines', function($table) 
		{ 
			$table->integer('opp_team_id')->after('team_id'); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('box_score_lines', function($table)
		{
		    $table->dropColumn('opp_team_id');
		});
	}

}
