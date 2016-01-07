<?php

use Illuminate\Database\Migrations\Migration;

class Trades01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Trades', function($table)
		{
    	$table->increments('TradesId');
    	$table->integer('TradesAccountId')->index('TradesAccountId');
     	$table->integer('TradesAssetsId')->index('TradesAssetsId');   	
			$table->string('TradesAsset');    	
			$table->integer('TradesShares'); 
			$table->date('TradesDateStart');
			$table->date('TradesDateEnd');			
    	$table->decimal('TradesStartPrice', 9, 2);
    	$table->decimal('TradesEndPrice', 9, 2);     	
    	$table->decimal('TradesStartCommission', 9, 2);
    	$table->decimal('TradesEndCommission', 9, 2);
			$table->enum('TradesStopped', [ 'Yes', 'No' ])->default('No');     	     	   	
     	$table->text('TradesNote');
    	$table->timestamp('TradesUpdatedAt');
    	$table->timestamp('TradesCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Trades');
	}

}