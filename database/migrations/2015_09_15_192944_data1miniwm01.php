<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1miniwm01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Data1MinIwm', function($table)
		{
    	$table->bigIncrements('Data1MinIwmId');
			$table->decimal('Data1MinIwmLast', 9, 2);
			$table->bigInteger('Data1MinIwmVol');
			$table->decimal('Data1MinIwmChange', 9, 2);
			$table->date('Data1MinIwmDate')->index('Data1MinIwmDate');
			$table->time('Data1MinIwmTime');
    	$table->timestamp('Data1MinIwmCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Data1MinIwm');
	}

}
