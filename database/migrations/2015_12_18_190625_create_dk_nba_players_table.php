<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDkNbaPlayersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dk_nba_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('player_pool_id')->unsigned();
		    $table->foreign('player_pool_id')->references('id')->on('player_pools');
		    $table->integer('player_id')->unsigned();
		    $table->foreign('player_id')->references('id')->on('players');
		    $table->integer('target_percentage'); 
		    $table->integer('team_id')->unsigned();
		    $table->foreign('team_id')->references('id')->on('teams');
		    $table->integer('opp_team_id')->unsigned();
		    $table->foreign('opp_team_id')->references('id')->on('teams');
		    $table->string('position');
		    $table->decimal('projected_fpts', 5, 2)->nullable();
		    $table->integer('salary');
		    $table->decimal('projected_vr', 4, 2)->nullable();
		    $table->decimal('actual_fpts', 5, 2)->nullable();
		    $table->decimal('actual_vr', 4, 2)->nullable();
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
		Schema::dropIfExists('dk_nba_players');
	}

}
