<?php
//
// By: Spicer Matthews
// Date: 1/26/2016
// Description: Helper library for screening for trades.
//
	
namespace App\Library;

use App;
use Auth;
use View;
use Cache;
use Crypt;
use App\Screens\PutCreditSpread;

class Screener
{
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
    
    return $rt;
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
    
    return $rt;
  }  
}

/* End File */