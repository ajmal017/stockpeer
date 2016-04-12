<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1minfutcl01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Data1MinFutCl', function($table)
		{
    	$table->bigIncrements('Data1MinFutClId');
			$table->decimal('Data1MinFutClOpen', 9, 2);
			$table->decimal('Data1MinFutClHigh', 9, 2);
			$table->decimal('Data1MinFutClLow', 9, 2);			
			$table->decimal('Data1MinFutClClose', 9, 2);
			$table->date('Data1MinFutClDate')->index('Data1MinFutClDate');
			$table->time('Data1MinFutClTime')->index('Data1MinFutClTime');
    	$table->timestamp('Data1MinFutClCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Data1MinFutCl');
	}

}
