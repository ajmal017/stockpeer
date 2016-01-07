<?php

use Illuminate\Database\Migrations\Migration;

class AssetMarks01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('AssetMarks', function($table)
		{
    	$table->increments('AssetMarksId');
    	$table->integer('AssetMarksAccountId')->index('AssetMarksAccountId');
    	$table->integer('AssetMarksAssetId')->index('AssetMarksAssetId');    	   	
			$table->date('AssetMarkDate');
    	$table->decimal('AssetMarksValue', 9, 2);
     	$table->text('AssetMarksNote');
    	$table->timestamp('AssetMarksUpdatedAt');
    	$table->timestamp('AssetMarksCreatedAt');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('AssetMarks');
	}

}