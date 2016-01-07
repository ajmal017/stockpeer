<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Fiveminspy01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('5MinSpy', function($table)
		{
    	$table->bigIncrements('5MinSpyId');
			$table->decimal('5MinSpyStockLast', 9, 2);
			$table->decimal('5MinSpyAsk', 9, 2);
			$table->decimal('5MinSpyBid', 9, 2);
			$table->integer('5MinSpyDaysToExpire');
			$table->decimal('5MinSpyImpVol', 9, 6);
			$table->bigInteger('5MinSpyTimeStamp')->index('5MinSpyTimeStamp');
			$table->decimal('5MinSpyLast', 9, 2);
			$table->bigInteger('5MinSpyOpenInterest');
			$table->string('5MinSpyType', 10)->index('5MinSpyType');
			$table->decimal('5MinSpyStrike', 9, 2)->index('5MinSpyStrike');
			$table->string('5MinSpyTradeTrend', 10);
			$table->string('5MinSpyTradeTick', 1);	
    	$table->timestamp('5MinSpyCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('5MinSpy');
	}

}
