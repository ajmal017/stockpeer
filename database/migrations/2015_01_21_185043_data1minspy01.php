<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Data1minspy01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Data1MinSpy', function($table)
		{
    	$table->bigIncrements('Data1MinSpyId');
			$table->decimal('Data1MinSpyLast', 9, 2);
			$table->bigInteger('Data1MinSpyVol');
			$table->date('Data1MinSpyDate')->index('Data1MinSpyDate');
			$table->time('Data1MinSpyTime');
    	$table->timestamp('Data1MinSpyCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Data1MinSpy');
	}

}
