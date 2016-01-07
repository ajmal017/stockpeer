<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Fiveminquote01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('FiveMinQuote', function($table)
		{
    	$table->bigIncrements('FiveMinQuoteId');
    	$table->integer('FiveMinQuoteSymbolId')->index('FiveMinQuoteSymbolId');
    	$table->decimal('FiveMinQuoteLast', 9, 2);
    	$table->integer('FiveMinQuoteVolume');
			$table->string('FiveMinQuoteTrend');    	
    	$table->timestamp('FiveMinQuoteCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('FiveMinQuote');
	}

}
