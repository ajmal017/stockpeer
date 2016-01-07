<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Daytrades01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('DayTrades', function($table)
		{
    	$table->increments('DayTradesId');
    	$table->integer('DayTradesAccountId')->index('DayTradesAccountId');   	    	
    	$table->integer('DayTradesSymbolsId')->index('DayTradesSymbolsId'); 
      $table->enum('DayTradesStatus', [ 'Pending', 'Open', 'Closed' ])->default('Pending');    	
    	$table->enum('DayTradesType', [ 'Long Stock', 'Short Stock' ])->default('Long Stock');
			$table->integer('DayTradesQty');
      $table->date('DayTradesDate')->index('DayTradesDate');    	
      $table->time('DayTradesOpenTime');
      $table->time('DayTradesCloseTime');      
      $table->decimal('DayTradesOpenPrice', 9, 2);
      $table->decimal('DayTradesClosePrice', 9, 2);
      $table->decimal('DayTradesOpenCommission', 9, 2);
      $table->decimal('DayTradesCloseCommission', 9, 2);
      $table->decimal('DayTradesProfit', 9, 2);     	
     	$table->text('DayTradesNote');
    	$table->timestamp('DayTradesUpdatedAt');
    	$table->timestamp('DayTradesCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('DayTrades');
	}

}
