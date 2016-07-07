<?php

namespace App\Models;

use Sendy;

class Newsletter extends \Cloudmanic\LaravelApi\Model
{
  //
  // Insert.
  //
  public function insert($data)
  {
    $id = parent::insert($data);
    
    // Subscribe with Sendy
		if(isset($data['NewsletterEmail']) && env('SENDY_URL'))
		{
      Sendy::subscribe([ 'email' => $data['NewsletterEmail'] ]);		
		}
		
		// Return happy.
		return $id;
  }

}

/* End File */