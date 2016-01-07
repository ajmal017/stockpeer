<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OptionsEod01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('OptionsEod', function($table)
		{
    	$table->bigIncrements('OptionsEodId');
    	$table->integer('OptionsEodSymbolId')->index('OptionsEodSymbolId');
			$table->decimal('OptionsEodSymbolLast', 9, 2);
			$table->enum('OptionsEodType', [ 'call', 'put' ])->index('OptionsEodType');
			$table->date('OptionsEodExpiration')->index('OptionsEodExpiration');
			$table->date('OptionsEodQuoteDate')->index('OptionsEodQuoteDate');			
			$table->decimal('OptionsEodStrike', 9, 2)->index('OptionsEodStrike');
			$table->decimal('OptionsEodLast', 9, 2);
			$table->decimal('OptionsEodBid', 9, 2);
			$table->decimal('OptionsEodAsk', 9, 2);									
			$table->integer('OptionsEodVolume');
			$table->integer('OptionsEodOpenInterest')->index('OptionsEodOpenInterest');
			$table->decimal('OptionsEodImpliedVol', 9, 4);
			$table->decimal('OptionsEodDelta', 9, 4);
			$table->decimal('OptionsEodGamma', 9, 4);
			$table->decimal('OptionsEodTheta', 9, 4);															
			$table->decimal('OptionsEodVega', 9, 4);	
    	$table->timestamp('OptionsEodCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('OptionsEod');
	}

}
