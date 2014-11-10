<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumnnToPlayersFdTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('players_fd', function($table) 
		{ 
			$table->boolean('status')->after('top_play_index'); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('players_fd', function($table)
		{
		    $table->dropColumn('status');
		});
	}

}
