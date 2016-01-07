<?php
use Illuminate\Database\Migrations\Migration;

class Shares01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Shares', function($table)
		{
    	$table->increments('SharesId');
    	$table->integer('SharesAccountId')->index('SharesAccountId');
			$table->date('SharesDate');    	
			$table->integer('SharesCount');
    	$table->decimal('SharesPrice', 9, 2);
     	$table->text('SharesNote');
    	$table->timestamp('SharesUpdatedAt');
    	$table->timestamp('SharesCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Shares');
	}

}