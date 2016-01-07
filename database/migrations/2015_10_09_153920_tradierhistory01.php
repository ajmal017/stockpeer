<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tradierhistory01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('TradierHistory', function($table)
		{
    	$table->increments('TradierHistoryId');
    	$table->integer('TradierHistoryAccountId')->index('TradierHistoryAccountId');
    	$table->string('TradierHistoryHash')->index('TradierHistoryHash');
    	$table->string('TradierHistoryType');   	
      $table->decimal('TradierHistoryAmount', 9, 2); 
      $table->date('TradierHistoryDate');
     	$table->text('TradierHistoryDetails');
     	$table->enum('TradierHistoryRecorded', [ 'Yes', 'No' ])->default('No');     	
    	$table->timestamp('TradierHistoryUpdatedAt');
    	$table->timestamp('TradierHistoryCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('TradierHistory');
	}

}
