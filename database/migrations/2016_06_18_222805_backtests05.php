<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtests05 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BackTests', function($table)
		{  	    
      $table->integer('BackTestsSpreadWidth')->after('BackTestsTradeSize')->default(2);  		                    		
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
    	$table->dropColumn('BackTestsSpreadWidth');    	    	    	    	    	  	    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
