<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Newsletter01 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('Newsletter', function($table)
		{
    	$table->increments('NewsletterId');
    	$table->string('NewsletterFirstName');
    	$table->string('NewsletterLastName');
     	$table->string('NewsletterEmail'); 
     	$table->string('NewsletterIp'); 
		 	$table->string('NewsletterCity'); 
     	$table->string('NewsletterState'); 
     	$table->string('NewsletterCountry'); 
     	$table->string('NewsletterTimezone');      	     	       	   	     	
     	$table->enum('NewsletterSubscribed', [ 'Yes', 'No' ])->default('Yes');
     	$table->enum('NewsletterStatus', [ 'Active', 'Disabled' ])->default('Active');
    	$table->integer('NewsletterOrder');
    	$table->timestamp('NewsletterUpdatedAt'); 
    	$table->timestamp('NewsletterCreatedAt');     	   	     	   	    	    	    	    	
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('Newsletter');
	}

}

