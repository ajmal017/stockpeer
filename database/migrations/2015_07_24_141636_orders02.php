<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Orders02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('Orders');
		
		Schema::create('Orders', function($table)
		{
    	$table->increments('OrdersId');
    	$table->integer('OrdersAccountId')->index('OrdersAccountId');
    	$table->integer('OrdersAssetId')->index('OrdersAssetId');
			$table->string('OrdersBrokerOrderId');			
			$table->string('OrdersType');
			$table->enum('OrdersSide', [ 'Buy', 'Sell', 'Buy To Open', 'Sell To Open', 'Buy To Close', 'Sell To Close' ])->default('Buy');			
			$table->string('OrdersSymbol');
			$table->string('OrdersQty');
			$table->decimal('OrdersPrice', 9, 2);
			$table->decimal('OrdersFilledPrice', 9, 2);			
			$table->enum('OrdersDuration', [ 'GTC', 'Day' ])->default('GTC');
			$table->datetime('OrdersEntered');
			$table->string('OrdersClass');
			$table->integer('OrdersLegs');																		
			$table->string('OrdersStrategy');
			$table->string('OrdersLeg1Symbol');
			$table->string('OrdersLeg1OptionSymbol');
			$table->string('OrdersLeg1Qty');
			$table->string('OrdersLeg1Side');						
			$table->string('OrdersLeg2Symbol');
			$table->string('OrdersLeg2OptionSymbol');
			$table->string('OrdersLeg2Qty');
			$table->string('OrdersLeg2Side');	
			$table->string('OrdersLeg3Symbol');
			$table->string('OrdersLeg3OptionSymbol');
			$table->string('OrdersLeg3Qty');
			$table->string('OrdersLeg3Side');	
			$table->string('OrdersLeg4Symbol');
			$table->string('OrdersLeg4OptionSymbol');
			$table->string('OrdersLeg4Qty');
			$table->string('OrdersLeg4Side');
     	$table->enum('OrdersStatus', [ 'Open', 'Filled', 'Partial', 'Canceled', 'Pending' ])->default('Open');
     	$table->text('OrdersNote');
    	$table->timestamp('OrdersUpdatedAt');
    	$table->timestamp('OrdersCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Orders');
	}

}
