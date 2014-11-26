<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProjectionColumnsToPlayersFdTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('players_fd', function($table) 
		{ 
			$table->decimal('vr_minus1', 5, 2)->after('updated_at')->nullable(); 
			$table->decimal('fppg_minus1', 5, 2)->after('vr_minus1')->nullable(); 
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('players_fd', function($table)
		{
		    $table->dropColumn('vr_minus1');
		    $table->dropColumn('fppg_minus1');
		});
	}

}
