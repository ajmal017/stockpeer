<?php

namespace App\Http\WebSockets;

use DB;
use App;
use Crypt;
use Cloudmanic\LaravelApi\Me;
  
trait Positions
{  
  //
  // Call this whenever we want the current possitions of an account.
  //
  public function get_current_positions()
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
      if(! $data = $tradier->get_account_positions_group_by_type($row->user->UsersTradierAccountId))
      {
        $this->server->error('get_current_positions: Error getting data from Tradier. (' . $tradier->get_last_error() . ')');
        continue;
      }
      
      // Debug the rate limits
      //$limit = $tradier->get_last_rate_limit();
      //$this->server->info(json_encode($limit));    
      
      // Build data we return.
      $rt = [
        'timestamp' => date('n/j/y g:i:s a'),        
        'type' => 'Positions:get_current_positions',
        'data' => $data
      ];         
  
      // Send data to client.
      $row->send(json_encode($rt));
      
      // Record this data so we don't make the same API call twice.
      $seen[$row->user->UsersId] = json_encode($rt);
      
      // This is hacky because we are making 2 api calls. But when we move to the 
      // grouping of trades this will be less hacky.
      $data = $tradier->get_account_positions($row->user->UsersTradierAccountId, true); 
      
      // Log this order in our order's table
      $this->_log_position($data, $user);      
    
      // Clear the account.
      Me::set_account([ 'AccountsId' => 0 ]);      
    }   
  }
  
  // ------------------ Private Helper Functions ------------------- //
  
  //
  // Log order
  //
  private function _log_position($data, $user)
  {   
    $trade_group_id = null;
    $positions_model = App::make('App\Models\Positions');
        
    // Loop through the positions and log them.
    foreach($data AS $key => $row)
    { 
      // See if we are updating or if this is a new position.
      if(! $positions_model->update_position($row))
      {
        // Add the new position to the database.
        if($t = $positions_model->add_new_position($row, $trade_group_id))
        {
          $trade_group_id = $t;
        }
      }
    }      
  }
}

/* End File */