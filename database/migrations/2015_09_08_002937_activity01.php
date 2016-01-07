<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Activity01 extends Migration {


	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('Activity');
		
		Schema::create('Activity', function($table)
		{
    	$table->increments('ActivityId');
    	$table->integer('ActivityAccountId')->index('ActivityAccountId');
    	$table->string('ActivityType');
      $table->integer('ActivityTypeId');    	
    	$table->string('ActivityText');
    	$table->timestamp('ActivityUpdatedAt');
    	$table->timestamp('ActivityCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Activity');
	}

}
