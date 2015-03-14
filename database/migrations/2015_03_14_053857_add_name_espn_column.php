<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNameEspnColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('mlb_players', function($table) 
		{ 
			$table->string('name_espn')->after('name'); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('mlb_players', function($table)
		{
		    $table->dropColumn('name_espn');
		});
	}

}
