<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Blog01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Blog', function($table)
		{
    	$table->increments('BlogId');
    	$table->string('BlogTitle');
    	$table->datetime('BlogDate');
    	$table->text('BlogSummary');
    	$table->text('BlogBody');
     	$table->enum('BlogStatus', [ 'Active', 'Disabled' ])->default('Active');
    	$table->integer('BlogOrder');
    	$table->timestamp('BlogUpdatedAt'); 
    	$table->timestamp('BlogCreatedAt');     	   	     	   	    	    	    	    	
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Blog');
	}

}
