<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLineupDkMlbPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('lineup_dk_mlb_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('lineup_id')->unsigned();
		    $table->foreign('lineup_id')->references('id')->on('lineups');
		    $table->integer('mlb_player_id')->unsigned();
		    $table->foreign('mlb_player_id')->references('id')->on('mlb_players');
		    $table->string('position');
		    $table->date('created_at');
		    $table->date('updated_at');
		});		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('lineup_dk_mlb_players');
	}

}
