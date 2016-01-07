<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1min03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Data1MinSpy', function($table)
		{  	    	    	   		
      $table->decimal('Data1MinSpyAsk', 9, 2)->after('Data1MinSpyLast');   		
      $table->decimal('Data1MinSpyBid', 9, 2)->after('Data1MinSpyLast');     		
		});
		
		Schema::table('Data1MinVix', function($table)
		{  	    	    	   		
      $table->decimal('Data1MinVixAsk', 9, 2)->after('Data1MinVixLast');   		
      $table->decimal('Data1MinVixBid', 9, 2)->after('Data1MinVixLast');     		
		});
		
		Schema::table('Data1MinIwm', function($table)
		{  	    	    	   		
      $table->decimal('Data1MinIwmAsk', 9, 2)->after('Data1MinIwmLast');   		
      $table->decimal('Data1MinIwmBid', 9, 2)->after('Data1MinIwmLast');     		
		});
		
		Schema::table('Data1MinIvv', function($table)
		{  	    	    	   		
      $table->decimal('Data1MinIvvAsk', 9, 2)->after('Data1MinIvvLast');   		
      $table->decimal('Data1MinIvvBid', 9, 2)->after('Data1MinIvvLast');     		
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
    	$table->dropColumn('Data1MinSpyAsk');
     	$table->dropColumn('Data1MinSpyBid');   	    	   	      	   	    	    	     	    	  	    	    	   	
		});
		
		Schema::table('Data1MinVix', function($table)
		{
    	$table->dropColumn('Data1MinVixAsk');
     	$table->dropColumn('Data1MinVixBid');   	    	   	      	   	    	    	     	    	  	    	    	   	
		});
		
		Schema::table('Data1MinIwm', function($table)
		{
    	$table->dropColumn('Data1MinIwmAsk');
     	$table->dropColumn('Data1MinIwmBid');   	    	   	      	   	    	    	     	    	  	    	    	   	
		});	
		
		Schema::table('Data1MinIvv', function($table)
		{
    	$table->dropColumn('Data1MinIvvAsk');
     	$table->dropColumn('Data1MinIvvBid');   	    	   	      	   	    	    	     	    	  	    	    	   	
		});						
	}

}
