<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtests01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('BackTests');
		
		Schema::create('BackTests', function($table)
		{
    	$table->increments('BackTestsId');
    	$table->integer('BackTestsAccountId')->index('BackTestsAccountId');
    	$table->string('BackTestsName');  
			$table->enum('BackTestsType', [ 'Put Credit Spreads', 'Long Butterfly Spread' ])->default('Put Credit Spreads');
			$table->date('BackTestsStart');      	
			$table->date('BackTestsEnd'); 
			$table->decimal('BackTestsStartBalance', 9, 2);
			$table->string('BackTestsTradeSize');
 			$table->string('BackTestsCloseAt'); 			
 			$table->text('BackTestsStopAt');			   	  	
			$table->enum('BackTestsStatus', [ 'Pending', 'Started', 'Ended', 'Error', 'Broke' ])->default('Pending');
    	$table->timestamp('BackTestsUpdatedAt');
    	$table->timestamp('BackTestsCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('BackTests');
	}

}

/* End File */