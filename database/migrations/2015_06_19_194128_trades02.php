<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Trades02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Trades', function($table)
		{
    	$table->enum('TradesType', [ 'Put Credit Spread', 'Weekly Put Credit', 'Iron Condor', 'Day Trade', 'Stock', 'Other' ])->after('TradesAssetsId')->default('Other')->index('TradesType');
    	$table->date('TradeExpiration')->after('TradesStopped');
    	$table->string('TradesStock')->after('TradesStopped');
    	$table->decimal('TradesShortLeg1', 9, 2)->after('TradesStopped');
    	$table->decimal('TradesLongLeg1', 9, 2)->after('TradesStopped');
    	$table->decimal('TradesShortLeg2', 9, 2)->after('TradesStopped');
    	$table->decimal('TradesLongLeg2', 9, 2)->after('TradesStopped');    	 
    	$table->decimal('TradesCredit', 9, 2)->after('TradesStopped');
     	$table->decimal('TradesSpreadWidth2', 9, 2)->after('TradesStopped');  
     	$table->decimal('TradesSpreadWidth1', 9, 2)->after('TradesStopped');  	  	    	    	
    	$table->enum('TradesStatus', [ 'Open', 'Closed' ])->after('TradesNote')->default('Closed');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Trades', function($table)
		{
    	$table->dropColumn('TradeExpiration');
    	$table->dropColumn('TradesStock');
    	$table->dropColumn('TradesType');
     	$table->dropColumn('TradesShortLeg1');
     	$table->dropColumn('TradesLongLeg1');
     	$table->dropColumn('TradesShortLeg2');
     	$table->dropColumn('TradesLongLeg2');  
      $table->dropColumn('TradesCredit');  
      $table->dropColumn('TradesSpreadWidth2');  
      $table->dropColumn('TradesSpreadWidth1');  
      $table->dropColumn('TradesStatus');         	    	     	    	  	    	    	   	
		});
	}

}
