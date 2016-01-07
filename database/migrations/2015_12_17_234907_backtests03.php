<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtests03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BackTests', function($table)
		{  	    
      $table->string('BackTestsPublicHash')->after('BackTestsStatus');
      $table->integer('BackTestsWins')->after('BackTestsCagr');
      $table->integer('BackTestsLosses')->after('BackTestsCagr');
      $table->integer('BackTestsTotalTrades')->after('BackTestsCagr');         		
      $table->decimal('BackTestsEndBalance', 9, 2)->after('BackTestsStartBalance');    		
      $table->decimal('BackTestsWinRate', 9, 2)->after('BackTestsCagr'); 
      $table->decimal('BackTestsAvgCredit', 9, 2)->after('BackTestsCagr'); 
      $table->decimal('BackTestsAvgDaysInTrade', 9, 2)->after('BackTestsCagr');                 		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('BackTests', function($table)
		{
    	$table->dropColumn('BackTestsPublicHash'); 
    	$table->dropColumn('BackTestsWins'); 
    	$table->dropColumn('BackTestsLosses'); 
    	$table->dropColumn('BackTestsTotalTrades'); 
     	$table->dropColumn('BackTestsEndBalance'); 
    	$table->dropColumn('BackTestsWinRate'); 
    	$table->dropColumn('BackTestsAvgCredit'); 
    	$table->dropColumn('BackTestsAvgDaysInTrade');    	    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
