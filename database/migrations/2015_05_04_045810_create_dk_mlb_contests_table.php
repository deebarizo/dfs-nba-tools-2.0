<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDkMlbContestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dk_mlb_contests', function($table)
		{
		    $table->increments('id');
		    $table->date('date');
		    $table->string('name');
		    $table->decimal('entry_fee', 8, 2);
		    $table->string('time_period');
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
		Schema::dropIfExists('dk_mlb_contests');
	}

}
