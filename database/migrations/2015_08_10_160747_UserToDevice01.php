<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserToDevice01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('UserToDevice');
		
		Schema::create('UserToDevice', function($table)
		{
    	$table->increments('UserToDeviceId');
    	$table->integer('UserToDeviceAccountId')->index('UserToDeviceAccountId');
    	$table->text('UserToDeviceGcmEndPoint');   
    	$table->text('UserToDeviceAppleToken'); 	
			$table->enum('UserToDeviceType', [ 'GCM Browser', 'Apple Push', 'Other' ])->default('Other');
    	$table->timestamp('UserToDeviceUpdatedAt');
    	$table->timestamp('UserToDeviceCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('UserToDevice');
	}

}
