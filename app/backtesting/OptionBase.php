<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Cache;
use Carbon\Carbon;
use Libraries\Rsi;

class OptionBase
{
  public $backtest_id = 0;
    
  public $table = null;
  public $symbol = null;
  
  public $snp_ivr = null;  
  public $last_quote = null;
  public $current_quote = null;
  public $vix_close_prices = null;
  
  public $cash = 0;
  public $start_cash = 0;
  public $reserved_cash = 0;
  
  public $start_date = null;
  public $end_date = null;
  public $start_time = null;
  public $end_time = null;
  public $day_count_30 = 0;
  
  public $debt_amount = 0;
  public $debt_months = 0;
  public $debt_payment = 0;
  
  public $trade_log = [];
  public $positions = [];
  
  public $backtesttrades_model = null;

  //
  // Construct...
  //
  public function __construct()
  {
    $this->snp_ivr = Cache::get('EodQuote.EodQuoteClose.Snp.IVR');        
    $this->vix_close_prices = Cache::get('EodQuote.EodQuoteClose.VIX');
    $this->backtesttrades_model = App::make('App\Models\BackTestTrades');
  }
  
  //
  // Run EOD options tick
  //
  public function run_eod_ticks()
  {
    $laps = 0;
    $total_days = 0;
    $filtered_dates = [];
    
    // Setup dates.
    $dates = Cache::get('Options.Eod.Dates');
    
    // Get an index of the last prices for the symbol
    $last = Cache::get('Options.Eod.' . strtoupper($this->symbol) . '.Lasts');

    // Epoch dates.
    $ep_start = strtotime($this->start_date);
    $ep_end = strtotime($this->end_date);

    // Filter out just the dates we want.    
    foreach($dates AS $key => $row)
    {
      // Skip dates we do not care about. - Start
      if($ep_start > strtotime($row))
      {
        continue;
      }

      // Skip dates we do not care about. - End
      if($ep_end < strtotime($row))
      {
        continue;
      } 
      
      // Count
      $total_days++;
      
      // Add to array;
      $filtered_dates[] = $row;
    }

    // Loop through each date.
    foreach($filtered_dates AS $key => $row)
    {
      $puts = [];
      $calls = [];
      
      // Set the progress
      if($laps >= 10)
      {
        $laps = 0;
        $progress = number_format(($key / $total_days) * 100, 2);
   
        // Tell websockets of the progress.
        Queue::pushOn('stockpeer.com.websocket', 'Backtesting:progress', [ 
          'UsersId' => (string) Auth::user()->UsersId, 
          'Payload' => [ 'progress' => $progress ] 
        ]);            
      } else
      {
        $laps++;
      }     
      
      // Pull from memory the data.
      $p = Cache::get('Options.Eod.' . strtoupper($this->symbol) . '.Puts.' . $row);
      $c = Cache::get('Options.Eod.' . strtoupper($this->symbol) . '.Calls.' . $row);
      
      // Expand our CSV stored data - puts
      foreach($p AS $key2 => $row2)
      {
        $t = explode(',', $row2);
        
        if(! isset($puts[$t[1]]))
        {
          $puts[$t[1]] = [];
        }
        
        $puts[$t[1]][$t[2]] = [
          'type' => $t[0],
          'expire' => $t[1],
          'strike' => $t[2],
          'last' => $t[3],
          'bid' => $t[4],  
          'ask' => $t[5],  
          'implied_vol' => $t[6],  
          'delta' => $t[7]                                                                    
        ];
      }

      // Expand our CSV stored data - calls
      foreach($c AS $key2 => $row2)
      {
        $t = explode(',', $row2);
        
        if(! isset($calls[$t[1]]))
        {
          $calls[$t[1]] = [];
        }        
        
        $calls[$t[1]][$t[2]] = [
          'type' => $t[0],
          'expire' => $t[1],
          'strike' => $t[2],
          'last' => $t[3],
          'bid' => $t[4],  
          'ask' => $t[5],  
          'implied_vol' => $t[6],  
          'delta' => $t[7]                                                                    
        ];
      }
      
      // Setup the chain.
      $this->current_quote = [
        'date' => $row,
        'last' => $last[$row],
        'snp_ivr' => (isset($this->snp_ivr[$row])) ? $this->snp_ivr[$row] : 0,
        'vix_close' => (isset($this->vix_close_prices[$row])) ? $this->vix_close_prices[$row] : 0,
        'puts' => $puts,
        'calls' => $calls
      ];
      
      // 30 day counter - 23 week days
      if($this->day_count_30 > 23)
      {
        $this->day_count_30 = 0;
        $this->day_30_trigger($this->current_quote, $this->last_quote);
      } else
      {
        $this->day_count_30++;
      }      
      
      // Start of day handler.
      $this->on_start_of_day($this->current_quote, $this->last_quote);

      // Send data to the tick handler
      $this->on_data($this->current_quote, $this->last_quote);
      
      // End of day handler.
      $this->on_end_of_day($this->current_quote, $this->last_quote);
      
      // Set last quote
      $this->last_quote = $this->current_quote;
    }
    
    // Tell websockets of the progress = 100%.
    Queue::pushOn('stockpeer.com.websocket', 'Backtesting:progress', [ 
      'UsersId' => (string) Auth::user()->UsersId, 
      'Payload' => [ 'progress' => 100 ] 
    ]); 
        
    // Return Happy
    return true;    
  }
  
