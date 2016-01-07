<?php

namespace App\Http\WebSockets;

use DB;
use App;
use Config;
use Pheanstalk\Pheanstalk;
  
trait Queue
{ 
  // 
  // Check to see if we have any queue messages to send to the browser.
  //
  public function get_queue_msg()
  {
    // See if we have a job in the DB Queue. We take up to 5 jobs at a time. 
		$jobs = DB::table('Jobs')
            ->lockForUpdate()
            ->where('queue', 'stockpeer.com.websocket')
            ->where('reserved', 0)
            ->where('available_at', '<=', time())
            ->orderBy('id', 'asc')
            ->take(5)
            ->get();   
            
    // Did we get a job?
    if(! $jobs)
    {
      return false;
    }
    
    // Delete all the jobs right away so we do not have any race conditions or loss data.
    // Yes there could be edge cases but we don't care as a simple page 
    // refresh would solve any wonky data on the screen and race conditions are not likely
    foreach($jobs AS $key => $row)
    {
      DB::table('Jobs')->where('id', $row->id)->delete();
    }
    
    // Loop through the different jobs.
    foreach($jobs AS $key => $row)
    {  
      // Deal with the message.
      $msg = json_decode($row->payload, true);
      
      // Build data
      $rt = [
        'timestamp' => date('n/j/y g:i:s a'),        
        'type' => $msg['job'],
        'data' => $msg['data']
      ];  
      
      // Make sure we have a user id.
      if(! isset($rt['data']['UsersId']))
      {
        DB::table('Jobs')->where('id', $row->id)->delete();
        return false;
      }      
      
      // Loop through and send the message to our clients.
      foreach($this->clients AS $key2 => $row2) 
      {
        // Make sure the client is authenicated. 
        if((! isset($row2->user)) || (! is_object($row2->user)))
        {
          continue;
        }       
        
        // Make sure this message is for this user.
        if($row2->user->UsersId != $rt['data']['UsersId'])
        {
          continue;
        }
        
        // Send message to client.
        $row2->send(json_encode($rt));
      }      
    }   
  }
}

/* End File */