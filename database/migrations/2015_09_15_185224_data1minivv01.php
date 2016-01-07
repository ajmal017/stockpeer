<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1minivv01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Data1MinIvv', function($table)
		{
    	$table->bigIncrements('Data1MinIvvId');
			$table->decimal('Data1MinIvvLast', 9, 2);
			$table->bigInteger('Data1MinIvvVol');
			$table->decimal('Data1MinIvvChange', 9, 2);
			$table->date('Data1MinIvvDate')->index('Data1MinIvvDate');
			$table->time('Data1MinIvvTime');
    	$table->timestamp('Data1MinIvvCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Data1MinIvv');
	}
}
