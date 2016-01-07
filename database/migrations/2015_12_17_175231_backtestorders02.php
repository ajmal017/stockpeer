<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtestorders02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('BackTestTrades');
		
		Schema::create('BackTestTrades', function($table)
		{
    	$table->increments('BackTestTradesId');
    	$table->integer('BackTestTradesAccountId')->index('BackTestTradesAccountId');
    	$table->integer('BackTestTradesTestId')->index('BackTestTradesTestId');
			$table->date('BackTestTradesOpen');      	
			$table->date('BackTestTradesClose'); 
			$table->decimal('BackTestTradesSymStart', 9, 2);
			$table->decimal('BackTestTradesSymEnd', 9, 2);
			$table->decimal('BackTestTradesSymDiff', 9, 2);	
			$table->decimal('BackTestTradesVixStart', 9, 2);
			$table->decimal('BackTestTradesVixEnd', 9, 2);	
			$table->decimal('BackTestTradesShortDeltaStart', 9, 2);
			$table->decimal('BackTestTradesShortDeltaEnd', 9, 2);				
			$table->decimal('BackTestTradesLongLeg1', 9, 2);
			$table->decimal('BackTestTradesLongLeg2', 9, 2);
			$table->decimal('BackTestTradesShortLeg1', 9, 2);
			$table->decimal('BackTestTradesShortLeg2', 9, 2);						
      $table->date('BackTestTradesExpire1'); 
      $table->date('BackTestTradesExpire2');       
      $table->integer('BackTestTradesLots');
      $table->enum('BackTestTradesTouched', [ 'Yes', 'No' ])->default('No');  
      $table->enum('BackTestTradesStopped', [ 'Yes', 'No' ])->default('No'); 
      $table->decimal('BackTestTradesOpenCredit', 9, 2);
      $table->decimal('BackTestTradesCloseCredit', 9, 2);      
      $table->decimal('BackTestTradesProfit', 9, 2);
      $table->decimal('BackTestTradesBalance', 9, 2);      
      $table->decimal('BackTestTradesCommissions', 9, 2);    	
    	$table->timestamp('BackTestTradesUpdatedAt');
    	$table->timestamp('BackTestTradesCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('BackTestTrades');
	}

}
