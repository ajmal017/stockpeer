<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class PutCreditSpreads extends OptionBase
{  
  public $start_date = null;
  public $end_date = null;
  public $waiting_period = 0;
  
  //
  // Construct...
  //
  public function __construct()
  {
    parent::__construct();
  }
  
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
    
    // Set debt
    $this->set_debt($this->signals['debt']['amount'], $this->signals['debt']['months'], $this->signals['debt']['payment']);
    
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
    // Are we in a waiting period?
    if($this->waiting_period > 0)
    {
      $this->waiting_period--;
      return false;
    } else
    {
      $this->waiting_period = 0;
    }    
      
    // Are we only do one trade at a time?
    if(($this->signals['buy']['one_at_time'] == 'Yes') && count($this->positions))
    {
      return false;
    }
    
    // Screen for possible trades.
    switch($this->signals['buy']['type'])
    {
      // Percent away.
      case 'precent-away':
        $trades = $this->filter_trades($this->screen_precent_away_trades($quote));
      break;
    }
    
    // Open Trade
    $trade = $this->get_trade_to_place($trades);
    $midpoint = ($this->signals['buy']['midpoint'] == 'yes') ? true : false;
    $this->open_basic_credit_spread($trade['buy_leg'], $trade['sell_leg'], $trade['expire'], 'puts', $this->get_lot_size(), $midpoint);
  }
  
  //
  // On Start of Day.
  //
  public function on_start_of_day(&$quote, &$last_quote)
  {    
    // Closed expired options.
    $this->close_expired_positions($quote, $last_quote);
    
    // Go through and see if we have any spread to take off.
    foreach($this->get_positions() AS $key => $row)
    {      
      // See what type of close signals we have.
      switch($this->signals['close']['type'])
      {
        // When an option hits a target price sell.
        case 'hit-target-price':
          $this->close_on_hit_target_price($quote, $row);
        break;
        
        // Let the spread expire.
        case 'let-expire':
          // Nothing to do really....
        break;
      } 
    }
    
    // Return happy
    return;
  }  
  
  //
  // On End of Day.
  //
  public function on_end_of_day(&$quote, &$last_quote)
  {    
    // Go through and see if we have any spread to take off.
    foreach($this->get_positions() AS $key => $row)
    {      
      // See what type of close signals we have.
      switch($this->signals['stop']['type'])
      {
        // Close if we hit our short strike.
        case 'touch-short-leg':
          $this->close_on_hit_short_strike($quote, $row);
        break;
        
        // See if we hit a short delta
        case 'short-delta-greater-than':
          $this->close_on_hit_greater_delta($quote, $row, $this->signals['stop']['value']);
        break;
      } 
    }
  }  

  // ---------------------- Private Helper Functions ------------------- //
  
  //
  // Loop through and close our trade if we hit a delta trigger.
  //
  public function close_on_hit_greater_delta(&$quote, $row, $delta)
  {    
    // Did we touch the short leg?
    if($quote[$row['type']][$row['sell_leg']['expire']][$row['sell_leg']['strike']]['delta'] < $delta)
    {
      $this->waiting_period = $this->signals['days_to_wait_after_loss'];
      $this->close_position($row['id'], null, true);
      return true;
    }
    
    // Sold nothing.
    return false;
  }  
  
  //
  // Loop through and close our trade if we hit the short strike.
  //
  public function close_on_hit_short_strike(&$quote, $row)
  {
    // Did we touch the short leg?
    if($row['sell_leg']['strike'] >= $quote['last'])
    {
      // Make sure we are less than x days away from expiring
      $date1 = date_create($quote['date']);
      $date2 = date_create($row['sell_leg']['expire']);
      $diff = date_diff($date1, $date2);      

      if($diff->days > 20)
      {
        return false;
      }
      
      $this->waiting_period = $this->signals['days_to_wait_after_loss'];
      $this->close_position($row['id'], null, true);
      return true;
    }
    
    // Sold nothing.
    return false;
  }  
  
  //
  // Select trade to put on.
  //
  public function get_trade_to_place(&$trades)
  {
    switch($this->signals['buy']['trade-select'])
    {
      case 'median-credit':
        return $this->get_middle_credit_trade($trades);      
      break;
      
      case 'highest-credit':
        return $this->get_highest_credit_trade($trades);
      break;
      
      case 'lowest-credit':
        return $this->get_lowest_credit_trade($trades);
      break;      
    }
  }
  
  //
  // Filter trades. Remove trades we know we do not want.
  //
  public function filter_trades($trades)
  {
    $filtered_trades = [];
    
    foreach($trades AS $key => $row)
    {
      // See if we already have this position on and ignore it. - Buy Leg
      if($this->check_position_on($row['expire'], $row['buy_leg'], 'puts'))
      {
        continue;
      }

      // See if we already have this position on and ignore it. - Sell Leg
      if($this->check_position_on($row['expire'], $row['sell_leg'], 'puts'))
      {
        continue;
      }
      
      // Check to see if a position with the same strike is on. - Buy Leg
      if($this->check_position_strike_on($row['buy_leg'], 'puts'))
      {
        continue;
      }

      // Check to see if a position with the same strike is on. - Sell Leg
      if($this->check_position_strike_on($row['sell_leg'], 'puts'))
      {
        continue;
      }
    
      // Get the number of positions already on for this expire date.
      $expire_count = $this->get_position_expire_count($row['expire'], 'puts');
      
      // See if we have reached our max expire count.
      if($expire_count >= $this->signals['max_per_expire_date'])
      {
        continue;
      }
      
      // Good trade.
      $filtered_trades[] = $row;
    }
    
    // Return filtered results.
    return $filtered_trades;    
  }

  //
  // Return trade with the middle credit.
  //
  public function get_middle_credit_trade(&$trades)
  {
    $tmp = [];
    $trade = null;
    
    if(count($trades) <= 0)
    {
      return $trade;
    }
    
    foreach($trades AS $key => $row)
    {
      $tmp[] = $row['credit'];
    }
    
    // Get the median
    rsort($tmp); 
    $middle = round(count($tmp) / 2); 
    $median = $tmp[$middle-1]; 
    
    // Look for our median
    foreach($trades AS $key => $row)
    {
      if($row['credit'] == $median)
      {
        return $row;
      }
    }
    
    return $trade;
  }

  //
  // Return trade with the lowest credit.
  //
  public function get_lowest_credit_trade(&$trades)
  {
    $trade = null;
    
    foreach($trades AS $key => $row)
    {
      if(is_null($trade))
      {
        $trade = $row;
        continue;
      }
      
      if($trade['credit'] > $row['credit'])
      {
        $trade = $row;
      }
    }
    
    return $trade;
  }
  
  //
  // Return trade with the highest credit.
  //
  public function get_highest_credit_trade(&$trades)
  {
    $trade = null;
    
    foreach($trades AS $key => $row)
    {
      if(is_null($trade))
      {
        $trade = $row;
        continue;
      }
      
      if($trade['credit'] < $row['credit'])
      {
        $trade = $row;
      }
    }
    
    return $trade;
  }
  
  //
  // Return lot size.
  //
  public function get_lot_size()
  {
    // Figure out lots size
    switch($this->signals['lot_size']['type'])
    {
      case 'percent-of-cash':
        return floor(($this->get_cash() * $this->signals['lot_size']['value']) / ($this->signals['buy']['spread_width'] * 100));
      break;
      
      case 'fixed-lot':
        return $this->signals['lot_size']['value'];
      break;
    }
    
    // Should not get here.
    return 1;    
  }
  
  //
  // Close trade on hitting a target price.
  //
  public function close_on_hit_target_price(&$quote, $row)
  {
    // Make sure we have a quote for the position we have on.
    if(! isset($quote['puts'][$row['sell_leg']['expire']]))
    {
      return false;
    }
    
    // Make sure we have a quote for the position we have on.
    if(! isset($quote['puts'][$row['sell_leg']['expire']][$row['sell_leg']['strike']]))
    {
      return false;
    }
    
    // Find out the price to close this position
    $price = $quote['puts'][$row['sell_leg']['expire']][$row['sell_leg']['strike']]['ask'] - 
              $quote['puts'][$row['buy_leg']['expire']][$row['buy_leg']['strike']]['bid']; 
              
    // See if we hit our target price.
    if($price <= $this->signals['close']['value'])
    {
      $this->close_position($row['id'], $this->signals['close']['value']);
      return true;
    }
    
    // We did nothing
    return false;                 
  }
  
  //
  // Screen for possible trades to make: precent-away.
  //
  public function screen_precent_away_trades(&$quote)
  {
    $rt = [];
    
    // Figure out the strike price that is the min we can sell.
    $tmp = $quote['last'] - ($quote['last'] * ($this->signals['buy']['value'] / 100));    
    $fraction = $tmp - floor($tmp);
    $min_sell_strike = ($fraction >= .5) ? (floor($tmp) + .5) : floor($tmp);       
    
    // Loop through all the options for today.
    foreach($quote['puts'] AS $expire => $strikes)
    {
      foreach($strikes AS $key => $row)
      {
        // Days to expire.
        $date1 = date_create($quote['date']);
        $date2 = date_create($row['expire']);
        $diff = date_diff($date1, $date2);
        
        // Don't want to go too far out.
        if($diff->days > $this->signals['buy']['max_days_to_expire'])
        {
          continue;
        }
        
        // Don't want to go too close out.
        if($diff->days < $this->signals['buy']['min_days_to_expire'])
        {
          continue;
        }  
             
/*
        // TODO - Add this to memcache - Skip open_interest of 0
        if($row['open_interest'] <= 0)
        {
          continue;
        }
*/

     
/*
      if($quote['snp_ivr'] < 50)
      {
        continue;
      }   
*/  
      
/*
      if($quote['puts'][$row['expire']][$row['strike']]['delta'] <= -0.21)
      {
        continue;
      }
*/

      //echo $quote['puts'][$row['expire']][$row['strike']]['delta'] . "\n";      
        
        // Skip strikes that are higher than our min strike.
        if($row['strike'] > $min_sell_strike)
        {
          continue;          
        }           
        
        // Get buy leg. number_format is important because this is an indexed array.
        $buy_strike = number_format($row['strike'] - $this->signals['buy']['spread_width'], 2);
        
        if(! isset($quote['puts'][$row['expire']][$buy_strike]))
        {
          continue;
        } else
        {
          $buy_leg = $quote['puts'][$row['expire']][$buy_strike];
        }
          
        // See if there is enough credit.
        $credit = $row['bid'] - $buy_leg['ask'];
        if($credit < $this->signals['buy']['min_credit'])
        {
          continue;
        }    
        
        // Figure out the credit spread amount.
        $buy_cost = $row['ask'] - $buy_leg['bid'];
        $mid_point = ($credit + $buy_cost) / 2;	
        
        // We have a winner
        $rt[] = [
          'date' => $quote['date'],
          'sell_leg' => $row['strike'],
          'buy_leg' => $buy_leg['strike'],
          'expire' => $row['expire'],      
          'credit' => $credit,
          'midpoint' => $mid_point,
          'precent_away' => number_format((1 - $row['strike'] / $quote['last']) * 100, 2)
        ];
      }      
    }
    
    // Return results.
    return $rt;
  }
  
}
	
/* End File */