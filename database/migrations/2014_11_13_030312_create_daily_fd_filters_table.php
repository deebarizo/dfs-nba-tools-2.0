<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyFdFiltersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('daily_fd_filters', function($table)
		{
		    $table->increments('id');
		    $table->integer('player_id')->unsigned();
		    $table->foreign('player_id')->references('id')->on('players');
		    $table->boolean('filter');
		    $table->boolean('playing');
   		    $table->string('fppg_source')->nullable();
   		    $table->string('fppm source')->nullable();
   		    $table->string('cv_source')->nullable();
   		    $table->decimal('mp_ot_filter', 4, 2)->nullable();
   		    $table->integer('dnp_games');
   		    $table->text('notes')->nullable();
		    $table->dateTime('created_at');
		    $table->dateTime('updated_at');
		});		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('daily_fd_filters');
	}

}