  //
  // On End of Day.
  //
  public function on_end_of_day(&$quote, &$last_quote)
  {
    // Place holder
  }

  //
  // On Start of Day.
  //
  public function on_start_of_day(&$quote, &$last_quote)
  {
    // Place holder    
  }  
  
  //
  // Triggger every 30 days.
  //
  public function day_30_trigger(&$quote, &$last_quote)
  {
    // See if we still have loan
    if($this->debt_months <= 0)
    {
      return false;
    }
    
    // Factor debt into this.
    if($this->signals['debt']['amount'] > 0)
    {
      $this->debt_months--;
      $this->cash = $this->cash - $this->signals['debt']['payment'];
    }  
    
    // Return Happy.
    return true;
  }
  
  // ---------------------- Positions / Orders ------------------------- //
  
  //
  // Set Debt.
  //
  public function set_debt($amount, $months, $payment)
  {
    $this->debt_amount = $amount;
    $this->debt_months = $months; 
    $this->debt_payment = $payment;
  }
  
  //
  // Set symbol.
  //
  public function set_symbol($syb)
  {
    $this->symbol = strtolower($syb);
  }
  
  //
  // Set cash.
  //
  public function set_cash($amount)
  {
    $this->cash = $amount + $this->debt_amount;
    $this->start_cash = $amount;
  }
  
  //
  // Return trade log.
  //
  public function get_trades()
  {
    return $this->trade_log;
  }
  
  //
  // Get option buying power
  //
  public function get_option_buying_power()
  {
    return $this->cash - $this->reserved_cash;
  }
  
  //
  // Get the cash amount.
  //
  public function get_cash()
  {
    return $this->cash;
  }
  
  //
  // Close everything at a market price.
  //
  public function close_all_positions()
  {       
    foreach($this->positions AS $key => $row)
    {
      $this->close_position($row['id']);
    }    
  }
  
  // 
  // Go through the positions and close expired positions.
  //
  public function close_expired_positions(&$quote, &$last_quote)
  {
    // Go through and see if we have any spread to take off.
    foreach($this->get_positions() AS $key => $row)
    {
      // Check for expired positions.
      if($this->check_for_expired_options($quote, $last_quote, $row))
      {
        continue;
      }
    }    
  }  

  //
  // Return true if a position with the same expire is already on.
  //
  public function check_position_expire_on($expire, $type)
  {
    foreach($this->positions AS $key => $row)
    {
      // Check buy side.
      if(($row['type'] == $type) &&
        ($row['buy_leg']['expire'] == $expire))
      {
        return true;
      }
      
      // Check sell side.
      if(($row['type'] == $type) &&
        ($row['sell_leg']['expire'] == $expire))
      {
        return true;
      }      
    }
    
    // Not found.
    return false;    
  }
  
  //
  // Return true if a position with the same strike is already on.
  //
  public function check_position_strike_on($strike, $type)
  {
    foreach($this->positions AS $key => $row)
    {
      // Check buy side.
      if(($row['type'] == $type) &&
        ($row['buy_leg']['strike'] == $strike))
      {
        return true;
      }
      
      // Check sell side.
      if(($row['type'] == $type) &&
        ($row['sell_leg']['strike'] == $strike))
      {
        return true;
      }      
    }
    
    // Not found.
    return false;    
  }
  
