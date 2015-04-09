<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMlbBoxScoreLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mlb_box_score_lines', function($table)
		{
		    $table->increments('id');
		    $table->integer('mlb_game_id')->unsigned();
		    $table->foreign('mlb_game_id')->references('id')->on('mlb_games');		    
		    $table->integer('mlb_team_id')->unsigned();
		    $table->foreign('mlb_team_id')->references('id')->on('mlb_teams');
		    $table->integer('opp_mlb_team_id')->unsigned();
		    $table->foreign('opp_mlb_team_id')->references('id')->on('mlb_teams');
		    $table->integer('mlb_player_id')->unsigned();
		    $table->foreign('mlb_player_id')->references('id')->on('mlb_players');
		    $table->integer('singles')->unsigned();
		    $table->integer('doubles')->unsigned();
		    $table->integer('triples')->unsigned();
		    $table->integer('hr')->unsigned();
		    $table->integer('rbi')->unsigned();
		    $table->integer('runs')->unsigned();
		    $table->integer('bb')->unsigned();
		    $table->integer('hbp')->unsigned();
		    $table->integer('sb')->unsigned();
		    $table->integer('cs')->unsigned();
		    $table->decimal('ip', 3, 1);
		    $table->integer('so')->unsigned();
		    $table->integer('win')->unsigned();
		    $table->integer('er')->unsigned();
		    $table->integer('hits_against')->unsigned();
		    $table->integer('bb_against')->unsigned();
		    $table->integer('hbp_against')->unsigned();
		    $table->integer('cg')->unsigned();
		    $table->integer('cg_shutout')->unsigned();
		    $table->integer('no_hitter')->unsigned();
		    $table->decimal('fpts', 5, 2);
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
		Schema::dropIfExists('mlb_box_score_lines');
	}

}
