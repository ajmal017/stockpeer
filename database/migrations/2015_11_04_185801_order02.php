<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Order02 extends Migration {


	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Orders', function($table)
		{  	    	    	   		        		
      $table->enum('OrdersReviewed', [ 'Yes', 'No' ])->default('No')->after('OrdersStatus')->index('OrdersReviewed');
      $table->decimal('OrdersLeg1FilledPrice', 9, 2)->after('OrdersLeg1Side'); 
      $table->decimal('OrdersLeg2FilledPrice', 9, 2)->after('OrdersLeg2Side'); 
      $table->decimal('OrdersLeg3FilledPrice', 9, 2)->after('OrdersLeg3Side'); 
      $table->decimal('OrdersLeg4FilledPrice', 9, 2)->after('OrdersLeg4Side');                    		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Orders', function($table)
		{
    	$table->dropColumn('OrdersReviewed');
     	$table->dropColumn('OrdersLeg1FilledPrice');
     	$table->dropColumn('OrdersLeg2FilledPrice');
    	$table->dropColumn('OrdersLeg3FilledPrice');
    	$table->dropColumn('OrdersLeg4FilledPrice');    	     	    	   	     	     	 	    	   	      	   	    	    	     	    	  	    	    
		});
	}

}
