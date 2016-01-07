<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Income01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Income', function($table)
		{
    	$table->increments('IncomeId');
    	$table->integer('IncomeAccountId')->index('IncomeAccountId');
    	$table->integer('IncomeTradeGroupId')->index('IncomeTradeGroupId');    	    	
    	$table->integer('IncomeSymbolsId')->index('IncomeSymbolsId'); 
    	$table->integer('IncomeTradierAssetId')->index('IncomeTradierAssetId');
    	$table->integer('IncomeTradierHistoryId');
    	$table->enum('IncomeType', [ 'Dividend', 'Interest', 'P2P Interest', 'Realty Shares Distribution', 'Loan Interest', 'Other' ])->default('Other');
			$table->date('IncomeDate');    	
    	$table->decimal('IncomeAmount', 9, 2);
     	$table->text('IncomeNote');
    	$table->timestamp('IncomeUpdatedAt');
    	$table->timestamp('IncomeCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Income');
	}

}
