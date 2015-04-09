<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMlbGamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mlb_games', function($table)
		{
		    $table->increments('id');
		    $table->integer('season_id')->unsigned();
		    $table->foreign('season_id')->references('id')->on('seasons');
		    $table->date('date');
		    $table->string('link_fg');
		    $table->unique('link_fg');
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
		Schema::dropIfExists('mlb_games');
	}

}
