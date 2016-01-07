<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Input;
use Crypt;
use Request;
use App\Autotrading\PutCreditSpread;

class Positions extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	

  //
  // Get my positions formated by type
  //
  public function get_by_types()
  {
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));    
    $data = $tradier->get_account_positions_group_by_type(Auth::user()->UsersTradierAccountId); 
    return $this->api_response($data);
  }
}

/* End File */