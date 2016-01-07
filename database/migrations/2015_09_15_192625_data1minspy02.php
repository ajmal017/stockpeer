<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1minspy02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Data1MinSpy', function($table)
		{  	    	    	   		
      $table->decimal('Data1MinSpyChange', 9, 2)->after('Data1MinSpyVol');     		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Data1MinSpy', function($table)
		{
    	$table->dropColumn('Data1MinSpyChange');    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
