<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtestorders03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BackTestTrades', function($table)
		{  	  
      $table->dropColumn('BackTestTradesShortDeltaStart'); 
      $table->dropColumn('BackTestTradesShortDeltaEnd');       
  		          		 
      $table->decimal('BackTestTradesShortDeltaEnd2', 9, 2)->after('BackTestTradesVixEnd'); 
      $table->decimal('BackTestTradesShortDeltaEnd1', 9, 2)->after('BackTestTradesVixEnd');
      $table->decimal('BackTestTradesShortDeltaStart2', 9, 2)->after('BackTestTradesVixEnd');    		
      $table->decimal('BackTestTradesShortDeltaStart1', 9, 2)->after('BackTestTradesVixEnd');                       		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('BackTestTrades', function($table)
		{
      $table->decimal('BackTestTradesShortDeltaStart', 9, 2)->after('BackTestsStartBalance');    		
      $table->decimal('BackTestTradesShortDeltaEnd', 9, 2)->after('BackTestsCagr'); 
      
    	$table->dropColumn('BackTestTradesShortDeltaStart1'); 
    	$table->dropColumn('BackTestTradesShortDeltaStart2'); 
    	$table->dropColumn('BackTestTradesShortDeltaEnd1'); 
    	$table->dropColumn('BackTestTradesShortDeltaEnd2');    	    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
