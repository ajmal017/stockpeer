<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Blog03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Blog', function($table)
		{
			$table->enum('BlogCategory', [ 'posts', 'trades' ])->after('BlogDate');			
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
			$table->dropColumn('BlogCategory');		
		});
	}

}
