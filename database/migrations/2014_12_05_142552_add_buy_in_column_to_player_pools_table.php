<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBuyInColumnToPlayerPoolsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('player_pools', function($table) 
		{ 
			$table->integer('buy_in')->after('url')->nullable(); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('players_pools', function($table)
		{
		    $table->dropColumn('buy_in');
		});
	}

}
