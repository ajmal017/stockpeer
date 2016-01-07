<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tradegoups01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
  	$types = [
    	'Other',
    	'Long Stock Trade',
    	'Short Stock Trade',
    	'Long Option Trade',
    	'Short Option Trade',
    	'Put Credit Spread',
    	'Put Debit Spread',
    	'Call Credit Spread',
    	'Put Debit Spread',
    	'Weekly Put Credit Spread'    	
  	];
  	
		Schema::table('Positions', function($table)
		{  	    	    	   		
      $table->integer('PositionsTradeGroupId')->index('PositionsTradeGroupId')->after('PositionsAccountId');   		
      $table->index('PositionsBrokerId');    		
		});  	
  	
		Schema::create('TradeGroups', function($table) use ($types)
		{
    	$table->increments('TradeGroupsId');
    	$table->integer('TradeGroupsAccountId')->index('TradeGroupsAccountId');
    	$table->string('TradeGroupsTitle');   	
      $table->decimal('TradeGroupsOpen', 9, 2); 
      $table->decimal('TradeGroupsClose', 9, 2); 
      $table->decimal('TradeGroupsOpenCommission', 9, 2); 
      $table->decimal('TradeGroupsCloseCommission', 9, 2); 
      $table->datetime('TradeGroupsStart');
      $table->datetime('TradeGroupsEnd');
     	$table->enum('TradeGroupsType', $types)->default('Other');
     	$table->enum('TradeGroupsStatus', [ 'Open', 'Closed' ])->default('Open');
     	$table->text('TradeGroupsNote');
    	$table->timestamp('TradeGroupsUpdatedAt');
    	$table->timestamp('TradeGroupsCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('TradeGroups');
	}

}
