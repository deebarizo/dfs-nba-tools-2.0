<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('games', function($table)
		{
		    $table->increments('id');
		    $table->integer('season_id')->unsigned();
		    $table->foreign('season_id')->references('id')->on('seasons');
		    $table->date('date');
		    $table->string('link_br')->unique();
		    $table->integer('home_team_id')->unsigned();
		    $table->foreign('home_team_id')->references('id')->on('teams');
		    $table->integer('home_team_score')->unsigned();
		    $table->decimal('vegas_home_team_score', 5, 2);
		    $table->integer('road_team_id')->unsigned();
		    $table->foreign('road_team_id')->references('id')->on('teams');
		    $table->integer('road_team_score')->unsigned();
		    $table->decimal('vegas_road_team_score', 5, 2);
		    $table->decimal('pace', 4, 1);
		    $table->string('type');	  
		    $table->integer('ot_periods')->unsigned();
		    $table->text('notes')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('games');
	}

}
