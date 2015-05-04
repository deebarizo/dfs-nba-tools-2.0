<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDkMlbContestLineupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dk_mlb_contest_lineups', function($table)
		{
		    $table->increments('id');
		    $table->integer('dk_mlb_contest_id')->unsigned();
		    $table->foreign('dk_mlb_contest_id')->references('id')->on('dk_mlb_contests');
		    $table->integer('rank');
		    $table->string('username');
		    $table->decimal('fpts', 6, 2);
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
		Schema::dropIfExists('dk_mlb_contest_lineups');
	}

}
