<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Users01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Users', function($table)
		{
    	$table->increments('UsersId');
    	$table->string('UsersFirst');
    	$table->string('UsersLast');
    	$table->string('UsersEmail');
    	$table->text('UsersWatchList');    	
    	$table->string('UsersPassword', 500);
    	$table->string('UsersWebSocketKey');    	
    	$table->string('UsersTradierToken', 500);
    	$table->string('UsersTradierAccountId');    	
    	$table->timestamp('UsersUpdatedAt'); 
    	$table->timestamp('UsersCreatedAt');     	   	     	   	    	    	    	    	
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Users');
	}
}

/* End File */