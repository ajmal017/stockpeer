<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Input;
use Crypt;
use Cache;
use Request;
use App\Autotrading\PutCreditSpread;

class Autotrade extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	

  //
  // Weekly SPY Percent Away
  //
  public function spy_weekly_percent_away()
  {
    // Return from cache if we have it.
    $rt = Cache::get('Screener.PercentAway.Spy.7Day', function() { 
        
      // Get data From Tradier
      $t = new PutCreditSpread;
      $t->set_tradier_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
      $d = $t->spy_weekly_percent_away();
      
      // Put in cache
      Cache::put('Screener.PercentAway.Spy.7Day', $d, 1);
      
      // Return data.
      return $d;      
      
    });
    
    return $this->api_response($rt);
  }
	
  //
  // SPY Percent Away
  //
  public function spy_percent_away()
  {
    // Return from cache if we have it.
    $rt = Cache::get('Screener.PercentAway.Spy.45Day', function() {
      
      // Get data From Tradier
      $t = new PutCreditSpread;
      $t->set_tradier_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
      $d = $t->spy_percent_away();
      
      // Put in cache
      Cache::put('Screener.PercentAway.Spy.45Day', $d, 1);
      
      // Return data.
      return $d;
      
    });
    
    return $this->api_response($rt);
  }
}

/* End File */