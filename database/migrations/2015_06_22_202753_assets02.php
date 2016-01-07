<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Assets02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Assets', function($table)
		{  	    	    	
    	$table->enum('AssetsAutoUpdate', [ 'Yes', 'No' ])->after('AssetsValue')->default('No');
    	$table->text('AssetsAccountToken')->after('AssetsName');    	      	
    	$table->string('AssetsAccountNum')->after('AssetsName');    	
    	$table->enum('AssetsBroker', [ 'Tradier', 'Tradeking', 'Robinhood', 'Coinbase', 'Other' ])->after('AssetsName')->default('Other');
    	$table->dropColumn('AssetsAccount');      		
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Assets', function($table)
		{
    	$table->dropColumn('AssetsAccountToken'); 
    	$table->dropColumn('AssetsAutoUpdate');  
     	$table->dropColumn('AssetsAccountNum'); 
      $table->dropColumn('AssetsBroker');     	      	   	    	    	     	    	  	    	    	   	
		});
	}

}
