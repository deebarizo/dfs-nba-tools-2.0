<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLineupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('lineups', function($table)
		{
		    $table->increments('id');
		    $table->integer('player_pool_id')->unsigned();
		    $table->foreign('player_pool_id')->references('id')->on('player_pools');
		    $table->integer('hash');
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
		Schema::dropIfExists('lineups');
	}

}
