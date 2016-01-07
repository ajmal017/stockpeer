<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Arbtest01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('ArbTest');
		
		Schema::create('ArbTest', function($table)
		{
    	$table->increments('ArbTestId');
    	$table->enum('ArbTestSpyShort', [ 'Yes', 'No' ])->default('No');
    	$table->decimal('ArbTestDiff', 9, 2);
      $table->decimal('ArbTestSpyOpen', 9, 2);
      $table->decimal('ArbTestSpyClose', 9, 2);
      $table->decimal('ArbTestIvvOpen', 9, 2);
      $table->decimal('ArbTestIvvClose', 9, 2);            
    	$table->enum('ArbTestStatus', [ 'Open', 'Closed' ])->default('Open');    	
    	$table->timestamp('ArbTestUpdatedAt');
    	$table->timestamp('ArbTestCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('ArbTest');
	}

}
