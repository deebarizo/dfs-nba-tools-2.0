<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaAndOtherColumnsToMlbBoxScoreLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('mlb_box_score_lines', function($table) 
		{ 
			$table->integer('pa')->after('mlb_player_id');
			$table->integer('ibb')->after('bb');
			$table->integer('sf')->after('hbp');
			$table->integer('sh')->after('sf');
			$table->integer('gdp')->after('sh');
			$table->integer('runs_against')->after('er');
			$table->integer('ibb_against')->after('bb_against');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('mlb_box_score_lines', function($table)
		{
		    $table->dropColumn('pa');
		    $table->dropColumn('ibb');
		    $table->dropColumn('sf');
		    $table->dropColumn('sh');
		    $table->dropColumn('gdp');
		    $table->dropColumn('runs_against');
		    $table->dropColumn('ibb_against');
		});
	}

}
