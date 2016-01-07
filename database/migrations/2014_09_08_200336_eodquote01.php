<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Eodquote01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('EodQuote', function($table)
		{
    	$table->bigIncrements('EodQuoteId');
    	$table->integer('EodQuoteSymbolId')->index('EodQuoteSymbolId');
    	$table->date('EodQuoteDate')->index('EodQuoteDate');
    	$table->decimal('EodQuoteOpen', 9, 2);
    	$table->decimal('EodQuoteHigh', 9, 2);
    	$table->decimal('EodQuoteLow', 9, 2);
    	$table->decimal('EodQuoteClose', 9, 2);
    	$table->integer('EodQuoteVolume');
    	$table->timestamp('EodQuoteCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('EodQuote');
	}

}
