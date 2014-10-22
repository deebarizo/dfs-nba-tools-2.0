<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerPoolsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('player_pools', function($table)
		{
		    $table->increments('id');
		    $table->date('date');
		    $table->string('time_period');
		    $table->string('site');
		    $table->string('url');
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
		Schema::dropIfExists('player_pools');
	}

}
