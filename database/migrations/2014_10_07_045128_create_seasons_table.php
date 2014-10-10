<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeasonsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('seasons', function($table)
		{
		    $table->increments('id');
		    $table->integer('start_year')->unsigned;
		    $table->integer('end_year')->unsigned;
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
		Schema::dropIfExists('seasons');
	}

}
