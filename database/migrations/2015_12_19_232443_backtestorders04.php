<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtestorders04 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BackTestTrades', function($table)
		{  	    		          		 
      $table->decimal('BackTestTradesSnpIvrEnd', 9, 2)->after('BackTestTradesVixEnd'); 
      $table->decimal('BackTestTradesSnpIvrStart', 9, 2)->after('BackTestTradesVixEnd');                     		
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
    	$table->dropColumn('BackTestTradesSnpIvrEnd'); 
    	$table->dropColumn('BackTestTradesSnpIvrStart');  	    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
