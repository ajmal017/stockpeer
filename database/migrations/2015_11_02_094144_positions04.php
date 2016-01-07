<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Positions04 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Positions', function($table)
		{  	    	    	   		        		
      $table->datetime('PositionsDateAcquired')->after('PositionsClosePrice');  		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Positions', function($table)
		{
    	$table->dropColumn('PositionsDateAcquired');     	     	 	    	   	      	   	    	    	     	    	  	    	    
		});
	}

}