  //
  // Return true if a position is already in place.
  //
  public function check_position_on($expire, $strike, $type)
  {
    foreach($this->positions AS $key => $row)
    {
      // Check buy side.
      if(($row['type'] == $type) &&
        ($row['buy_leg']['strike'] == $strike) &&
        ($row['buy_leg']['expire'] == $expire))
      {
        return true;
      }
      
      // Check sell side.
      if(($row['type'] == $type) &&
        ($row['sell_leg']['strike'] == $strike) &&
        ($row['sell_leg']['expire'] == $expire))
      {
        return true;
      }      
    }
    
    // Not found.
    return false;
  }
  
  //
  // Return the number of options we have on for this expire.
  //
  public function get_position_expire_count($expire, $type)
  {
    $count = 0;
    
    foreach($this->positions AS $key => $row)
    {
      // Check buy side.
      if(($row['type'] == $type) &&
        ($row['buy_leg']['expire'] == $expire))
      {
        $count++;
        continue;
      }
      
      // Check sell side.
      if(($row['type'] == $type) &&
        ($row['sell_leg']['expire'] == $expire))
      {
        $count++;
        continue;        
      }      
    }
    
    // Return count.
    return $count;    
  }  
  
  //
  // See if an option has expired and deal with it.
  //
  public function check_for_expired_options(&$quote, &$last_quote, $row)
  {
    // First we check for expired. Options.
    if(($quote['date'] == $row['sell_leg']['expire']) || 
        (strtotime($quote['date']) > strtotime($row['sell_leg']['expire'])))
    {       
      // First we look to see if the option expired worthless.
      if($last_quote['last'] >= $row['sell_leg']['strike'])
      {
         $this->close_worthless_position($row['id']);
         return true;
      }
      
      // Did we take a loss?
      if($last_quote['last'] < $row['sell_leg']['strike'])
      {
        // Close based on the last price.
        $tmp = $this->current_quote;
        $this->current_quote = $this->last_quote;
        $this->close_position($row['id']);
        $this->current_quote = $tmp;
        return true;
      }
    }
    
    return false;    
  }  
  
  //
  // Open a single option order. Buying at the ask.
  //
  public function open_option($type, $strike, $expire, $lots = 1)
  {
    // Make sure these spreads are real - buy leg
    if(! isset($this->current_quote[$type][$expire][$strike]))
    {
      return false;
    } else
    {
      $option = $this->current_quote[$type][$expire][$strike];
    } 
    
    // Make sure we have more than one lot.
    if($lots == 0)
    {
      return false;
    } 
    
    // Figure out cost.
    if($lots > 0)
    {
      $price = $option['ask'];
      $cost = $option['ask'] * $lots * 100;
    } else
    {
      $price = $option['bid'];
      $cost = $option['bid'] * $lots * 100;      
    }
    
    // Put order.
    $this->positions[] = [
      'id' => uniqid(),
      'open_date' => $this->current_quote['date'],
      'order_type' => 'single-option',
      'type' => $type,
      'option' => $option,
      'price' => $price,
      'cost' => $cost,
      'lots' => $lots,
      'open_symb' => $this->current_quote['last'],
      'open_vix' => $this->current_quote['vix_close'],
      'open_snp_ivr' => $this->current_quote['snp_ivr'],
      'open_delta' => $this->current_quote[$type][$expire][$strike]['delta'],
      'close_delta' => 0,
      'close_symb' => 0.00
    ];  
    
    // Update cash.
    $this->cash = $this->cash - $cost;
    
    // Return happy.
    return true;         
  }
  
