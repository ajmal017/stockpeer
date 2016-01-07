<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Orders01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Orders', function($table)
		{
    	$table->increments('OrdersId');
    	$table->integer('OrdersAccountId')->index('OrdersAccountId');
			$table->string('OrdersAsset');
			$table->string('OrdersType', 10);
			$table->decimal('OrdersStrike', 9, 2);
			$table->bigInteger('OrdersExpire');
    	$table->integer('OrdersContracts');			
			$table->enum('OrdersStatus', [ 'pending', 'filled', 'sold' ]);			   	   	
    	$table->decimal('OrdersPrice', 9, 2);
    	$table->decimal('OrdersLimit', 9, 2);
    	$table->decimal('OrdersSell', 9, 2);
    	$table->decimal('OrdersSold', 9, 2);     	    	    	
     	$table->text('OrdersNote');
    	$table->timestamp('OrdersUpdatedAt');
    	$table->timestamp('OrdersCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Orders');
	}

}
