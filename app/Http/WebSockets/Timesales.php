<?php

namespace App\Http\WebSockets;

use DB;
use App;
use Crypt;
use DateTime;
use DateInterval;
use Cloudmanic\LaravelApi\Me;
  
trait Timesales
{  
  // 
  // Get time sales data and send it to clients.
  //
  public function get_timesales()
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
      
      // See if this user even has a timesales
      if((! isset($row->timesales)) || (! is_array($row->timesales)))
      {
        continue;
      }      
      
      // Have we already seen this UsersId (connected from more than one device).
      if(isset($seen[$row->timesales['range'] . ':' . $row->timesales['symbol']]))
      {
        $row->send($seen[$row->timesales['range'] . ':' . $row->timesales['symbol']]);
        continue;
      }
      
      // Get the watch list of this user.
      $user = DB::table('Users')->where('UsersId', $row->user->UsersId)->first();
      
      // Set today.
      $today = new DateTime();
      
      // Build parms.
      switch($row->timesales['range'])
      {
        case 'today-1':
          $inv = '1min';
          $start = date('n/j/Y');
          $end = date('n/j/Y');          
        break;
      
        case 'today-5':
          $inv = '5min';
          $start = date('n/j/Y');
          $end = date('n/j/Y');          
        break;
        
        case 'today-15':
          $inv = '15min';
          $start = date('n/j/Y');
          $end = date('n/j/Y');        
        break;
        
        case '5d-15':
          $inv = '15min';
          $start = $today->sub(new DateInterval('P7D'))->format('n/j/Y');
          $end = date('n/j/Y');                
        break;      
        
        case '1y-daily':
          $inv = 'daily';
          $start = $today->sub(new DateInterval('P365D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
        
        case '1y-weekly':
          $inv = 'weekly';
          $start = $today->sub(new DateInterval('P365D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
      
        case '1y-monthly':
          $inv = 'monthly';
          $start = $today->sub(new DateInterval('P365D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
        
        case '2y-daily':
          $inv = 'daily';
          $start = $today->sub(new DateInterval('P730D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
        
        case '2y-weekly':
          $inv = 'weekly';
          $start = $today->sub(new DateInterval('P730D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
      
        case '2y-monthly':
          $inv = 'monthly';
          $start = $today->sub(new DateInterval('P730D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break; 
        
        case '5y-weekly':
          $inv = 'weekly';
          $start = $today->sub(new DateInterval('P1825D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
      
        case '5y-monthly':
          $inv = 'monthly';
          $start = $today->sub(new DateInterval('P1825D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;            
      
        case '10y-weekly':
          $inv = 'weekly';
          $start = $today->sub(new DateInterval('P3650D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break;
      
        case '10y-monthly':
          $inv = 'monthly';
          $start = $today->sub(new DateInterval('P3650D'))->format('n/j/Y');
          $end = date('n/j/Y');        
        break; 
      
        case 'max-monthly':
          $inv = 'monthly';
          $start = '1/1/1980';
          $end = date('n/j/Y');        
        break; 
        
        default:
          return false;
        break;
      }
      
      // Make API call to Tradier to get quotes.
      $tradier = App::make('App\Library\Tradier');
      $tradier->set_token(Crypt::decrypt($row->user->UsersTradierToken));
      
      // Setup Start / End times.
      $start_t = date('Y-m-d 09:30', strtotime($start));
      $end_t = date('Y-m-d 16:00', strtotime($end)); 
        
      // Make API call and get data.
      switch($inv)
      {
        case '1min':
        case '5min':
        case '15min':            
          if(! $data_t = $tradier->get_timesales($row->timesales['symbol'], $inv, $start_t, $end_t, 'open'))
          {
            $this->server->error('Timesales::get_timesales: Error getting data from Tradier. (' . $tradier->get_last_error() . ')');
            $data_t = [];        
          }
        break;
        
        case 'daily':
        case 'weekly':
        case 'monthly':            
          if(! $data_t = $tradier->get_historical_pricing($row->timesales['symbol'], $inv, $start_t, $end_t))
          {
            $this->server->error('Timesales::get_timesales: Error getting data from Tradier. (' . $tradier->get_last_error() . ')');
            $data_t = [];
          }
          
          // Add timestamps
          foreach($data_t AS $key2 => $row2)
          {
            $data_t[$key2]['timestamp'] = strtotime($row2['date']);
          } 
        break;    
      }      
      
      // Debug the rate limits
      //$limit = $tradier->get_last_rate_limit();
      //$this->server->info(json_encode($limit));
      
      // Setup the data we send over.      
      $data = [ 
        'range' => $row->timesales['range'], 
        'symbol' => $row->timesales['symbol'],
        'start' => $start,
        'end' => $end,
        'inv' => $inv,
        'data' => $data_t 
      ];
      
      // Build data we return.
      $rt = [
        'timestamp' => date('n/j/y g:i:s a'),        
        'type' => 'Timesales:get_timesales',
        'data' => $data
      ];      

      // Send data to client.
      $row->send(json_encode($rt));
      
      // Record this data so we don't make the same API call twice.
      $seen[$row->timesales['range'] . ':' . $row->timesales['symbol']] = json_encode($rt);
    }    
  }
}

/* End File */