<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Symbols01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Symbols', function($table)
		{
    	$table->increments('SymbolsId');
			$table->string('SymbolsShort')->index('SymbolsShort');
			$table->string('SymbolsFull');
    	$table->timestamp('SymbolsUpdatedAt');
    	$table->timestamp('SymbolsCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Symbols');
	}


}
