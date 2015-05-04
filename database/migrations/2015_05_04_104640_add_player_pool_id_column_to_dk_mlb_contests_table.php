<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlayerPoolIdColumnToDkMlbContestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('dk_mlb_contests', function($table) 
		{ 
			$table->integer('player_pool_id')->unsigned()->after('id'); 
		    $table->foreign('player_pool_id')->references('id')->on('player_pools');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('dk_mlb_contests', function($table)
		{
		    $table->dropColumn('player_pool_id');
		});
	}

}
