<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMlbGameLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mlb_game_lines', function($table)
		{
		    $table->increments('id');
		    $table->integer('mlb_game_id')->unsigned();
		    $table->foreign('mlb_game_id')->references('id')->on('mlb_games');
		    $table->boolean('home');
		    $table->boolean('road');
		    $table->integer('mlb_team_id')->unsigned();
		    $table->foreign('mlb_team_id')->references('id')->on('mlb_teams');
		    $table->integer('score')->unsigned();
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
		Schema::dropIfExists('mlb_game_lines');
	}

}
