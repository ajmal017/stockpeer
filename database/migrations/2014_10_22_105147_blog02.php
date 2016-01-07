<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Blog02 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Blog', function($table)
		{
			$table->integer('BlogImage')->after('BlogBody');
			$table->string('BlogDescription', 160)->after('BlogBody');			
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Blog', function($table)
		{
			$table->dropColumn('BlogImage');
			$table->dropColumn('BlogDescription');			
		});
	}

}
