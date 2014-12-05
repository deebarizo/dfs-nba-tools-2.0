<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalSalaryColumnToLineupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('lineups', function($table) 
		{ 
			$table->integer('total_salary')->after('hash'); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('lineups', function($table)
		{
		    $table->dropColumn('total_salary');
		});
	}

}
