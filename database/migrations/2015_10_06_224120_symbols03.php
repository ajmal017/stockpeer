<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Symbols03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Symbols', function($table)
		{  	    	    	   		        		
      $table->enum('SymbolsOptionType', [ 'Put', 'Call', '' ])->after('SymbolsType')->default('');     		
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
    	$table->dropColumn('SymbolsOptionType');      	 	    	   	      	   	    	    	     	    	  	    	    
		});
	}

}
