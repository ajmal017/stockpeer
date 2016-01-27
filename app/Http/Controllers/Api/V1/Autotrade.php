<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Input;
use Crypt;
use Cache;
use Request;
use App\Library\Screener;

class Autotrade extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	

  //
  // Weekly SPY Percent Away
  //
  public function spy_weekly_percent_away()
  {    
    $screener = new Screener;
    return $this->api_response($screener->spy_weekly_percent_away());
  }
	
  //
  // SPY Percent Away
  //
  public function spy_percent_away()
  {
    $screener = new Screener;
    return $this->api_response($screener->spy_percent_away());
  }
}

/* End File */