  //
  // Open a Long Butterfly Spread
  //
  // $type : call or put
  // $expire : xxxx-xx-xx
  // $itm_strike : strike price
  // $atm_strike : strike price
  // $otm_strike : strike price
  //
  public function open_basic_long_butterfly_spread($type, $expire, $itm_strike, $atm_strike, $otm_strike, $lots = 1)
  { 
    // Find the options we are trading.
    $itm_trade = $this->current_quote[$type][$expire][$itm_strike];
    $atm_trade = $this->current_quote[$type][$expire][$atm_strike];
    $otm_trade = $this->current_quote[$type][$expire][$otm_strike]; 
    
    // Price
    $price = $itm_trade['ask'] + $otm_trade['ask'] - ($atm_trade['bid'] * 2); 
    
    // Cost
    $cost = $price * $lots;  
    
    // Put order.
    $this->positions[] = [
      'id' => uniqid(),
      'open_date' => $this->current_quote['date'],
      'order_type' => 'basic-long-butterfly-spread',
      'type' => $type,
      'otm_leg' => $otm_trade,
      'atm_leg' => $atm_trade,
      'itm_leg' => $itm_trade,
      'price' => $price,
      'cost' => $cost,
      'lots' => $lots,
      'open_symb' => $this->current_quote['last'],
      'open_vix' => $this->current_quote['vix_close'],
      'open_snp_ivr' => $this->current_quote['snp_ivr'],
      'open_delta' => $this->current_quote[$type][$expire][$atm_strike]['delta'],
      'close_delta' => 0,
      'close_symb' => 0.00
    ];  
    
    // Update cash.
    $this->cash = $this->cash - $cost;     
  }
  
  //
  // Open a basic spread order.
  // $buy_strike : should be in xxx.xx format
  // $sell_strike : should be in xxx.xx format
  // $type = puts / calls
  // If $midpoint we assume we can fill at the mid point. Else market order.
  //
  public function open_basic_credit_spread($buy_strike, $sell_strike, $expire, $type, $lots = 1, $midpoint = false)
  {        
    // Make sure these spreads are real - buy leg
    if(! isset($this->current_quote[$type][$expire][$buy_strike]))
    {
      return false;
    } else
    {
      $buy_leg = $this->current_quote[$type][$expire][$buy_strike];
    }
    
    // Make sure these spreads are real - sell leg
    if(! isset($this->current_quote[$type][$expire][$sell_strike]))
    {
      return false;
    } else
    {
      $sell_leg = $this->current_quote[$type][$expire][$sell_strike];
    }   
    
    // Make sure we have more than one lot.
    if($lots <= 0)
    {
      return false;
    }
    
    // Get prices.
    $credit = $sell_leg['bid'] - $buy_leg['ask'];
    $buy_cost = $sell_leg['ask'] - $buy_leg['bid'];
    $mid_point = ($credit + $buy_cost) / 2;	
    $price = ($midpoint) ? ($mid_point * $lots * 100) : ($credit * $lots * 100);
    $per_share_price = $price / ($lots * 100);
    $margin = ((($sell_leg['strike'] - $buy_leg['strike']) * 100) * $lots) - $price;
    
    // See if I have enough cash to put the trade on.
    if($margin > $this->get_option_buying_power())
    {
      return false;
    }
    
    // Figure out margin.
    $margin = ($sell_leg['strike'] - $buy_leg['strike']) * 100 * $lots;
    
    // Put order.
    $this->positions[] = [
      'id' => uniqid(),
      'open_date' => $this->current_quote['date'],
      'order_type' => 'basic-credit-spread',
      'type' => $type,
      'buy_leg' => $buy_leg,
      'sell_leg' => $sell_leg,
      'price' => $per_share_price,
      'cost' => $price,
      'lots' => $lots,
      'margin' => $margin,
      'open_symb' => $this->current_quote['last'],
      'open_vix' => $this->current_quote['vix_close'],
      'open_snp_ivr' => $this->current_quote['snp_ivr'],
      'open_short_delta1' => $this->current_quote[$type][$expire][$sell_strike]['delta'],
      'close_short_delta1' => 0,
      'close_symb' => 0.00
    ];
    
    // Update cash.
    $this->cash = $this->cash + $price;
    $this->reserved_cash = $this->reserved_cash + $margin;
    
    // Return happy.
    return true;
  }
  
