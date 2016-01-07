<?php
	
namespace Backtesting;

use DB;
use App;
use Libraries\Rsi;

class Rsi1MinData
{
	private $signals = [];
	private $trades = [];
	private $open_trade = null;
	private $balance = 0;
	private $data1minspy_model = null;
	
	//
	// Construct.
	//
	//	$signals = [
	//		'ticker' => 'spy',
	//		
	//		'start' => '1/15/2005', 
	//		
	//		'end' => '1/30/2015',
	//		
	//		'balance' => 10000.00,
	//		
	//		'close_change_success' => 0.20, 
	//		
	//		'close_change_failed' => 0.20, 		
	//		
	//		'put_close_change_success' => 0.30, 
	//		
	//		'put_close_change_failed' => 3.00, 				
	//	];	
	//
	//
	public function  __construct($signals)
	{
		// Set signals
		$this->signals = $signals;
		$this->balance = $signals['balance'];
		
		// Set Models
		$this->data1minspy_model = App::make('Models\Data1MinSpy');
	}
	
	//
	// Run back test.
	//
	public function run()
	{
		// Get the dates we want to backtest.
		$dates = $this->data1minspy_model->get_trading_dates($this->signals['start'], $this->signals['end']);
		
		// Loop through the dates and backtest.
		foreach($dates AS $key => $row)
		{
			$this->_backtest_one_day($row);
		}
		
		$success = 0;
		$failed = 0;
		
		foreach($this->trades AS $key => $row)
		{
			if($row['profit'] > 0)
			{
				$success++;
			} else
			{
				$failed++;
			}
		}		
		
		$rate = round($success / count($this->trades), 2) * 100;

		echo 'Balance: $' . number_format($this->balance, 2) . '<br /><br />';
		echo 'Success Rate: ' . $rate . '%<br /><br />';

		echo 'Trades: ' . count($this->trades) . '<br /><br />';

		echo '<pre>' . print_r($this->trades, TRUE) . '</pre>';
		
	}
	
	// ---------------- Private Helper Functions ----------------- //
	
	//
	// Backtest one day.
	//
	private function _backtest_one_day($date)
	{		
		// Setup RSI object.
		$rsi = new Rsi(14);
		
		// Get data for one date.
		$day = $this->data1minspy_model->get_by_date($date);
		
		// Build the RSI tree.
		foreach($day AS $key => $row)
		{
			$rsi->add_period($row['Data1MinSpyTime'], $row['Data1MinSpyLast']);
		}
		
		// Get RSI array.
		$rsi_index = $rsi->get();

		// Backtest this MoFo
		foreach($day AS $key => $row)
		{
			// Figure out what hour we are on.
			$hour = date('G', strtotime($row['Data1MinSpyTime']));
			
			// We only trade after 9am PST
			if($hour < 11)
			{
				continue;
			}

			// Process put side of this strat.
			$this->_process_put_side($row, $hour, $rsi_index);
			
/*
			// If we do not have an open trade we see if this is the time to open one.
			// We also do not put trades on late in the date.
			if(is_null($this->open_trade) && ($hour < 12))
			{
				
				// Check to see if the RSI reading is under 30
				if($rsi_index[$row['Data1MinSpyTime']] < 25)
				{
					// Figure out how many shares to buy.
					$shares = floor($this->balance / $row['Data1MinSpyLast']);
					
					// Open the trade.
					$this->open_trade = [
						'date' => $row['Data1MinSpyDate'],
						'time' => $row['Data1MinSpyTime'],
						'price' => $row['Data1MinSpyLast'],	
						'rsi' => round($rsi_index[$row['Data1MinSpyTime']], 2),
						'shares' => $shares					 
					];
				}
				
			} else // See if we have some trades to close out.
			{
				
				// Set failed price and success price.
				$failed = $this->open_trade['price'] - $this->signals['close_change_failed'];
				$success = $this->open_trade['price'] + $this->signals['close_change_success'];
				
				// See if we hit our stop limit.
				if(($row['Data1MinSpyLast'] <= $failed) && (! is_null($this->open_trade)))
				{							
					$this->balance = $this->balance + ($row['Data1MinSpyLast'] - $this->open_trade['price']);
					
					// Log trade
					$this->trades[] = [
						'date' => $this->open_trade['date'],
						'open_time' => $this->open_trade['time'],
						'close_time' => $row['Data1MinSpyTime'],
						'open_price' => $this->open_trade['price'],
						'close_price' => $row['Data1MinSpyLast'],						
						'open_rsi' => $this->open_trade['rsi'],
						'eod' => 'no',
						'shares' => $this->open_trade['shares'],
						'profit' => round($row['Data1MinSpyLast'] - $this->open_trade['price'], 2),
						'balance' => $this->balance 
					];
					
					$this->open_trade = null;	
					
					// One trade per day
					return true;				
				}
				
				// See if we hit our upside target
				if(($row['Data1MinSpyLast'] >= $success) && (! is_null($this->open_trade)))
				{				
					$this->balance = $this->balance + ($row['Data1MinSpyLast'] - $this->open_trade['price']);
					
					// Log trade
					$this->trades[] = [
						'date' => $this->open_trade['date'],
						'open_time' => $this->open_trade['time'],
						'close_time' => $row['Data1MinSpyTime'],
						'open_price' => $this->open_trade['price'],
						'close_price' => $row['Data1MinSpyLast'],						
						'open_rsi' => $this->open_trade['rsi'],
						'eod' => 'no',
						'shares' => $this->open_trade['shares'],
						'profit' => round($row['Data1MinSpyLast'] - $this->open_trade['price'], 2),
						'balance' => $this->balance 
					];	
					
					$this->open_trade = null;	
					
					// One trade per day
					return true;												
				}
				
			}
*/
			
/*
			// If we have reached 1pm PST and we still have a trade on we need to close it.
			if(($hour == 13) && (! is_null($this->open_trade)))
			{
				$this->balance = $this->balance + ($row['Data1MinSpyLast'] - $this->open_trade['price']);
				
				// Log trade
				$this->trades[] = [
					'date' => $this->open_trade['date'],
					'open_time' => $this->open_trade['time'],
					'close_time' => $row['Data1MinSpyTime'],
					'open_price' => $this->open_trade['price'],
					'close_price' => $row['Data1MinSpyLast'],						
					'open_rsi' => $this->open_trade['rsi'],
					'eod' => 'yes',
					'shares' => $this->open_trade['shares'],
					'profit' => round($row['Data1MinSpyLast'] - $this->open_trade['price'], 2),
					'balance' => $this->balance 
				];	
				
				$this->open_trade = null;	
				
				// One trade per day
				return true;					
			}
*/
		}		
	}
	
