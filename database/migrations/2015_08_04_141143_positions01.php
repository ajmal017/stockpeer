<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Positions01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('Positions');
		
		Schema::create('Positions', function($table)
		{
    	$table->increments('PositionsId');
    	$table->integer('PositionsAccountId')->index('PositionsAccountId');
    	$table->integer('PositionsAssetId')->index('PositionsAssetId');
    	$table->string('PositionsBrokerId'); 
    	$table->integer('PositionsSymbolId')->index('PositionsSymbolId');    	
			$table->enum('PositionsType', [ 'Stock', 'Option', 'Credit Spread', 'Debit Spread', 'Other' ])->default('Stock');
			$table->integer('PositionsQty');	    	
      $table->decimal('PositionsCostBasis', 9, 2); 
			$table->decimal('PositionsAvgPrice', 9, 2);
     	$table->enum('PositionsStatus', [ 'Open', 'Closed' ])->default('Open');
     	$table->text('PositionsNote');
    	$table->timestamp('PositionsUpdatedAt');
    	$table->timestamp('PositionsCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Positions');
	}

}