<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMlbPlayersTeamsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mlb_players_teams', function($table)
		{
		    $table->increments('id');
		    $table->integer('mlb_player_id')->unsigned();
		    $table->foreign('mlb_player_id')->references('id')->on('mlb_players');
		    $table->integer('mlb_team_id')->unsigned();
		    $table->foreign('mlb_team_id')->references('id')->on('mlb_teams');
			$table->date('start_date');
			$table->date('end_date');
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
		Schema::dropIfExists('mlb_players_teams');
	}

}
