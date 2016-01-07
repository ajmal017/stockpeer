<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DayTrade extends Command 
{
  private $tradier = null;
  private $daytrades_model = null;
	protected $name = 'stockpeer:daytrade';
	protected $description = 'We run this to see if there are any day trades for us to make.';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();
    
    // Log user in as spicer
    Auth::loginUsingId(1);
    
    // Setup models.
    $this->daytrades_model = App::make('App\Models\DayTrades');
    
    // Setup Tradier
    $this->tradier = App::make('App\Library\Tradier');
    $this->tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));  		
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->log('Starting Day Trading');
    
    // Setup master vars.
    $last_20 = [];
    $std_pad = 2.35;
    $comission = 1.00;
    $limit_order = 0.40;
    $stop_order = 0.40;
    $balance = 10000;
    $shares_to_trade = 50;
    
    // Loop through every min and see if there are things to do.
    while(1)
    {
      // See if today is a weekend
      if($this->isWeekend())
      {
        $this->log('Sleeping.... Today is a weekend. Nothing to do.');
        $last_20 = [];      
        sleep(60);
        continue;         
      }
      
      // We only want to trade after 9am PST 
      if(date('H') < 9) 
      {
        $this->log('Sleeping.... We start trading at 9am PST.');
        $last_20 = [];      
        sleep(60);
        continue;        
      }

      // We only want to trade before 11am PST 
      if(date('H') > 10) 
      {
        $this->log('Sleeping.... We end trading at 11am PST.');
        $last_20 = [];      
        sleep(60);
        continue;        
      }
      
      // Get quote
      $quote = $this->tradier->get_quotes([ 'spy' ]);
    
      $last_20[] = $quote['last'];
          
      // If we have not gone 20 times just keep going
      if(count($last_20) < 20)
      {
        $this->log('Warming up: ' . count($last_20) . ' of 20 SMAs');
        
        sleep(60);
        continue;
      }     
      
      // Get a STD
      $std = $this->standard_deviation($last_20);     
    
      // Setup Bounds
      $sma = array_sum($last_20) / count($last_20);
      $upper = $sma + ($std * $std_pad);
      $lower = $sma - ($std * $std_pad);  
      
      // First we see if we have any orders to close.
      $this->close_orders($quote, $limit_order, $stop_order);

      // Second we see if we have any orders to open.
      $this->open_orders($quote, $upper, $lower, $comission, $shares_to_trade, $limit_order, $stop_order);
      
      // take first off list.
      array_shift($last_20);      
      
      // Sleep wait for next cycle
      sleep(60);
    }
    
    $this->log('Ending Day Trading');    
	}
	
	// ----------- Private Helper Functions ---------- //

	//
	// Here we see if we have any orders to open.
	//
	private function open_orders($quote, $upper, $lower, $comission, $shares_to_trade, $limit_order, $stop_order)
	{
  	$price = 0.00;
    $trade = null;
    
    // Make sure we don't already have an open trade.
    $this->daytrades_model->set_col('DayTradesStatus', 'Open');
    if($this->daytrades_model->get())
    {
      return false;
    }
  	
/*
    // Did we cross the upper bound?
    if($quote['bid'] > $upper)
    {
      $price = $quote['bid'];
      $trade = 'Short Stock';
    }
*/
    
    // Did we cross the lower bound
    if($quote['ask'] < $lower)
    {
      $price = $quote['ask']; 
      $trade = 'Long Stock';
    }
    
    // Did we hit a bound
    if(is_null($trade))
    {
      return false;
    }
    
    // Place order.
    $this->daytrades_model->insert([
      'DayTradesSymbolsId' => 1,
      'DayTradesStatus' => 'Open',
      'DayTradesType' => $trade,
      'DayTradesQty' => $shares_to_trade,
      'DayTradesDate' => date('Y-m-d'),
      'DayTradesOpenTime' => date('G:i:s'),
      'DayTradesOpenPrice' => $price,
      'DayTradesOpenCommission' => 1.00
    ]); 
    
    $this->log("Placed a $trade order at $price.");
	}
	
	//
	// Here we see if we have any orders to close.
	//
	private function close_orders($quote, $limit_order, $stop_order)
	{
  	// When we opened the order we placed a stop / limit order.
  	// Here we are checking on the status of that order and seeing
  	// if we need to update our trade log.
  	
    // But..... for now we are paper trading.
    
    // Make sure we have  an open trade.
    $this->daytrades_model->set_col('DayTradesStatus', 'Open');
    if($t = $this->daytrades_model->get())
    {
      $trade = $t[0];
    } else
    {
      return false;
    }  	
    
    // See if we hit our limit order target - Long
    if(($trade['DayTradesType'] == 'Long Stock') && ($quote['bid'] >= ($trade['DayTradesOpenPrice'] + $limit_order)))
    {
      $profit = (($quote['bid'] - $trade['DayTradesOpenPrice']) * $trade['DayTradesQty']) - $trade['DayTradesOpenCommission'] - 1.00;
      
      $this->daytrades_model->update([
        'DayTradesStatus' => 'Closed',
        'DayTradesCloseTime' => date('G:i:s'),
        'DayTradesClosePrice' => $quote['bid'],
        'DayTradesCloseCommission' => 1.00,
        'DayTradesProfit' => $profit
      ], $trade['DayTradesId']);
      
      $this->log("Closed long stock position at \$$quote[bid] for a profit of \$$profit");
      
      return true;      
    }

    // See if we hit our stop order target - Long
    if(($trade['DayTradesType'] == 'Long Stock') && ($quote['bid'] <= ($trade['DayTradesOpenPrice'] - $stop_order)))
    {
      $profit = (($quote['bid'] - $trade['DayTradesOpenPrice']) * $trade['DayTradesQty']) - $trade['DayTradesOpenCommission'] - 1.00;    
      
      $this->daytrades_model->update([
        'DayTradesStatus' => 'Closed',
        'DayTradesCloseTime' => date('G:i:s'),
        'DayTradesClosePrice' => $quote['bid'],
        'DayTradesCloseCommission' => 1.00,
        'DayTradesProfit' => $profit
      ], $trade['DayTradesId']); 
      
      $this->log("Closed long stock position at \$$quote[bid] for a profit of \$$profit");      
      
      return true;            
    }
    
    // See if we hit our limit order target - Short
    if(($trade['DayTradesType'] == 'Short Stock') && ($quote['ask'] <= ($trade['DayTradesOpenPrice'] - $limit_order)))
    {
      $profit = (($trade['DayTradesOpenPrice'] - $quote['ask']) * $trade['DayTradesQty']) - $trade['DayTradesOpenCommission'] - 1.00;
      
      $this->daytrades_model->update([
        'DayTradesStatus' => 'Closed',
        'DayTradesCloseTime' => date('G:i:s'),
        'DayTradesClosePrice' => $quote['bid'],
        'DayTradesCloseCommission' => 1.00,
        'DayTradesProfit' => $profit
      ], $trade['DayTradesId']);  
      
      $this->log("Closed short stock position at \$$quote[ask] for a profit of \$$profit");      
      
      return true;           
    }

    // See if we hit our stop order target - Long
    if(($trade['DayTradesType'] == 'Short Stock') && ($quote['ask'] >= ($trade['DayTradesOpenPrice'] + $stop_order)))
    {
      $profit = (($trade['DayTradesOpenPrice'] - $quote['ask']) * $trade['DayTradesQty']) - $trade['DayTradesOpenCommission'] - 1.00;
      
      $this->daytrades_model->update([
        'DayTradesStatus' => 'Closed',
        'DayTradesCloseTime' => date('G:i:s'),
        'DayTradesClosePrice' => $quote['bid'],
        'DayTradesCloseCommission' => 1.00,
        'DayTradesProfit' => $profit
      ], $trade['DayTradesId']);
      
      $this->log("Closed short stock position at \$$quote[ask] for a profit of \$$profit");       
      
      return true;             
    }
    
    // Nothing closed.
    return false;
	}
	
  //
  // Calculate variance of array
  //
  private function variance($aValues, $bSample = false)
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
  private function standard_deviation($aValues, $bSample = false)
  {
    $fVariance = $this->variance($aValues, $bSample);
    return (float) sqrt($fVariance);
  }	
	
	//
	// Is today a weekend?
	//
	private function isWeekend() 
  {
    return (date('N') >= 6);
  }
  
  //
  // Log...
  //
  private function log($msg)
  {
    $this->info('[' . date('Y-m-d G:i:s') . '] ' . $msg);
  }
	
  //
  // Get the console command arguments.
  //
	protected function getArguments()
	{
		return [];
	}

	//
	// Get the console command options.
	//
	protected function getOptions()
	{
		return [];
	}
}

/* End File */