  //
  // Call this when a position closes worthless.
  //
  public function close_worthless_position($id)
  {
    // Lopp through and find the positions we are trying to close.
    foreach($this->positions AS $key => $row)
    {
      // We found what we are closing.
      if($row['id'] == $id)
      {
        // Close position
        unset($this->positions[$key]);
        
        // Figure out profit.
        $profit = ($row['price'] * $row['lots'] * 100);  
        
        // Update Balance
        $this->reserved_cash = $this->reserved_cash - $row['margin'];           
      
        // Setup order.
        $order = [
          'id' => $row['id'],
          'order_type' => $row['order_type'],
          'type' => $row['type'],
          'symbol' => $this->symbol,
          'BackTestTradesSymStart' => $row['open_symb'],
          'BackTestTradesSymEnd' => $this->current_quote['last'],
          'BackTestTradesVixStart' => $row['open_vix'],
          'BackTestTradesVixEnd' => $this->current_quote['vix_close'],          
          'BackTestTradesSnpIvrStart' => $row['open_snp_ivr'],
          'BackTestTradesSnpIvrEnd' => $this->current_quote['snp_ivr'],  
          'BackTestTradesSymDiff' => (($this->current_quote['last'] - $row['open_symb']) / $row['open_symb']) * 100,           
          'BackTestTradesLots' => $row['lots'],
          'BackTestTradesOpen' => $row['open_date'],
          'BackTestTradesClose' => $row['sell_leg']['expire'],            
          'BackTestTradesLongLeg1' => $row['buy_leg']['strike'],
          'BackTestTradesShortLeg1' => $row['sell_leg']['strike'],
          'BackTestTradesExpire1' => $row['buy_leg']['expire'],
          'BackTestTradesExpire2' => $row['sell_leg']['expire'],
          'BackTestTradesOpenCredit' => $row['price'],          
          'BackTestTradesCloseCredit' => 0.00,
          'BackTestTradesStopped' => 'No',
          'BackTestTradesProfit' => $profit,
          'BackTestTradesBalance' => $this->cash,
          'BackTestTradesShortDeltaStart1' => $row['open_short_delta1'],
          'BackTestTradesShortDeltaEnd1' => 0,
          'reserved_cash' => $this->reserved_cash        
        ];
      
        // Log order.
        $this->trade_log[] = $order;
        
        // Tell the websocket this happened
        Queue::pushOn('stockpeer.com.websocket', 'Backtesting:order', [
          'UsersId' => (string) Auth::user()->UsersId,
          'Payload' => $order
        ]);                
      }
    }    
  }
  
