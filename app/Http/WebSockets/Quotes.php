<?php

namespace App\Http\WebSockets;

use DB;
use App;
use Crypt;
  
trait Quotes
{  
  //
  // Call this whenever the server wants to tell us something.
  //
  public function get_quotes()
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
      
      // Make API call to Tradier to get quotes.
      $tradier = App::make('App\Library\Tradier');
      $tradier->set_token(Crypt::decrypt($row->user->UsersTradierToken));
      
      // Get the data. Any errors just return.
      if(! $data = $tradier->get_quotes(json_decode($user->UsersWatchList, true)))
      {
        $this->server->error('getQuotes: Error getting data from Tradier. (' . $tradier->get_last_error() . ')');
        continue;
      }
            
      // Build data we return.
      $rt = [
        'timestamp' => date('n/j/y g:i:s a'),        
        'type' => 'Quotes:get_quotes',
        'data' => $data
      ];      

      // Send data to client.
      $row->send(json_encode($rt));
      
      // Send rate limit stuff so we can keep track of that. We do this year because we call this function the most.
      $limit = $tradier->get_last_rate_limit();
      
      $row->send(json_encode([ 
        'timestamp' => date('n/j/y g:i:s a'),        
        'type' => 'Quotes:rate_limit',
        'data' => $limit        
       ]));      
      
      // Record this data so we don't make the same API call twice.
      $seen[$row->user->UsersId] = json_encode($rt);
    }   
  }
}

/* End File */