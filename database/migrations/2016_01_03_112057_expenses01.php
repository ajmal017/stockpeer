<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Expenses01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{		
		Schema::create('Expenses', function($table)
		{
    	$table->increments('ExpensesId');
    	$table->integer('ExpensesAccountId')->index('ExpensesAccountId');
    	$table->date('ExpensesDate');
    	$table->enum('ExpensesVendor', [ 'Avant', 'Prosper', 'Lending Club', 'Tradier', 'Other' ])->default('Other');
    	$table->enum('ExpensesCategory', [ 'Loan Interest', 'Broker Fees', 'Bank Fees', 'Loan Fees', 'Other' ])->default('Other');
    	$table->decimal('ExpensesAmount', 9, 2);
      $table->text('ExpensesNote');
    	$table->timestamp('ExpensesUpdatedAt');
    	$table->timestamp('ExpensesCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Expenses');
	}

}
