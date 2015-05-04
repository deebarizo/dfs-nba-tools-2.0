<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDkMlbContestLineupPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dk_mlb_contest_lineup_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('dk_mlb_player_id')->unsigned();
		    $table->foreign('dk_mlb_player_id')->references('id')->on('dk_mlb_players');
		    $table->integer('mlb_player_id')->unsigned();
		    $table->foreign('mlb_player_id')->references('id')->on('mlb_players');
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
		Schema::dropIfExists('dk_mlb_contest_lineup_players');
	}

}
