<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class WeeklySpy
{
	//
	// Construct.
	//
	public function  __construct()
	{
    
	}
	
	//
	// Run back test.
	//
	public function run($backtest_id)
	{  	
    $backtest = null;
    $orders = [];
    $days = [];
    $away = 0.025;
    $spread_dist = 2;
    $lost_counter = 0;
    $close_when = 'on_touch';
    $eodquote_model = App::make('App\Models\EodQuote');
    $optionseod_model = App::make('App\Models\OptionsEod');
    $backtests_model = App::make('App\Models\BackTests');
    $backtestorders_model = App::make('App\Models\BackTestOrders');        
    $chains_helper = App::make('App\Library\Chains');  
    
    // Get the backtest we are after.
    if(! $backtest = $backtests_model->get_by_id($backtest_id))
    {
      return false;
    } 

    // Lets get the show on the road.
    Auth::loginUsingId($backtest['BackTestsAccountId']);
    $balance = $backtest['BackTestsStartBalance'];
    $backtests_model->update([ 
      'BackTestsStatus' => 'Started', 
      'BackTestsClockStart' => date('Y-m-d G:i:s')
    ], $backtest['BackTestsId']);
     
    // Figure out lot size
    $pts = explode('-', $backtest['BackTestsTradeSize']);
    switch($pts[0])
    {
      case 'fixed':
        $lots_type = [ 'type' => 'fixed', 'value' => $pts[1] ];  
      break;
      
      case 'percent':
        $lots_type = [ 'type' => 'precent', 'value' => $pts[1] ];
      break;
    }
     
    // Get trading days.
    $days = $optionseod_model->get_trade_days('SPY', $backtest['BackTestsStart'], $backtest['BackTestsEnd']);
    $total_days = count($days);
    
    // Loop through the different days
    $laps = 0;
    foreach($days AS $day_count => $day)
    {    
      // Set the progress
      if($laps >= 10)
      {
        $laps = 0;
        $progress = number_format(($day_count / $total_days) * 100, 2);
   
        // Tell websockets of the progress.
        Queue::pushOn('stockpeer.com.websocket', 'Backtesting:progress', [ 'UsersId' => 1, 'progress' => $progress ]);            
      } else
      {
        $laps++;
      }
      
      // Get day of the week.
      $day_of_week = date('l', strtotime($day)); 
      
      // We take 30 days off after a loss
      if(($lost_counter > 0) && ($lost_counter < 30))
      {
        $lost_counter++;
        continue;
      } else
      {
        $lost_counter = 0;
      }
      
      // Get today's chain.
      $chain = $optionseod_model->get_chain('SPY', $day); 
      
      // We only work on Friday. But we do some checking to see how things are going on other days.
      if($day_of_week != 'Friday')
      {        
        // Loop through the orders and see if any open orders touched.
        foreach($orders AS $key => $row)
        { 
          if(! is_null($row['close']))
          {
            continue;
          } 
          
          // See if an open order has touched.
          if($chain['last'] <= $row['sell_strike'])
          {
            $orders[$key]['touched'] = 'Yes';
            
            // Do we close on touch?
            if($close_when == 'on_touch')
            {
              // We close right away on touch.
              $chain = $optionseod_model->get_chain('SPY', $day);              
              $sell_leg = $chains_helper->get_option($chain, 'put', $row['expire'], $row['sell_strike']);  
              $buy_leg = $chains_helper->get_option($chain, 'put', $row['expire'], $row['buy_strike']);            
              $debit = $sell_leg['OptionsEodAsk'] - $buy_leg['OptionsEodBid'];  
              $close_cost = ($debit * $row['lots'] * 100) + (.35 * 2 * $row['lots']);
              
              // Figure out max loss. If there is no sense in closeing now don't.
              if($close_cost > $row['max_loss'])
              {
                continue;
              }
              
              // Close the trade. Cut losses move on.
              $orders[$key]['close_day'] = $day;
              $orders[$key]['close'] = $chain['last'];
              $orders[$key]['vix_close'] = $chain['vix'];
              $orders[$key]['diff'] = $row['open'] - $chain['last'];
              $orders[$key]['prct_diff'] = (abs($row['open'] - $chain['last']) / (($row['open'] + $chain['last']) / 2)) * 100;          
              
              // Figure out profit
              $balance = $balance - $row['profit'];
              $orders[$key]['profit'] = ($close_cost * -1) + ($row['credit'] * $row['lots'] * 100) - $row['commissions']; // close costs, credit on open, commission on open
              $orders[$key]['commissions'] = $row['commissions'] + ($row['lots'] * 2 * .35);
              $balance = $balance + $orders[$key]['profit'];
              $orders[$key]['balance'] = $balance;
              
              // See if this was a loss
              if($orders[$key]['profit'] < 0)
              {
                $lost_counter = 1;
              }
              
              // Store the order.
              $this->_store_order($orders[$key]);                
            }          
          }       
        }
            
        // This is the only thing we do on non-fridays
        continue;
      }      
      
      // First we close out any trades for last week.
      foreach($orders AS $key => $row)
      {
        if(! is_null($row['close']))
        {
          continue;
        }
  
        // Close trade
        $orders[$key]['close_day'] = $day;
        $orders[$key]['close'] = $chain['last'];
        $orders[$key]['vix_close'] = $chain['vix'];
        $orders[$key]['diff'] = $row['open'] - $chain['last'];
        $orders[$key]['prct_diff'] = (abs($row['open'] - $chain['last']) / (($row['open'] + $chain['last']) / 2)) * 100;
        
        // See what our profit would be.
        if($chain['last'] < $row['sell_strike'])
        {          
          $balance = $balance - $row['profit'];
          
          $close_diff = $row['sell_strike'] - $chain['last'];
                  
          if($close_diff >= 2)
          {
            $orders[$key]['profit'] = (($spread_dist * 100) * $row['lots'] * -1) - ($row['lots'] * 2 * .35) + ($row['credit'] * 100 * $row['lots']);
          } else
          {          
            $orders[$key]['profit'] = ($close_diff * 100 * $row['lots'] * -1) - ($row['lots'] * 2 * .35) + ($row['credit'] * 100 * $row['lots']);
          }
          
          $orders[$key]['commissions'] = $orders[$key]['commissions'] + ($row['lots'] * 2 * .35);
          
          $balance = $balance + $orders[$key]['profit'];
          $orders[$key]['balance'] = $balance;
        }
        
        // See if this was a loss
        if($orders[$key]['profit'] < 0)
        {
          $lost_counter = 1;
        }
        
        // Store the order.
        $this->_store_order($orders[$key]);               
      }
          
      // Sell leg strike
      $sell_strike = $chain['last'] - ($chain['last'] * $away);
      
      // Get sell leg strike price.
      if(($sell_strike - floor($sell_strike)) >= 0.75)
      {
        $sell_strike = ceil($sell_strike);
      } else if(($sell_strike - floor($sell_strike)) >= 0.25)
      {
        $sell_strike = floor($sell_strike) + 0.5;        
      } else
      {
         $sell_strike = floor($sell_strike); 
      }    
  
      // See what spread to sell for next week.
      $exps = $chains_helper->get_expiration_from_chain($chain);
        
      // Get sell leg
      $sell_leg = $chains_helper->get_option($chain, 'put', $exps[1], $sell_strike);
  
      // Get buy leg
      $buy_leg = $chains_helper->get_option_away($chain, $exps[1], $sell_strike, $spread_dist, 'put', 'down');
  
      // Credit 
      $credit = $sell_leg['OptionsEodBid'] - $buy_leg['OptionsEodAsk'];
      
      // We need a credit of at least 0.10
      if($credit <= 0.09)
      {
        continue;
      }
      
      // Make sure we have a buy leg.
      if(! isset($buy_leg['OptionsEodStrike']))
      {
        continue;
      }
      
      // Figure out how many lots we are buying.
      if($lots_type['type'] == 'precent')
      {
        $lots = floor(($balance * ($lots_type['value'] / 100)) / ($spread_dist * 100));
      } else
      {
        $lots = $lots_type['value'];
      }
      
      // Balance
      $commissions = ($lots * 2 * .35);
      $profit = ($lots * $credit * 100) - $commissions;
      $balance = $balance + $profit;
      
      // Place order
      $orders[] = [
        'order_id' => $backtest['BackTestsId'],
        'open_day' => $day,
        'close_day' => null,
        'open' => $chain['last'],
        'close' => null,
        'profit' => $profit,
        'diff' => null,
        'prct_diff' => null,
        'lots' => $lots,
        'commissions' => $commissions,
        'credit' => $credit,
        'balance' => $balance,
        'vix_open' => $chain['vix'],
        'vix_close' => 0,        
        'touched' => 'No',
        'max_loss' => (($spread_dist * 100) * $lots) + ($lots * 4 * .35) - ($credit * 100 * $lots),
        'sell_strike' => $sell_leg['OptionsEodStrike'],
        'buy_strike' => $buy_leg['OptionsEodStrike'],
        'spread' => $buy_leg['OptionsEodStrike'] . '/' . $sell_leg['OptionsEodStrike'],
        'expire' => $buy_leg['OptionsEodExpiration']        
      ];
    }    
    
    // Figure out the CAGR
    $years = date('Y', strtotime($backtest['BackTestsEnd'])) - date('Y', strtotime($backtest['BackTestsStart']));
    
    if($years > 0)
    {
      $CAGR = (pow(($balance / $backtest['BackTestsStartBalance']), (1 / $years)) - 1) * 100;
    } else
    {
      $CAGR = (pow(($balance / $backtest['BackTestsStartBalance']), (1 / 1)) - 1) * 100;
    }        
  
    // Mark that we are done.
    $backtests_model->update([ 
      'BackTestsStatus' => 'Ended', 
      'BackTestsProfit' => ($balance - $backtest['BackTestsStartBalance']), 
      'BackTestsCagr' => $CAGR,
      'BackTestsClockEnd' => date('Y-m-d G:i:s')
    ], $backtest['BackTestsId']);
  
    // Return the data
    return [ 'start_balance' => $backtest['BackTestsStartBalance'], 'balance' => $balance, 'orders' => $orders ];		
	}
	
	//
	// Store the order.
	//
	private function _store_order($order)
	{
    $backtestorders_model = App::make('App\Models\BackTestOrders');
  	
  	// Store order in the orders db table.
  	$id = $backtestorders_model->insert([
      'BackTestOrdersTestId' => $order['order_id'],
      'BackTestOrdersOpen' => date('Y-m-d', strtotime($order['open_day'])),    	
      'BackTestOrdersClose' => date('Y-m-d', strtotime($order['close_day'])),
      'BackTestOrdersSymStart' => $order['open'],
      'BackTestOrdersSymEnd' => $order['close'],
      'BackTestOrdersSymDiff' => $order['diff'],	
      'BackTestOrdersVixStart' => $order['vix_open'],
      'BackTestOrdersVixEnd' => $order['vix_close'],	
      'BackTestOrdersLongLeg1' => $order['buy_strike'],
      'BackTestOrdersLongLeg2' => 0,
      'BackTestOrdersShortLeg1' => $order['sell_strike'],
      'BackTestOrdersShortLeg2' => 0,					
      'BackTestOrdersExpire1' => $order['expire'], 
      'BackTestOrdersLots' => $order['lots'],
      'BackTestOrdersTouched' => $order['touched'],  
      'BackTestOrdersCredit' => $order['credit'],
      'BackTestOrdersProfit' => $order['profit'],
      'BackTestOrdersBalance' => $order['balance'],      
      'BackTestOrdersCosts' => $order['commissions']    	    
  	]);
  	
  	// Tell websockets this happened
    $order['UsersId'] = 1;
    Queue::pushOn('stockpeer.com.websocket', 'Backtesting:order', $order);
	}
}
	
/* End File */