  //
  // Close a position. We can pass in our own close price.
  // Since we are using EOD data this is useful for when 
  // limit orders are hit as we know they will be hit at this price.
  //
  public function close_position($id, $close_price = null, $stopped = false)
  {    
    // Lopp through and find the positions we are trying to close.
    foreach($this->positions AS $key => $row)
    {
      // We found what we are closing.
      if($row['id'] == $id)
      {
        // Close position
        unset($this->positions[$key]);
        
        // Is this a single option trade
        if($row['order_type'] == 'single-option')
        {
          if(! isset($this->current_quote[$row['type']][$row['option']['expire']][$row['option']['strike']]))
          {
            return false;
          }
          
          // Get option.
          $option = $this->current_quote[$row['type']][$row['option']['expire']][$row['option']['strike']];
          
          // Short or long?
          if($row['lots'] > 0)
          {
            $close_price = ($option['bid'] * $row['lots'] * 100);
            $profit = $close_price - $row['cost'];
            $this->cash = $this->cash + $close_price;
          } else
          {
            $close_price = ($option['ask'] * $row['lots'] * 100);
            $profit = $close_price - $row['cost'];
            $this->cash = $this->cash + $close_price;           
          }
              
          // Log order.
          $order = [
            'id' => $row['id'],
            'order_type' => $row['order_type'],
            'type' => $row['type'],
            'symbol' => $this->symbol,
            'BackTestTradesSymStart' => $row['open_symb'],
            'BackTestTradesSymEnd' => $this->current_quote['last'],
            'BackTestTradesSymDiff' => (($this->current_quote['last'] - $row['open_symb']) / $row['open_symb']) * 100,
            'BackTestTradesVixStart' => $row['open_vix'],
            'BackTestTradesVixEnd' => $this->current_quote['vix_close'],
            'BackTestTradesSnpIvrStart' => $row['open_snp_ivr'],
            'BackTestTradesSnpIvrEnd' => $this->current_quote['snp_ivr'],                                   
            'BackTestTradesLots' => $row['lots'],
            'BackTestTradesStrike' => $row['option']['strike'],
            'BackTestTradesExpire1' => $row['option']['expire'],                        
            'BackTestTradesOpen' => $row['open_date'],
            'BackTestTradesClose' => $this->current_quote['date'],                       
            'BackTestTradesOpenCost' => $row['cost'],
            'BackTestTradesCloseCost' => $close_price,            
            'BackTestTradesProfit' => $profit,
            'BackTestTradesBalance' => $this->cash,         
            'reserved_cash' => $this->reserved_cash       
          ];
          
          $this->trade_log[] = $order;   
          
          // Record trade
          $order['BackTestTradesTestId'] = $this->backtest_id;
          $this->backtesttrades_model->insert($order);                   
        }
        
        
        
        
        // Is this a basic credit spread?
        if($row['order_type'] == 'basic-credit-spread')
        {
          // Figure out close price.
          if(is_null($close_price))
          {	          
            $buy_leg = $this->current_quote[$row['type']][$row['buy_leg']['expire']][$row['buy_leg']['strike']];
						$sell_leg = $this->current_quote[$row['type']][$row['sell_leg']['expire']][$row['sell_leg']['strike']];
						$close_price = $sell_leg['ask'] - $buy_leg['bid'];
          }
          
          // Figure out profit.
          $profit = ($row['price'] * $row['lots'] * 100) - ($close_price * $row['lots'] * 100);
          
          // Update Balance
          $this->reserved_cash = $this->reserved_cash - $row['margin'];
          $this->cash = $this->cash - ($close_price * $row['lots'] * 100);           
          
          // Log order.
          $order = [
            'id' => $row['id'],
            'order_type' => $row['order_type'],
            'type' => $row['type'],
            'symbol' => $this->symbol,
            'BackTestTradesSymStart' => $row['open_symb'],
            'BackTestTradesSymEnd' => $this->current_quote['last'],
            'BackTestTradesSymDiff' => (($this->current_quote['last'] - $row['open_symb']) / $row['open_symb']) * 100,
            'BackTestTradesVixStart' => $row['open_vix'],
            'BackTestTradesVixEnd' => $this->current_quote['vix_close'],
            'BackTestTradesSnpIvrStart' => $row['open_snp_ivr'],
            'BackTestTradesSnpIvrEnd' => $this->current_quote['snp_ivr'],                                   
            'BackTestTradesLots' => $row['lots'],
            'BackTestTradesOpen' => $row['open_date'],
            'BackTestTradesClose' => $this->current_quote['date'],            
            'BackTestTradesLongLeg1' => $row['buy_leg']['strike'],
            'BackTestTradesShortLeg1' => $row['sell_leg']['strike'],
            'BackTestTradesExpire1' => $row['buy_leg']['expire'],
            'BackTestTradesExpire2' => $row['sell_leg']['expire'],
            'BackTestTradesOpenCredit' => $row['price'],
            'BackTestTradesCloseCredit' => $close_price,
            'BackTestTradesStopped' => ($stopped) ? 'Yes' : 'No',            
            'BackTestTradesProfit' => $profit,
            'BackTestTradesBalance' => $this->cash,
            'BackTestTradesShortDeltaStart1' => $row['open_short_delta1'],
            'BackTestTradesShortDeltaEnd1' => 0, // TODO: Fix. //($this->current_quote[$row['type']][$row['sell_leg']['expire']][$row['sell_leg']['strike']]['delta'],          
            'reserved_cash' => $this->reserved_cash       
          ];
          
          $this->trade_log[] = $order;
          
          // Record trade
          $order['BackTestTradesTestId'] = $this->backtest_id;
          $this->backtesttrades_model->insert($order);          
          
          // Tell the websocket this happened
          Queue::pushOn('stockpeer.com.websocket', 'Backtesting:order', [
            'UsersId' => (string) Auth::user()->UsersId,
            'Payload' => $order
          ]);                      
        }
          
      } 
    }
    
    // Return happy.
    return true;
  }
  
  //
  // Return the first of our position. Useful when we know we only have one position.
  //
  public function get_first_position()
  {
    if(! $count = $this->position_count())
    {
      return false;
    }
    
    return $this->positions[0];
  }
  
