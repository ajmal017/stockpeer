<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Symbols04 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Symbols', function($table)
		{  	    	    	   		        		
      $table->string('SymbolsNameAlt1')->after('SymbolsExpire');     		
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
    	$table->dropColumn('SymbolsNameAlt1');      	 	    	   	      	   	    	    	     	    	  	    	    
		});
	}

}
