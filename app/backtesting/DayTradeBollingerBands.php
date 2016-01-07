<?php
	
namespace App\Backtesting;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class DayTradeBollingerBands extends StockBase
{
  private $qty = 50;  
  private $last_20 = [];
  private $std_pad = 2.35;
  private $limit = 0.40;
  private $stop = 0.40;
  
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
    $this->start_time = '9:31:00';
    $this->end_time = '16:00:00';
    
    // Set symbol we are backtesting
    $this->set_symbol($parms['symbol']);
    
    // Set starting capital.
    $this->set_cash(5000);
    
    // Tick by 1 min.
    $this->run_1_min_trades();
  }

  //
  // On data.
  //
  public function on_data($quote)
  {
    $this->last_20[] = $quote['Last'];
    
    // If we have not gone 20 times just keep going
    if(count($this->last_20) < 20)
    {
      return;
    } 
    
    // Get STDs
    $std = $this->standard_deviation($this->last_20);
    
    // SMA
    $sma = array_sum($this->last_20) / count($this->last_20);
    $upper = round($sma + ($std * $this->std_pad), 2);
    $lower = round($sma - ($std * $this->std_pad), 2);    
    
    // We only place a trade if we have no positions on and the order is after 12:00 EST and before 3:00 EST
    if((! $this->position_count()) && 
        (date('G', strtotime($quote['Time'])) >= 12) && 
        (date('G', strtotime($quote['Time'])) <= 14))
    {
          
      // See if we should place a trade.
      if($quote['Last'] > $upper)
      {
        //$this->order($this->symbol, (floor($this->cash / $quote['Last']) * -2));
      } else if($quote['Last'] < $lower)
      {
        $this->order($this->symbol, null, true);
      }
      
    } else 
    {
      // See if we have any orders to close.
      $this->_close_order_check($quote); 
    }
    
    // take first off list.
    array_shift($this->last_20);
    
    // Return happy.
    return;
  }
  
  //
  // On Start of Day.
  //
  public function on_start_of_day($quote)
  {
    // Reset last 20
    $this->last_20 = [];
  }  
  
  //
  // On End of Day.
  //
  public function on_end_of_day($quote)
  {    
    // Sell everything if we have hang over.
    $this->close_all_positions();
  }
  
  // ---------------------- Private Helper Functions ------------------- //
  
  //
  // Close any orders if the time is right.
  //
  private function _close_order_check($quote)
  {
    // Make sure we have positions.
    if(! $pos = $this->get_first_position())
    {
      return false;
    }
    
    // See if the position is short or long.
    if($pos['qty'] > 0)
    {
      // Did we hit our limit? - Close with a profit....
      if($quote['Last'] >= ($pos['price'] + $this->limit))
      {
        $this->order_close('spy'); 
      }
      
      // Did we hit our stop? - Close with a profit....
      if($quote['Last'] <= ($pos['price'] - $this->stop))
      {
        $this->order_close('spy'); 
      }      
    } else 
    {
      // Did we hit our limit? - Close with a profit....
      if($quote['Last'] <= ($pos['price'] - $this->limit))
      {
        $this->order_close('spy'); 
      }

      // Did we hit our stop? - Close with a loss....
      if($quote['Last'] >= ($pos['price'] + $this->stop))
      {
        $this->order_close('spy'); 
      }
    }
    
    
  }

}
	
/* End File */