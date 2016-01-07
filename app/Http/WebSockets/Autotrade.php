<?php

namespace App\Http\WebSockets;

use App;
use Crypt;
use App\Autotrading\PutCreditSpread;
  
trait Autotrade
{  
  //
  // Call this to get a list of possible trades.
  //
  public function get_possible_spy_put_credit_spreads_weeklies()
  {    
    $seen = [];
    
    // If no one is connected no need to do anything.
    if(count($this->clients) <= 0)
    {
      return false;
    }
    
    // Get a list of stocks I need to get qu
    foreach($this->clients AS $key => $row)
    {
      // Make sure the client is authenicated. 
      if((! isset($row->user)) || (! is_object($row->user)))
      {
        continue;
      }
      
      // Have we already seen this UsersId (connected from more than one device).
      if(isset($seen[$row->user->UsersId]))
      {
        $row->send($seen[$row->user->UsersId]);
        continue;
      } 
      
      // Get the possible trades.
      $t = new PutCreditSpread;
      $t->set_tradier_token(Crypt::decrypt($row->user->UsersTradierToken));
      
      // Look for trades.
      if(! $data = $t->spy_weekly_percent_away())
      {
        //$this->server->error('get_possible_spy_put_credit_spreads_weeklies: No Trades Found.');     
      } 
        
      // Build data we return.
      $rt = [
        'timestamp' => date('n/j/y g:i:s a'),
        'type' => 'Autotrade:get_possible_spy_put_credit_spreads_weeklies',
        'data' => $data
      ];
      
      // Send data down the wire.
      $row->send(json_encode($rt));
      
      // Mark as seen.
      $seen[$row->user->UsersId] = json_encode($rt);       
    }    
  }

  //
  // Call this to get a list of possible trades.
  //
  public function get_possible_spy_put_credit_spreads_45_days_out()
  {    
    // If no one is connected no need to do anything.
    if(count($this->clients) <= 0)
    {
      return false;
    }
    
     // Get the possible trades.
    $t = new PutCreditSpread;
    if(! $data = $t->spy_percent_away())
    {
      $this->server->error('get_possible_spy_put_credit_spreads_45_days_out: No Trades Found.');     
    } 
      
    // Build data we return.
    $rt = [
      'timestamp' => date('n/j/y g:i:s a'),      
      'type' => 'Autotrade:get_possible_spy_put_credit_spreads_45_days_out',
      'data' => $data
    ];
      
    // Loop through and send the message to our clients.
    foreach($this->clients AS $key => $row) 
    {
      $row->send(json_encode($rt));
    }     
  }
}

/* End File */