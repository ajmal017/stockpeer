<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1minvix01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Data1MinVix', function($table)
		{
    	$table->bigIncrements('Data1MinVixId');
			$table->decimal('Data1MinVixLast', 9, 2);
			$table->bigInteger('Data1MinVixVol');
			$table->decimal('Data1MinVixChange', 9, 2);
			$table->date('Data1MinVixDate')->index('Data1MinVixDate');
			$table->time('Data1MinVixTime');
    	$table->timestamp('Data1MinVixCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Data1MinVix');
	}
}
