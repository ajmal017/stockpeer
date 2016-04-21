<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Positions05 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{  	
    DB::statement("ALTER TABLE Positions CHANGE COLUMN PositionsType PositionsType ENUM('Stock', 'Option', 'Credit Spread', 'Debit Spread', 'Future', 'Other')");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{  	
    DB::statement("ALTER TABLE Positions CHANGE COLUMN PositionsType PositionsType ENUM('Stock', 'Option', 'Credit Spread', 'Debit Spread', 'Other')");
	}
}