  //
  // Return the array of positions.
  //
  public function get_positions()
  {
    return $this->positions;
  }
  
  //
  // Return number of positions on.
  //
  public function position_count()
  {
    return count($this->positions);
  }
  
  // ---------------------- Helper Function ----------------------------- //
  
  //
  // Find by strike and expire.
  //
  public function find_by_strike_expire($strike, $expire, &$chain)
  {
    foreach($chain AS $key => $row)
    {
      if(($row['strike'] == $strike) && ($row['expire'] == $expire))
      {
        return $row;
      }
    }
    
    return false;
  }
  
  //
  // Clean quote data.
  //
  public function clean_quote($quote)
  {
    $rt = [];
    
    foreach($quote AS $key => $row)
    {
      $rt[str_ireplace($this->table, '', $key)] = $row;
    }
    
    return $rt;
  }
  
  //
  // Return an array of summary data on trades.
  //
  public function get_trade_summary()
  {
    $credit_sum = [];
    $days_in_trade = [];
    
    $rt = [
      'wins' => 0,
      'losses' => 0,
      'total' => 0,
      'profit' => 0,
      'end_cash' => 0,
      'return' => 0,
      'cagr' => 0,
      'win_rate' => 0,
      'avg_credit' => 0,
      'rounded_years' => 0,
      'avg_days_in_trade' => 0,
      'start_date' => $this->start_date,
      'end_date' => $this->end_date  
    ];
    
    $trades = $this->get_trades();
    
    foreach($trades AS $key => $row)
    {
      $rt['total']++;
      
      if($row['BackTestTradesProfit'] > 0)
      {
        $rt['wins']++;
      } else
      {
        $rt['losses']++;
      }
      
      $credit_sum[] = $row['BackTestTradesOpenCredit'];
      
      $rt['profit'] = $rt['profit'] + $row['BackTestTradesProfit'];
      
      // Get the number of days in the trade.
      $date1 = date_create($row['BackTestTradesOpen']);
      $date2 = date_create($row['BackTestTradesClose']);
      $diff = date_diff($date1, $date2); 
      $days_in_trade[] = $diff->days;     
    }
    
    $rt['end_cash'] = $this->start_cash + $rt['profit'];
    $rt['avg_credit'] = (count($credit_sum) > 0) ? round(array_sum($credit_sum) / count($credit_sum), 2) : 0;
    $rt['win_rate'] = (count($trades) > 0) ? round(($rt['wins'] / count($trades)) * 100, 2) : 0;
    $rt['return'] = (($rt['end_cash'] - $this->start_cash) / $this->start_cash) * 100;
    
    // CAGR Calc.
    $date1 = date_create($this->start_date);
    $date2 = date_create($this->end_date);
    $diff = date_diff($date1, $date2);
    $rt['rounded_years'] = ceil($diff->days / 365);
    $rt['cagr'] = round(((pow(($rt['end_cash'] / $this->start_cash), (1 / $rt['rounded_years']))) - 1) * 100, 2);
    
    // Average number of days holding a trade.
    $rt['avg_days_in_trade'] = (count($days_in_trade) > 0) ? round(array_sum($days_in_trade) / count($days_in_trade)) : 0;

    return $rt;
  } 
  
  //
  // Get closest .5 strike from last.
  //
  public function get_closest_point_5_strike_from_last($last)
  {
    $diff = $last - round($last);
    
    if($diff > 0)
    {
      $strike = number_format(round($last), 2);
    } else
    {
      $strike = number_format((round($last) - 1) + 0.50, 2);
    }    
    
    return $strike;
  }
  
  //
  // Calculate variance of array
  //
  public function variance($aValues, $bSample = false)
  {
    $fMean = array_sum($aValues) / count($aValues);
    $fVariance = 0.0;
  
    foreach($aValues as $i)
    {
      $fVariance += pow($i - $fMean, 2);
    }
    
    $fVariance /= ( $bSample ? count($aValues) - 1 : count($aValues) );
    
    return $fVariance;
  }
  
  //
  // Calculate standard deviation of array, by definition it is square root of variance
  //
  public function standard_deviation($aValues, $bSample = false)
  {
    $fVariance = $this->variance($aValues, $bSample);
    return (float) sqrt($fVariance);
  }  
}

/* End File */