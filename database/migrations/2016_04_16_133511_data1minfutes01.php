<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1minfutes01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Data1MinFutEs', function($table)
		{
    	$table->bigIncrements('Data1MinFutEsId');
			$table->decimal('Data1MinFutEsOpen', 9, 2);
			$table->decimal('Data1MinFutEsHigh', 9, 2);
			$table->decimal('Data1MinFutEsLow', 9, 2);			
			$table->decimal('Data1MinFutEsClose', 9, 2);
			$table->date('Data1MinFutEsDate')->index('Data1MinFutEsDate');
			$table->time('Data1MinFutEsTime')->index('Data1MinFutEsTime');
    	$table->timestamp('Data1MinFutEsCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Data1MinFutEs');
	}

}
