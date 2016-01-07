<?php

use Illuminate\Database\Migrations\Migration;

class Assets01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Assets', function($table)
		{
    	$table->increments('AssetsId');
    	$table->integer('AssetsAccountId')->index('AssetsAccountId');
			$table->string('AssetsName');    	
			$table->string('AssetsAccount'); 
			$table->date('AssetsLastMark');
    	$table->decimal('AssetsValue', 9, 2);
     	$table->text('AssetsNote');
    	$table->timestamp('AssetsUpdatedAt');
    	$table->timestamp('AssetsCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Assets');
	}

}