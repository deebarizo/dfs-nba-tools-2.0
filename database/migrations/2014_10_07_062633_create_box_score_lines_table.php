<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBoxScoreLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('box_score_lines', function($table)
		{
		    $table->increments('id');
		    $table->integer('game_id')->unsigned();
		    $table->foreign('game_id')->references('id')->on('games');
		    $table->integer('team_id')->unsigned();
		    $table->foreign('team_id')->references('id')->on('teams');
		    $table->integer('player_id')->unsigned();
		    $table->foreign('player_id')->references('id')->on('players');
		    $table->text('role');
		    $table->decimal('mp', 4, 2);
		    $table->integer('fg')->unsigned();
    		$table->integer('fga')->unsigned();
    		$table->integer('threep')->unsigned();
    		$table->integer('threepa')->unsigned();
    		$table->integer('ft')->unsigned();
    		$table->integer('fta')->unsigned();
    		$table->integer('orb')->unsigned();
    		$table->integer('drb')->unsigned();
    		$table->integer('trb')->unsigned();
    		$table->integer('ast')->unsigned();
    		$table->integer('stl')->unsigned();
    		$table->integer('blk')->unsigned();
    		$table->integer('tov')->unsigned();
    		$table->integer('pf')->unsigned();
    		$table->integer('pts')->unsigned();
    		$table->integer('plus_minus');
    		$table->decimal('orb_percent', 4, 1);
    		$table->decimal('drb_percent', 4, 1);
    		$table->decimal('trb_percent', 4, 1);
    		$table->decimal('ast_percent', 4, 1);
    		$table->decimal('stl_percent', 4, 1);
    		$table->decimal('blk_percent', 4, 1);
    		$table->decimal('tov_percent', 4, 1);
    		$table->decimal('usg', 4, 1);
    		$table->integer('off_rating')->unsigned();
    		$table->integer('def_rating')->unsigned();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('box_score_lines');
	}

}
