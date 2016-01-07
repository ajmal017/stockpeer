<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class OptionDirectional extends OptionBase
{  
  public $start_date = null;
  public $end_date = null;

  
  //
  // Run....
  //
  public function run($parms)
  {
    $this->start_date = $parms['start_date'];
    $this->end_date = $parms['end_date'];
    $this->signals = $parms['signals'];
    
    // Set symbol we are backtesting
    $this->set_symbol($parms['symbol']);
    
    // Set starting capital.
    $this->set_cash($parms['cash']);
    
    // Tick by eod
    $this->run_eod_ticks();
    
    // Return all the trades.
    return $this->trade_log;
  }

  //
  // On data.
  //
  public function on_data(&$quote, &$last_quote)
  {      
    // On Mondays we open trades.
    if(date('D', strtotime($quote['date'])) === 'Mon')
    {
      // Figure out the ITM call option.
      $strike = $this->get_closest_point_5_strike_from_last($quote['last']);
      
      // Figure out which expire to buy.
      $expires = array_keys($quote['calls']);
      $expire = $expires[0];
      
      // Get the option we are going to buy.
      if(! isset($quote['calls'][$expire][$strike]))
      {
        return false;
      }
            
      // Buy option.
      $this->open_option('puts', $strike, $expire, 1);

      $this->open_option('calls', $strike, $expire, 1);
    } 
        
  }
  
  //
  // On End of Day.
  //
  public function on_end_of_day(&$quote, &$last_quote)
  {    
    // Close Trades On Friday.
    if(date('D', strtotime($quote['date'])) === 'Fri')
    {
      foreach($this->get_positions() AS $key => $row)
      {
        $this->close_position($row['id']);
      }
    }
  }    
}
	
/* End File */