<?php

namespace App\Http\Controllers\Api\V1;

USE DB;
use App;
use Auth;
use Crypt;
use Input;
use Cache;
use Request;
use DateTime;
use DateInterval;

class Quotes extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	
	
	//
	// Return quotes for the entire account.
	//
	public function get_account_quotes()
	{
  	$sybs = [];
  	
  	// Get symbols we need from orders
  	$orders = DB::select("SELECT OrdersSymbol, OrdersLeg1OptionSymbol, OrdersLeg2OptionSymbol, OrdersLeg3OptionSymbol, OrdersLeg4OptionSymbol FROM Orders WHERE (OrdersUpdatedAt > TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 day)) OR OrdersStatus = 'Open') AND OrdersAccountId=" . Auth::user()->UsersId);
  	
    foreach($orders AS $key => $row)
    {
      foreach($row AS $key2 => $row2)
      {
        if(! empty($row2))
        {
          $sybs[] = $row2;
        }
      }
    }
    
    // Get Positions
    $positions = DB::select("SELECT SymbolsShort FROM Positions LEFT JOIN Symbols ON PositionsSymbolId = SymbolsId WHERE  PositionsStatus='Open' AND PositionsAccountId=" . Auth::user()->UsersId);
  	
    foreach($positions AS $key => $row)
    {
      $sybs[] = $row->SymbolsShort;
    }  	

    // Get Watchlist.
    $watch = DB::select("SELECT UsersWatchList FROM Users WHERE UsersId=" . Auth::user()->UsersId);

    foreach(json_decode($watch[0]->UsersWatchList, true) AS $key => $row)
    {
      $sybs[] = strtoupper($row);
    } 
  	
    // Get quotes from Tradier
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
    $data = $tradier->get_quotes($sybs);  	
    
  	// Return data.
  	return $this->api_response($data);
	}
	
	//
	// Return current S&P 500 rank
	//
	public function get_snp_500_rank($days)
	{
    $vix_rank = Cache::get('Quotes.SnP500.Rank.' . $days, function() use ($days) {   	
      // Vix.
      $today = 0;
      $vix_rank = 0;
      $vix_running = [];   	
      
      // Get VIX data from Tradier.
      $tradier = App::make('App\Library\Tradier');
      $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
      $data = $tradier->get_historical_pricing('vix', 'daily', date('n/j/Y', strtotime("-$days days")), date('n/j/Y')); 
      
      // Get the current price for the VIX.
      $vix = $tradier->get_quotes([ 'vix' ]); 
      $vix = $vix[0];
      $today = $vix['last'];    
      
      // Loop through data.              
      foreach($data AS $key => $row)
      {
        if(count($vix_running) >= $days)
        {
          array_shift($vix_running);
        } 
      
        $vix_running[] = $row['close'];
      }
      
      // Calculate the IVR
      $l = 0;
      $s = 0;
      
      foreach($vix_running AS $key2 => $row2)
      {
        if($row2 < $today)
        {
          $l++;
        } 
        
        if($row2 == $today)
        {
          $s++;
        } 
        
        $vix_rank = number_format((($l + (0.5 * $s)) / count($vix_running)) * 100, 2);
      }
      
      // Put in cache (5 min cache)
      Cache::put('Quotes.SnP500.Rank.' . $days, $vix_rank, 5);        
      
      // Return rank.
      return $vix_rank;   
    });      
    
    // Return data.
    return $this->api_response([ 'Rank' => $vix_rank ]);
	}
	
	
  //
  // Get timesales data.
  //
  public function timesales()
  {
    // Setup Tradier instance
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
    
    // Setup Start times.
    if(is_integer(Input::get('start')))
    {
      $start = date('Y-m-d 09:30', Input::get('start'));
    } else
    {
      $start = date('Y-m-d 09:30', strtotime(Input::get('start')));      
    }

    // Setup End times.
    if(is_integer(Input::get('end')))
    {
      $end = date('Y-m-d 16:00', Input::get('end'));  
    } else
    {
      $end = date('Y-m-d 16:00', strtotime(Input::get('end')));      
    }
    
    // Sometimes we just want to use presets.
    if(Input::get('preset'))
    {
      // Set today.
      $today = new DateTime();      
      
      switch(Input::get('preset'))
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
      
      // Replace input values.
      Input::merge([ 'interval' => $inv ]);
      Input::merge([ 'start' => $start ]);
      Input::merge([ 'end' => $end ]);         
    }    
    
    // Setup Start / End times.
    $start_t = date('Y-m-d 09:30', strtotime($start));
    $end_t = date('Y-m-d 16:00', strtotime($end));     
      
    // Make API call and get data.
    switch(Input::get('interval'))
    {
      case '1min':
      case '5min':
      case '15min':           
        $data = $tradier->get_timesales(Input::get('symbol'), Input::get('interval'), $start_t, $end_t, 'open'); 
      break;
      
      case 'daily':
      case 'weekly':
      case 'monthly':            
        $data = $tradier->get_historical_pricing(Input::get('symbol'), Input::get('interval'), Input::get('start'), Input::get('end'));
        
        // Add timestamps
        foreach($data AS $key => $row)
        {
          $data[$key]['timestamp'] = strtotime($row['date']);
        } 
      break;
      
      default:
        return $this->api_response([]);
      break;      
    }
    
    // Return happy.
    return $this->api_response($data);
  }
}

/* End File */