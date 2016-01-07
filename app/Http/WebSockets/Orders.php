<?php

namespace App\Http\WebSockets;

use DB;
use App;
use Crypt;
use Cloudmanic\LaravelApi\Me;
  
trait Orders
{  
  //
  // Get the orders we have in the account.
  //
  public function get_open_orders()
  {
    $seen = [];
    $quotes = [];
    
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
      
      // Get the watch list of this user.
      $user = DB::table('Users')->where('UsersId', $row->user->UsersId)->first();
      
      // Set the account.
      Me::set_account([ 'AccountsId' => $user->UsersId ]);
      
      // Make API to get the possitions.
      $tradier = App::make('App\Library\Tradier');
      $tradier->set_token(Crypt::decrypt($row->user->UsersTradierToken));     
      if(! $data = $tradier->get_account_orders($row->user->UsersTradierAccountId, true))
      {
        $this->server->error('get_open_orders: Error getting data from Tradier. (' . $tradier->get_last_error() . ')');
        continue;
      }
      
      // Debug the rate limits
      //$limit = $tradier->get_last_rate_limit();
      //$this->server->info(json_encode($limit)); 
         
      // Build data we return.
      $rt = [
        'timestamp' => date('n/j/y g:i:s a'),        
        'type' => 'Orders:get_open_orders',
        'data' => $data
      ];         
  
      // Send data to client.
      $row->send(json_encode($rt));
      
      // Record this data so we don't make the same API call twice.
      $seen[$row->user->UsersId] = json_encode($rt);
      
      // Clear the account.
      Me::set_account([ 'AccountsId' => 0 ]);
    }   
  }
}

/* End File */