<?php

use Illuminate\Database\Migrations\Migration;

class Marks01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Marks', function($table)
		{
    	$table->increments('MarksId');
    	$table->integer('MarksAccountId')->index('MarksAccountId');   	   	
			$table->date('MarksDate');
    	$table->decimal('MarksValue', 9, 2);
    	$table->bigInteger('MarksShares');
    	$table->timestamp('MarksUpdatedAt');
    	$table->timestamp('MarksCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Marks');
	}

}