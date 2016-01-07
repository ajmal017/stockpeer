<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Options1minspy01 extends Migration 
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Options1MinSpy', function($table)
		{
    	$table->bigIncrements('Options1MinSpyId');
      $table->integer('Options1MinSpySymbolId')->index('Options1MinSpySymbolId');      
      $table->decimal('Options1MinSpyLast', 9, 2);
      $table->decimal('Options1MinSpyBid', 9, 2); 	
      $table->decimal('Options1MinSpyAsk', 9, 2);
      $table->integer('Options1MinSpyBidSize'); 	
      $table->integer('Options1MinSpyAskSize');
      $table->decimal('Options1MinSpyUnderlyingLast', 9, 2);      
      $table->bigInteger('Options1MinSpyOpenInterest');
			$table->date('Options1MinSpyDate')->index('Options1MinSpyDate');
			$table->time('Options1MinSpyTime');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Options1MinSpy');
	}

}
