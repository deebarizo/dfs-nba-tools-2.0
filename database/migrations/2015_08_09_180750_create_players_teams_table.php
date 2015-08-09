<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayersTeamsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('players_teams', function($table)
		{
		    $table->increments('id');
		    $table->integer('player_id')->unsigned();
		    $table->foreign('player_id')->references('id')->on('players');
		    $table->integer('team_id')->unsigned();
		    $table->foreign('team_id')->references('id')->on('teams');
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
		Schema::dropIfExists('players_teams');
	}

}
