<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tradegroups02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('TradeGroups', function($table)
		{  	    	    	   		        		
      $table->decimal('TradeGroupsRisked', 9, 2)->after('TradeGroupsClose');     		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('TradeGroups', function($table)
		{
    	$table->dropColumn('TradeGroupsRisked');      	 	    	   	      	   	    	    	     	    	  	    	    
		});
	}

}
