<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Users03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Users', function($table)
		{  	    		          		 
      $table->integer('UsersDefaultPutCreditSpreadLots')->after('UsersTradierAccountId');                     		
      $table->decimal('UsersDefaultPutCreditSpreadCloseCredit', 9, 2)->after('UsersTradierAccountId'); 
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
    	$table->dropColumn('UsersDefaultPutCreditSpreadCloseCredit');     		    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}
	
}
