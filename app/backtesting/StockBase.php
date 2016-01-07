<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class StockBase
{
  public $table = null;
  public $symbol = null;
  
  public $current_quote = null;
  
  public $cash = 0;
  public $start_cash = 0;
  
  public $start_date = null;
  public $end_date = null;
  public $start_time = null;
  public $end_time = null;
  
  public $trade_log = [];
  public $positions = [];
  
  
  protected $symbol_table_1_min_map = [
    'spy' => 'Data1MinSpy',
    'iwm' => 'Data1MinIwm'
  ];
  
  //
  // Construct...
  //
  public function __construct()
  {
    // Place holder
  }
  
  //
  // Run 1 min trades.
  //
  public function run_1_min_trades()
  {
    $day = null;
    $last = null;
    
    // Big query.
    $d = DB::table($this->table)
            ->where($this->table . 'Date', '>=', $this->start_date)
            ->where($this->table . 'Date', '<=', $this->end_date)            
            ->where($this->table . 'Time', '>=', $this->start_time) 
            ->where($this->table . 'Time', '<=', $this->end_time)                              
            ->orderby($this->table . 'Date', 'asc')
            ->orderby($this->table . 'Time', 'asc')
            ->get();
            
    // Loop through and call a function per data.
    foreach($d AS $key => $row)
    {
      // Store the current quote.
      $this->current_quote = (array) $row; 
      
      // Keep track if we change days.
      if(is_null($day))
      {
        $day = $row->{$this->table . 'Date'};
        $this->on_start_of_day($this->clean_quote($this->current_quote));
      } if($day != $row->{$this->table . 'Date'})
      {
        $day = $row->{$this->table . 'Date'};
        
        // Do end of day stuff.
        $tmp = $this->current_quote;
        $this->current_quote = $last;
        $this->on_end_of_day($this->clean_quote($this->current_quote));
        
        // Do start of new day stuff.
        $this->current_quote = $tmp;
        $this->on_start_of_day($this->clean_quote($this->current_quote));        
      } 
      
      // Call on_data function in the kid class
      $this->on_data($this->clean_quote($this->current_quote));
      
      // Store last tick.
      $last = $this->current_quote;
    }
  }
  
  //
  // On End of Day.
  //
  public function on_end_of_day($quote)
  {
    // Place holder
  }

  //
  // On Start of Day.
  //
  public function on_start_of_day($quote)
  {
    // Place holder    
  }  
  
  // ---------------------- Positions / Orders ------------------------- //
  
  //
  // Set symbol.
  //
  public function set_symbol($syb)
  {
    $this->symbol = strtolower($syb);
    $this->table = $this->symbol_table_1_min_map[$this->symbol];
  }
  
  //
  // Set cash.
  //
  public function set_cash($amount)
  {
    $this->cash = $amount;
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
  // Close everything at a market price.
  //
  public function close_all_positions()
  {       
    foreach($this->positions AS $key => $row)
    {
      $this->order_close($row['symbol']);
    }    
  }
  
  //
  // Place an order for a stock position. Use negative number for short.
  // If we do not pass in a qty we use all our money. If we set margin
  // to true we double our qty if we do not pass in a qty
  //
  public function order($sym, $qty = null, $margin = false)
  {
    // Do we use all our money?
    if(is_null($qty))
    {
      $qty = floor($this->cash / $this->current_quote[$this->table . 'Last']);
      
      if($margin)
      {
        $qty = $qty * 2;
      }
    }
    
    // Update Balance
    $this->cash = $this->cash - $this->current_quote[$this->table . 'Last'] * abs($qty);
    
    // Place order
    $this->positions[] = [
      'symbol' => strtolower($sym),
      'qty' => $qty,
      'price' => $this->current_quote[$this->table . 'Last'],
      'open_date' => $this->current_quote[$this->table . 'Date'],
      'open_time' => $this->current_quote[$this->table . 'Time']
    ];
  }
  
  //
  // Close all positions for a particular symbol stock.
  //
  public function order_close($sym)
  {    
    foreach($this->positions AS $key => $row)
    {
      if($row['symbol'] == strtolower($sym))
      {
        unset($this->positions[$key]);
        
        // Reset the position.
        $tmp = $this->positions;
        $this->positions = [];
        foreach($tmp AS $key2 => $row2)
        {
          $this->positions[] = $row2;
        }
        
        // Figure out profit.
        if($row['qty'] > 0)
        {
          $profit = round(($this->current_quote[$this->table . 'Last'] - $row['price']) * $row['qty'], 2);
        } else
        {
          $profit = round(($row['price'] - $this->current_quote[$this->table . 'Last']) * $row['qty'], 3);
        }
        
        // Update Balance
        $this->cash = $this->cash + ($this->current_quote[$this->table . 'Last'] * abs($row['qty']));       
        
        // Log order.
        $this->trade_log[] = [
          'symbol' => $row['symbol'],
          'type' => ($row['qty'] > 0) ? 'Long' : 'Short',
          'qty' => $row['qty'],
          'open_date' => $row['open_date'],
          'close_date' => $this->current_quote[$this->table . 'Date'],
          'open_time' => $row['open_time'],
          'close_time' => $this->current_quote[$this->table . 'Time'],
          'open_price' => $row['price'],
          'close_price' => $this->current_quote[$this->table . 'Last'],
          'profit_share' => ($profit / $row['qty']), 
          'profit' => $profit,
          'cash' => $this->cash        
        ];
      }
    }
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
  // Return number of positions on.
  //
  public function position_count()
  {
    return count($this->positions);
  }
  
  // ---------------------- Helper Function ----------------------------- //
  
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
    $rt = [
      'end_cash' => 0,
      'profit' => 0,
      'wins' => 0,
      'losses' => 0,
      'win_rate' => 0
    ];
    
    $trades = $this->get_trades();
    
    foreach($trades AS $key => $row)
    {
      
      if($row['profit'] > 0)
      {
        $rt['wins']++;
      } else
      {
        $rt['losses']++;
      }
      
      $rt['profit'] = $rt['profit'] + $row['profit'];
      $rt['end_cash'] = $row['cash'];
    }
    
    $rt['win_rate'] = round(($rt['wins'] / count($trades)) * 100, 2);
    
    return $rt;
  }
  
  //
  // Output summary.....helpful in debugging.
  //
  public function return_html()
  {
    $trades = $this->get_trades();
    $summary = $this->get_trade_summary();
    
    return view('backtester.raw-output', [ 
      'trades' => $trades,
      'start_cash' => $this->start_cash,
      'end_cash' => $summary['end_cash'],
      'start_date' => $this->start_date,
      'end_date' => $this->end_date,
      'trade_count' => count($trades), 
      'profit' => $summary['profit'],
      'win_rate' => $summary['win_rate'],
      'profit_precent' => number_format((($summary['end_cash'] - $this->start_cash) / ($this->start_cash)) * 100, 2)
    ]);   
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