<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Symbols02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Symbols', function($table)
		{  	    	    	   		
      $table->date('SymbolsExpire')->after('SymbolsFull');   		
      $table->string('SymbolsUnderlying')->after('SymbolsFull'); 
      $table->decimal('SymbolsStrike', 9, 2)->after('SymbolsFull');         		
      $table->enum('SymbolsType', [ 'Stock', 'Option', 'Other' ])->after('SymbolsFull')->default('Stock');     		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Symbols', function($table)
		{
    	$table->dropColumn('SymbolsExpire');
     	$table->dropColumn('SymbolsUnderlying');  
    	$table->dropColumn('SymbolsStrike');
     	$table->dropColumn('SymbolsType');       	 	    	   	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
