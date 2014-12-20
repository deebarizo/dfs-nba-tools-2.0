<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuyInPercentagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('buy_in_percentages', function($table)
		{
		    $table->increments('id');
		    $table->integer('percentage')->unsigned;
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
		Schema::dropIfExists('buy_in_percentages');
	}
}
