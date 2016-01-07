<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Input;
use Crypt;
use Request;
use App\Autotrading\PutCreditSpread;

class Orders extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	

  //
  // Get open orders
  //
  public function get_open()
  {
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
    $data = $tradier->get_account_orders(Auth::user()->UsersTradierAccountId, true);    
    return $this->api_response($data);
  }
}

/* End File */