<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtests02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BackTests', function($table)
		{  	    	    	
      $table->datetime('BackTestsClockEnd')->after('BackTestsStopAt');  		
      $table->datetime('BackTestsClockStart')->after('BackTestsStopAt');          		
      $table->decimal('BackTestsCagr', 9, 2)->after('BackTestsStopAt');    		
      $table->decimal('BackTestsProfit', 9, 2)->after('BackTestsStopAt');     		
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
    	$table->dropColumn('BackTestsProfit'); 
    	$table->dropColumn('BackTestsCagr'); 
    	$table->dropColumn('BackTestsClockStart'); 
    	$table->dropColumn('BackTestsClockEnd');     	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
