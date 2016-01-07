<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtests04 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('BackTests', function($table)
		{  	    
      $table->integer('BackTestsMaxDaysExpire')->after('BackTestsTradeSize');  		
      $table->integer('BackTestsMinDaysExpire')->after('BackTestsTradeSize');
      $table->decimal('BackTestOpenPercentAway', 9, 2)->after('BackTestsTradeSize');       
      $table->string('BackTestsOpenAt')->after('BackTestsCloseAt'); 
      
      $table->decimal('BackTestsMinOpenCredit', 9, 2)->after('BackTestsTradeSize');
      $table->enum('BackTestsOneTradeAtTime', [ 'Yes', 'No' ])->default('No')->after('BackTestsTradeSize');
      $table->string('BackTestsTradeSelect')->after('BackTestsTradeSize');                      		
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
    	$table->dropColumn('BackTestsMaxDaysExpire'); 
    	$table->dropColumn('BackTestsMinDaysExpire'); 
    	$table->dropColumn('BackTestsOpenAt'); 
    	$table->dropColumn('BackTestOpenPercentAway'); 
    	$table->dropColumn('BackTestsMinOpenCredit'); 
    	$table->dropColumn('BackTestsOneTradeAtTime'); 
    	$table->dropColumn('BackTestsTradeSelect');     	    	    	    	    	  	    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
