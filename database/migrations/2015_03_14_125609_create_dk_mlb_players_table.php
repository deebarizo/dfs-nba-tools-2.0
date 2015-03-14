<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDkMlbPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dk_mlb_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('player_pool_id')->unsigned();
		    $table->foreign('player_pool_id')->references('id')->on('player_pools');
		    $table->integer('mlb_player_id')->unsigned();
		    $table->foreign('mlb_player_id')->references('id')->on('mlb_players');
		    $table->integer('target_percentage'); 
		    $table->integer('mlb_team_id')->unsigned();
		    $table->foreign('mlb_team_id')->references('id')->on('mlb_teams');
		    $table->integer('mlb_opp_team_id')->unsigned();
		    $table->foreign('mlb_opp_team_id')->references('id')->on('mlb_teams');
		    $table->string('position');
		    $table->integer('salary');
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
		Schema::dropIfExists('dk_mlb_players');
	}

}
