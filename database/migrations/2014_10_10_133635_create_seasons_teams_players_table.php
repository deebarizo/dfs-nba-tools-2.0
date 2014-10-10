<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeasonsTeamsPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('seasons_teams_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('team_id')->unsigned();
		    $table->foreign('team_id')->references('id')->on('teams');
		    $table->integer('player_id')->unsigned();
		    $table->foreign('player_id')->references('id')->on('players');
		    $table->integer('season_id')->unsigned();
		    $table->foreign('season_id')->references('id')->on('seasons');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('seasons_teams_players');
	}

}
