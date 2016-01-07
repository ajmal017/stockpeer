<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CurrentOptions01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('CurrentOptions', function($table)
		{
    	$table->bigIncrements('CurrentOptionsId');
			$table->decimal('CurrentOptionsStockLast', 9, 2);
			$table->decimal('CurrentOptionsAsk', 9, 2);
			$table->decimal('CurrentOptionsBid', 9, 2);
			$table->integer('CurrentOptionsDaysToExpire');
			$table->decimal('CurrentOptionsImpVol', 9, 6);
			$table->bigInteger('CurrentOptionsTimeStamp');
			$table->decimal('CurrentOptionsLast', 9, 2);
			$table->bigInteger('CurrentOptionsOpenInterest');
			$table->string('CurrentOptionsType', 10)->index('CurrentOptionsType');
			$table->decimal('CurrentOptionsStrike', 9, 2)->index('CurrentOptionsStrike');
			$table->string('CurrentOptionsSymbol', 10)->index('CurrentOptionsSymbol');
			$table->decimal('CurrentOptionsPremMult', 9, 2);
			$table->decimal('CurrentOptionsWk52Hi', 9, 2);
			$table->bigInteger('CurrentOptionsWk52HiDate');
			$table->decimal('CurrentOptionsWk52Lo', 9, 2);
			$table->bigInteger('CurrentOptionsWk52LoDate');
			$table->bigInteger('CurrentOptionsXdate')->index('CurrentOptionsXdate');
			$table->integer('CurrentOptionsXday')->index('CurrentOptionsXday');
			$table->integer('CurrentOptionsXmonth')->index('CurrentOptionsXmonth');
			$table->integer('CurrentOptionsXyear')->index('CurrentOptionsXyear');
			$table->string('CurrentOptionsTradeTick', 50);
    	$table->timestamp('CurrentOptionsUpdatedAt');
    	$table->timestamp('CurrentOptionsCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('CurrentOptions');
	}

}
