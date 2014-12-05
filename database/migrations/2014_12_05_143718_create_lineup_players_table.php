<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLineupPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('lineup_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('lineup_id')->unsigned();
		    $table->foreign('lineup_id')->references('id')->on('lineups');
		    $table->integer('player_fd_id')->unsigned();
		    $table->foreign('player_fd_id')->references('id')->on('players_fd');
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
		Schema::dropIfExists('lineup_players');
	}

}
