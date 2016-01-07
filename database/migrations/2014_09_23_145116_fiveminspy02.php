<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Fiveminspy02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('5MinSpy', function($table)
		{
    	$table->date('5MinSpyExpireDate')->after('5MinSpyBid')->index('5MinSpyExpireDate');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('5MinSpy', function($table)
		{
    	$table->dropColumn('5MinSpyExpireDate');
		});
	}

}
