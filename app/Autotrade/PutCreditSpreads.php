<?php
  
namespace App\Autotrade;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;

class PutCreditSpreads extends AutoTradeBase
{
  public $buy_lots = 5;
  public $buy_min_credit = 0.18;
  public $buy_value_away = 4;
  public $buy_spread_width = 2;
  public $max_days_to_expire = 45;
  public $min_days_to_expire = 0;
  
  //
  // Everytime we get data (1st a min) we manage the data.
  // We get an options chain.
  //
  // $now - Carbon instance of time. 
  // $data - Data that was returned from the data driver.  
  //
  public function on_data($now, $data)
  {
    // See if the market is open. Nothing to do if it is not open.
    if(! $this->data_driver->is_market_open())
    {
      $this->cli->info('[' . date('Y-m-d G:i:s') . '] Market is closed.');
      return false;
    }
    
    // Get possible trades. 
    $trades = $this->_find_possible_trades($data);
    
    $this->filter_trades($trades);
    
    // Get the trade we are going to make.
    $trade = $this->get_middle_credit_trade($trades);

    // Place order.
    $this->orders_driver->order_put_credit_spread($trade['occ_buy'], $trade['occ_sell'], 'limit', $trade['credit'], $this->buy_lots);
    
    return true;
  }  
  
  // --------------------- Private Helper Functions ------------------------ //  
  
  //
  // Filter out positions we already have.
  //
  public function filter_trades(&$trades)
  {
    $list = [];
    
    // Get the positions we already have on.
    $poss = $this->positions_driver->get_positions();
    
    foreach($poss AS $key => $row)
    {
      $list[] = $row['symbol'];
    }
    
    // Filter out trades
    foreach($trades AS $key => $row)
    {
      if(in_array($row['occ_sell'], $list))
      {
        unset($trades[$key]);
      } else if(in_array($row['occ_buy'], $list))
      {
        unset($trades[$key]);
      }      
    }
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
  // Find possible trades.
  //
  private function _find_possible_trades(&$data)
  {
    $rt = [];
    
    // Figure out the strike price that is the min we can sell.
    $tmp = $data['stock']['last'] - ($data['stock']['last'] * ($this->buy_value_away / 100));    
    $fraction = $tmp - floor($tmp);
    $min_sell_strike = ($fraction >= .5) ? (floor($tmp) + .5) : floor($tmp);       
      
    // Loop through expire dates looking for trades.
    foreach($data['chain'] AS $key => $row)
    {
      // Days to expire.
      $date1 = date_create("now");
      $date2 = date_create($key);
      $diff = date_diff($date1, $date2);
      
      // Don't want to go too far out.
      if($diff->days > $this->max_days_to_expire)
      {
        continue;
      }
      
      // Don't want to go too close out.
      if($diff->days < $this->min_days_to_expire)
      {
        continue;
      }  
      
      // Loop through chain and review.
      foreach($row AS $key2 => $row2)
      {
        // Only puts
        if($row2['option_type'] != 'put')
        {
          continue;
        }
        
        // Skip open_interest of 0
        if($row2['open_interest'] <= 0)
        {
          continue;
        }
        
        // Skip strikes that are higher than our min strike.
        if($row2['strike'] > $min_sell_strike)
        {
          continue;          
        }
        
        // Find the strike that is x points away.
        if(! $buy_leg = $this->_find_by_strike($row, 'put', ($row2['strike'] - $this->buy_spread_width)))
        {
          continue;
        }
        
        // See if there is enough credit.
        $credit = $row2['bid'] - $buy_leg['ask'];
        if($credit < $this->buy_min_credit)
        {
          continue;
        }    
        
        // Figure out the credit spread amount.
        $buy_cost = $row2['ask'] - $buy_leg['bid'];
        $mid_point = ($credit + $buy_cost) / 2;	           
        
        // We have a winner
        $rt[] = [
          'timestamp' => date('n-j-Y g:i:s a'),
          'timestamp_df1' => date('n/j/y g:i:s a'),
          'sell_leg' => $row2['strike'],
          'buy_leg' => $buy_leg['strike'],
          'expire' => $key,
          'expire_df1' => date('n/j/y', strtotime($key)),       
          'credit' => number_format($credit, 2),
          'midpoint' => number_format($mid_point, 2),
          'precent_away' => number_format((1 - $row2['strike'] / $data['stock']['last']) * 100, 2),
          'occ_sell' => $row2['symbol'],
          'occ_buy' => $buy_leg['symbol'] 
        ];
      }
    }     
    
    // Return trades.
    return $rt;    
  }
  
  //
  // Find a strike price that is X number of strikes below.
  //
  private function _find_by_strike(&$chain, $type, $strike)
  {    
    foreach($chain AS $key => $row)
    {
      // Only want puts.
      if($row['option_type'] != $type)
      {
        continue;
      }
      
      if($strike == $row['strike'])
      {
        return $row;
      }
    }
    
    return false;    
  }  
}

/* End File */