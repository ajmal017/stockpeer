 <?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Backtestorders01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('BackTestOrders');
		
		Schema::create('BackTestOrders', function($table)
		{
    	$table->increments('BackTestOrdersId');
    	$table->integer('BackTestOrdersAccountId')->index('BackTestOrdersAccountId');
    	$table->integer('BackTestOrdersTestId')->index('BackTestOrdersTestId');
			$table->date('BackTestOrdersOpen');      	
			$table->date('BackTestOrdersClose'); 
			$table->decimal('BackTestOrdersSymStart', 9, 2);
			$table->decimal('BackTestOrdersSymEnd', 9, 2);
			$table->decimal('BackTestOrdersSymDiff', 9, 2);	
			$table->decimal('BackTestOrdersVixStart', 9, 2);
			$table->decimal('BackTestOrdersVixEnd', 9, 2);	
			$table->decimal('BackTestOrdersLongLeg1', 9, 2);
			$table->decimal('BackTestOrdersLongLeg2', 9, 2);
			$table->decimal('BackTestOrdersShortLeg1', 9, 2);
			$table->decimal('BackTestOrdersShortLeg2', 9, 2);						
      $table->date('BackTestOrdersExpire1'); 
      $table->integer('BackTestOrdersLots');
      $table->enum('BackTestOrdersTouched', [ 'Yes', 'No' ])->default('No');  
      $table->decimal('BackTestOrdersCredit', 9, 2);
      $table->decimal('BackTestOrdersProfit', 9, 2);
      $table->decimal('BackTestOrdersBalance', 9, 2);      
      $table->decimal('BackTestOrdersCosts', 9, 2);    	
    	$table->timestamp('BackTestOrdersUpdatedAt');
    	$table->timestamp('BackTestOrdersCreatedAt');
    });		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('BackTestOrders');
	}

}
