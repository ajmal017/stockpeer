<?php

namespace App\Http\Controllers\Api\V1;

use \Input;
use \Request;

class Newsletter extends \Cloudmanic\LaravelApi\Controller 
{    
	public $validation_create = [
		'NewsletterEmail' => 'required|email|unique:Newsletter'
	];
	public $validation_update = [];	
	
	// 
	// Create.
	//
	public function create()
	{
		// Add the IP address to the request.
		Input::merge([ 'NewsletterIp' => Request::ip() ]);
		
		return parent::create();
	}
}

/* End File */