	//
	// Process put sides of the strat.
	//
	private function _process_put_side($row, $hour, $rsi_index)
	{
		// If we do not have an open trade we see if this is the time to open one.
		// We also do not put trades on late in the date.
		if(is_null($this->open_trade) && ($hour < 12))
		{
			// Check to see if the RSI reading is under 30
			if($rsi_index[$row['Data1MinSpyTime']] > 70)
			{
				// Figure out how many shares to buy.
				$shares = floor($this->balance / $row['Data1MinSpyLast']);
				
				// Open the trade.
				$this->open_trade = [
					'date' => $row['Data1MinSpyDate'],
					'time' => $row['Data1MinSpyTime'],
					'price' => $row['Data1MinSpyLast'],
					'type' => 'put',	
					'rsi' => round($rsi_index[$row['Data1MinSpyTime']], 2),
					'shares' => $shares					 
				];
			}
		} else
		{
			
			// Set failed price and success price.
			$failed = $this->open_trade['price'] + $this->signals['put_close_change_failed'];
			$success = $this->open_trade['price'] - $this->signals['put_close_change_success'];
			
			// See if we hit our stop limit.
			if(($row['Data1MinSpyLast'] >= $failed) && (! is_null($this->open_trade)))
			{							
				$this->balance = $this->balance + ($this->open_trade['price'] - $row['Data1MinSpyLast']);
				
				// Log trade
				$this->trades[] = [
					'date' => $this->open_trade['date'],
					'open_time' => $this->open_trade['time'],
					'close_time' => $row['Data1MinSpyTime'],
					'open_price' => $this->open_trade['price'],
					'close_price' => $row['Data1MinSpyLast'],						
					'open_rsi' => $this->open_trade['rsi'],
					'eod' => 'no',
					'type' => $this->open_trade['type'],
					'shares' => $this->open_trade['shares'],
					'profit' => round($this->open_trade['price'] - $row['Data1MinSpyLast'], 2),
					'balance' => $this->balance 
				];
				
				$this->open_trade = null;	
				
				// One trade per day
				return true;				
			}
			
			// See if we hit our success target
			if(($row['Data1MinSpyLast'] <= $success) && (! is_null($this->open_trade)))
			{				
				$this->balance = $this->balance + ($this->open_trade['price'] - $row['Data1MinSpyLast']);
				
				// Log trade
				$this->trades[] = [
					'date' => $this->open_trade['date'],
					'open_time' => $this->open_trade['time'],
					'close_time' => $row['Data1MinSpyTime'],
					'open_price' => $this->open_trade['price'],
					'close_price' => $row['Data1MinSpyLast'],						
					'open_rsi' => $this->open_trade['rsi'],
					'eod' => 'no',
					'type' => $this->open_trade['type'],
					'shares' => $this->open_trade['shares'],
					'profit' => round($this->open_trade['price'] - $row['Data1MinSpyLast'], 2),
					'balance' => $this->balance 
				];	
				
				$this->open_trade = null;	
				
				// One trade per day
				return true;												
			}						
		}
		
		// If we have reached 1pm PST and we still have a trade on we need to close it.
		if(($hour == 13) && (! is_null($this->open_trade)) && ($this->open_trade['type'] == 'put'))
		{
			$this->balance = $this->balance + ($row['Data1MinSpyLast'] - $this->open_trade['price']);
			
			// Log trade
			$this->trades[] = [
				'date' => $this->open_trade['date'],
				'open_time' => $this->open_trade['time'],
				'close_time' => $row['Data1MinSpyTime'],
				'open_price' => $this->open_trade['price'],
				'close_price' => $row['Data1MinSpyLast'],						
				'open_rsi' => $this->open_trade['rsi'],
				'eod' => 'yes',
				'shares' => $this->open_trade['shares'],
				'profit' => round($this->open_trade['price'] - $row['Data1MinSpyLast'], 2),
				'balance' => $this->balance 
			];	
			
			$this->open_trade = null;	
			
			// One trade per day
			return true;					
		}		
		 		
	}
}
	
/* End File */