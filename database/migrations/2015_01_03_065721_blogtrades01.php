<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Blogtrades01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('BlogTrades', function($table)
		{
    	$table->increments('BlogTradesId');
    	$table->string('BlogTradesTitle');
			$table->string('BlogTradesTicker'); 
     	$table->enum('BlogTradesPortfolio', [ 'Index Credit Spreads' ])->default('Index Credit Spreads');
     	$table->enum('BlogTradesType', [ 'Put', 'Call' ])->default('Put');      	 
     	$table->datetime('BlogTradesExpireDate');      	  	
    	$table->datetime('BlogTradesOpenDate');
    	$table->datetime('BlogTradesCloseDate');    	  	
    	$table->decimal('BlogTradesBuyStrike', 12, 2);  
    	$table->decimal('BlogTradesSellStrike', 12, 2); 
    	$table->decimal('BlogTradesOpenCredit', 12, 2);  
    	$table->decimal('BlogTradesCloseDebit', 12, 2);  
    	$table->text('BlogTradesNote');
     	$table->enum('BlogTradesStatus', [ 'Active', 'Disabled' ])->default('Active');     	
    	$table->integer('BlogTradesOrder');
    	$table->timestamp('BlogTradesUpdatedAt'); 
    	$table->timestamp('BlogTradesCreatedAt');     	   	     	   	    	    	    	    	
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('BlogTrades');
	}

}
