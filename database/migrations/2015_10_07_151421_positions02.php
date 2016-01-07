<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Positions02 extends Migration {


	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Positions', function($table)
		{  	    	    	   		        		
      $table->decimal('PositionsClosePrice', 9, 2)->after('PositionsAvgPrice'); 
      $table->datetime('PositionsClosed')->after('PositionsNote');     		
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
    	$table->dropColumn('PositionsClosePrice'); 
    	$table->dropColumn('PositionsClosed');     	     	 	    	   	      	   	    	    	     	    	  	    	    
		});
	}

}
