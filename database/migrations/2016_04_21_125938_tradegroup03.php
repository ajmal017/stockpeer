<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tradegroup03 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
  	$types = [
    	"'Other'",
    	"'Long Stock Trade'",
    	"'Short Stock Trade'",
    	"'Long Option Trade'",
    	"'Short Option Trade'",
    	"'Put Credit Spread'",
    	"'Put Debit Spread'",
    	"'Call Credit Spread'",
    	"'Put Debit Spread'",
    	"'Weekly Put Credit Spread'",
    	"'Futures Day Trade'"    	
  	];  	
  	
    DB::statement("ALTER TABLE TradeGroups CHANGE COLUMN TradeGroupsType TradeGroupsType ENUM(" . implode(',', $types) . ")");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
  	$types = [
    	"'Other'",
    	"'Long Stock Trade'",
    	"'Short Stock Trade'",
    	"'Long Option Trade'",
    	"'Short Option Trade'",
    	"'Put Credit Spread'",
    	"'Put Debit Spread'",
    	"'Call Credit Spread'",
    	"'Put Debit Spread'",
    	"'Weekly Put Credit Spread'"   	
  	];  	
  	
    DB::statement("ALTER TABLE TradeGroups CHANGE COLUMN TradeGroupsType TradeGroupsType ENUM(" . implode(',', $types) . ")");
	}

}
