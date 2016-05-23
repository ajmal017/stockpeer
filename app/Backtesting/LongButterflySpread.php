<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class LongButterflySpread extends OptionBase
{  
  public $start_date = null;
  public $end_date = null;
  public $waiting_period = 0;
  
  //
  // Run....
  //
  public function run($parms)
  {    
    $this->parms = $parms;
    $this->start_date = $parms['start_date'];
    $this->end_date = $parms['end_date'];
    $this->option_type = $parms['option_type'];
    
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
    
    $trades = $trades = $this->_filter_trades($quote, $this->_screen_trades_trades($quote));
    
    //echo '<pre>' . print_r($trades, TRUE) . '</pre>';
    
/*
    foreach($trades AS $key => $row)
    {      
      //echo $row['itm']['strike'] . ' / ' . $row['atm']['strike'] . ' / ' . $row['otm']['strike'] . ' ' . $row['atm']['expire'] . ' $' . $row['bid_price'] . "\n";
    }
*/
    
    // Just for kicks we trade the last option.
    if(count($this->positions) == 0)
    {
      $last_option = $trades[count($trades) - 1];
    
      echo "Opening: " . $quote['date'] . ' :: ' . $last_option['itm']['strike'] . ' / ' . $last_option['atm']['strike'] . ' / ' . $last_option['otm']['strike'] . ' ' . $last_option['atm']['expire'] . ' $' . $last_option['bid_price'] . "\n";
    
      $this->open_basic_long_butterfly_spread($this->option_type, $last_option['atm']['expire'], $last_option['itm']['strike'], $last_option['atm']['strike'], $last_option['otm']['strike'], 1);
    }
    
    //die();
    
    // open_basic_long_butterfly_spread($type, $expire, $itm, $atm, $otm);
    
    // Return happy
    return;
  }
  
  //
  // On Start of Day.
  //
  public function on_start_of_day(&$quote, &$last_quote)
  {    
    // See if we have any trades to take off.
    foreach($this->positions AS $key => $row)
    {
      if(! isset($quote[$this->option_type][$row['itm_leg']['expire']]))
      {
        // Hack for testing.
        $this->positions = [];
        
        echo "Closing: " .  round($quote['last'] - $row['itm_leg']['strike'], 2) . " (" . $quote['last'] . ")\n";
        
        continue;
      }
      
      // Get close price of current pos. 
      $ask_price = ($quote[$this->option_type][$row['atm_leg']['expire']][$row['atm_leg']['strike']]['ask'] * 2) -
                    ($quote[$this->option_type][$row['itm_leg']['expire']][$row['itm_leg']['strike']]['bid'] + 
                    $quote[$this->option_type][$row['otm_leg']['expire']][$row['otm_leg']['strike']]['bid']);
      
      //echo $row['cost'] . '  ' . $ask_price . '  ' . $quote['last'] . "\n";
    }
    
    // Return happy
    return;
  }  
  
  //
  // On End of Day.
  //
  public function on_end_of_day(&$quote, &$last_quote)
  {    
    // Return happy
    return;
  }  

  // ---------------------- Private Helper Functions ------------------- //  
  
  //
  // Filter out trades we are not interested in.
  //
  private function _filter_trades(&$quote, $trades)
  {
    $rt = [];
    
    // Loop through the trades
    foreach($trades AS $key => $row)
    {
      // Make sure we are less than x days away from expiring
      $date1 = date_create($quote['date']);
      $date2 = date_create($row['atm']['expire']);
      $diff = date_diff($date1, $date2);     
    
      // Is this outside our max days to trade?
      if($diff->days > $this->parms['max_days_to_expire'])
      {
        continue;
      }

      // Is this outside our min days to trade?
      if($diff->days < $this->parms['min_days_to_expire'])
      {
        continue;
      }
      
      // Remove trades that are too pricey
      if($this->parms['max_price_to_pay'] < $row['bid_price'])
      {
        continue;
      }
      
      // We know a price of zero does not make sense.
      if($row['bid_price'] <= 0)
      {
        continue;
      }
      
      // Accept the trade.
      $rt[] = $row;
    }
    
    // Return filtered trades.
    return $rt;
  }
  
  //
  // Screen for possible trades
  //
  private function _screen_trades_trades(&$quote)
  {
    $rt = [];
    
    // Figure out the ATM strike
    $atm_strike = number_format(($quote['last'] >= .5) ? (floor($quote['last']) + .5) : floor($quote['last']), 2);
    $itm_strike = number_format(($atm_strike - 5), 2);      
    $otm_strike = number_format(($atm_strike + 5), 2); 
    
    // Loop through the exire dates.
    foreach($quote[$this->option_type] AS $key => $row)
    {
      $trade = [ 'itm' => [], 'atm' => [], 'otm' => [] ];
      
      // Make sure we have the ATM option
      if(! isset($row[$atm_strike]))
      {
        continue;
      } else
      {
        $trade['atm'] = $row[$atm_strike];
      }
      
      // Get the ITM option.
      if(! isset($row[$itm_strike]))
      {
        continue;
      } else
      {
        $trade['itm'] = $row[$itm_strike];
      }
      
      // Get the OTM option.
      if(! isset($row[$otm_strike]))
      {
        continue;
      } else
      {
        $trade['otm'] = $row[$otm_strike];
      }
      
      // Add bid price.
      $trade['bid_price'] = $price = $trade['itm']['ask'] + $trade['otm']['ask'] - ($trade['atm']['bid'] * 2);
      
      $rt[] = $trade;      
    }
    
    // Return trades.
    return $rt;
  }  
}
	
/* End File */