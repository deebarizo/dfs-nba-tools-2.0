<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDkMlbContestPlayersTable extends Migration {

	public function up()
	{
		Schema::create('dk_mlb_contest_players', function($table)
		{
		    $table->increments('id');
		    $table->integer('dk_mlb_contest_id')->unsigned();
		    $table->foreign('dk_mlb_contest_id')->references('id')->on('dk_mlb_contests');	
		    $table->integer('dk_mlb_player_id')->unsigned();
		    $table->foreign('dk_mlb_player_id')->references('id')->on('dk_mlb_players');
		    $table->decimal('ownership', 4, 1);
		    $table->decimal('total_ownership', 4, 1);
		    $table->string('other_position')->nullable();
		    $table->decimal('other_position_ownership', 4, 1);
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
		Schema::dropIfExists('dk_mlb_contest_players');
	}

}
