<?php

namespace App\Http\Controllers\Api\V1;

use \App;
use \Input;
use \Request;

class EodQuote extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];	
	
	//
	// RSI study.
	//
	// http://stockpeer.dev/api/v1/eodquote/p_l_rsi_based?format=php&symbol=spy&rsi_bottom=30&rsi_top=70&close_buy_percent=0.11&close_buy_limit_percent=0.03&close_sell_percent=0.11&close_sell_limit_percent=0.07&allow_long=1&allow_short=0&period=14
	//
	public function p_l_rsi_based()
	{
		$eodquote_model = App::make('App\Models\EodQuote');
		$rsi = App::make('App\Library\Rsi');
		
		// Set the period
		$rsi->set_period(Input::get('period'));
		
		// First we get the symbol id.
		$symbols_model = App::make('App\Models\Symbols');
		if(! $sym_id = $symbols_model->get_symbol_id(Input::get('symbol')))
		{
			return $this->api_response([], 0);
		}		
		
		// Get all data.
		$eodquote_model->set_col('EodQuoteSymbolId', $sym_id);
		$eodquote_model->set_order('EodQuoteDate', 'asc');
		$periods = $eodquote_model->get();
		
	  // Load the data so we can review the RSI
	  foreach($periods AS $key => $row)
	  {
			$rsi->add_period($row['EodQuoteDate'], $row['EodQuoteClose']);
	  }
	  
	  // Get the RSI values.
	  $rsis = $rsi->get();
	  
	  // Setup vars to manage backtest.
	  $allow_short = Input::get('allow_short');
	  $allow_long = Input::get('allow_long');
	  $balance = $start_balance = 10000;
	  $start_date = '';
	  $end_date = '';
	  $p_l = 0;
	  $success = 0;
	  $failed = 0;
	  $trades = [];
	  $rsi_bottom = Input::get('rsi_bottom');
	  $rsi_top = Input::get('rsi_top');
	
		// Setup buy vars (RSI < $rsi_bottom)
	  $close_buy_percent = Input::get('close_buy_percent');
	  $close_buy_limit_percent = Input::get('close_buy_limit_percent');  
	  $open_buy = null;
	  $open_buy_shares = 0;
	  $open_buy_date = '';
	
		// Setup sell vars (RSI > $rsi_top)
	  $close_sell_percent = Input::get('close_sell_percent');
	  $close_sell_limit_percent = Input::get('close_sell_limit_percent');  
	  $open_sell = null;
	  $open_sell_shares = 0; 
		$open_sell_date = '';  
	  
		// Time for some poor man backtesting.
		foreach($periods AS $key => $row)
		{		
			// Start date.
			if($key == 0)
			{
				$start_date = $row['EodQuoteDate'];
			}
			
			// End Date.
			$end_date = $row['EodQuoteDate'];
			
			// Long: Do we have a trade on for buying.
			if($allow_long)
			{
				if(! is_null($open_buy))
				{
					// See if we hit our buy target.
					if($row['EodQuoteClose'] >= ($open_buy * (1 + $close_buy_percent)))
					{				
						$p_l = (($row['EodQuoteClose'] - $open_buy) * $open_buy_shares);	
						$balance = $balance + ($open_buy * $open_buy_shares) + $p_l;				
						
						$trades[] = [ 'start_date' => $open_buy_date, 'end_date' => $row['EodQuoteDate'], 'qty' => $open_buy_shares, 'open_price' => $open_buy, 'close_price' => $row['EodQuoteClose'], 'type' => 'long', 'profit_loss' => $p_l ];
										
						$open_buy = null;
						$open_buy_shares = 0;
						$success++;
					} else if($row['EodQuoteClose'] <= ($open_buy - ($open_buy * $close_buy_limit_percent))) // See if we hit our limit target.
					{
						$p_l = ($row['EodQuoteClose'] - $open_buy) * $open_buy_shares;
						$balance = $balance + ($open_buy * $open_buy_shares) + $p_l;				
						
						$trades[] = [ 'start_date' => $open_buy_date, 'end_date' => $row['EodQuoteDate'], 'qty' => $open_buy_shares, 'open_price' => $open_buy, 'close_price' => $row['EodQuoteClose'], 'type' => 'long', 'profit_loss' => $p_l ];				
						
						$open_buy = null;
						$open_buy_shares = 0;
						$failed++;
					}			
				}
			}
			
			// Short: Do we have a trade on for buying.
			if($allow_short)
			{
				if(! is_null($open_sell))
				{
					// See if we hit our limit target.
					if($row['EodQuoteClose'] >= ($open_sell * (1 + $close_sell_limit_percent)))
					{				
						$p_l = (($row['EodQuoteClose'] - $open_sell) * $open_sell_shares);
						$balance = $balance + ($open_sell * $open_sell_shares) - $p_l;				
						$trades[] = [ 'start_date' => $open_sell_date, 'end_date' => $row['EodQuoteDate'], 'qty' => $open_sell_shares, 'open_price' => $open_sell, 'close_price' => $row['EodQuoteClose'], 'type' => 'short', 'profit_loss' =>  ($p_l * -1) ];
						
						$open_sell = null;
						$open_sell_shares = 0;
						$failed++;				
					} else if($row['EodQuoteClose'] <= ($open_sell - ($open_sell * $close_sell_percent))) // success
					{				
						$p_l = (($open_sell - $row['EodQuoteClose']) * $open_sell_shares);				
						$balance = ($balance + ($open_sell * $open_sell_shares)) + $p_l;
						$trades[] = [ 'start_date' => $open_sell_date, 'end_date' => $row['EodQuoteDate'], 'qty' => $open_sell_shares, 'open_price' => $open_sell, 'close_price' => $row['EodQuoteClose'], 'type' => 'short', 'profit_loss' =>  $p_l ];
						
						$open_sell = null;
						$open_sell_shares = 0;
						$success++;				
					}			
				}
			}		
			
			// Long: Should we buy?
			if($allow_long)
			{
				if(($rsis[$row['EodQuoteDate']] < $rsi_bottom) && (is_null($open_buy)))
				{
					if(is_null($open_sell))
					{
						$open_buy_shares = floor(($balance / 2) / $row['EodQuoteClose']);
					} else
					{
						$open_buy_shares = floor($balance / $row['EodQuoteClose']);				
					}
					
					// Use all the money if you your not running short side of things.
					if(! $allow_short)
					{
						$open_buy_shares = floor($balance / $row['EodQuoteClose']);				
					}
					
					$balance = $balance - ($open_buy_shares * $row['EodQuoteClose']);
					$open_buy = $row['EodQuoteClose'];
					$open_buy_date = $row['EodQuoteDate'];
				}
			}
			
			// Short: Should be sell?
			if($allow_short)
			{
				if(($rsis[$row['EodQuoteDate']] > $rsi_top) && (is_null($open_sell)))
				{
					if(is_null($open_buy))
					{
						$open_sell_shares = floor(($balance / 2) / $row['EodQuoteClose']);
					} else
					{
						$open_sell_shares = floor($balance / $row['EodQuoteClose']);				
					}
		
					// Use all the money if we are just looking at the short side.
					if(! $allow_long)
					{
						$open_sell_shares = floor($balance / $row['EodQuoteClose']);
					}
		
					$balance = $balance - ($open_sell_shares * $row['EodQuoteClose']);	
					$open_sell = $row['EodQuoteClose'];
					$open_sell_date = $row['EodQuoteDate'];
				}
			}		
		}
		
		// Set the summery.
		$rt = [
			'period' => Input::get('period'),
			'allow_short' => $allow_short,
			'allow_long' => $allow_long,		
			'rsi_bottom' => $rsi_bottom,
			'rsi_top' => $rsi_top,
			'p_l' => $balance - $start_balance,
			'success' => $success,
			'failed' => $failed,
			'occurrences' => $success + $failed,
			'success_rate' => 0,
			'close_sell_percent' => $close_sell_percent * 100,
			'close_sell_limit_percent' => $close_sell_limit_percent * 100,
			'close_buy_percent' => $close_buy_percent * 100,
			'close_buy_limit_percent' => $close_buy_limit_percent * 100,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'start_balance' => $start_balance,
			'end_balance' => $balance,
			'trades' => $trades
		];
		
		// Figure out success rate.
		if($rt['success'] > 0)
		{
			$rt['success_rate'] = round(($rt['success'] / $rt['occurrences']) * 100, 2);
		}
		
		// Do special things for a CSV return.
		$headers = [ 'start', 'end', 'qty', 'open_price', 'close_price', 'type', 'profit_loss' ];
		$this->set_csv_return($headers, $rt['trades'], 'stockpeer_' . Input::get('symbol') .'p_l_rsi_based.csv');		
		
		// Return happy
		return $this->api_response($rt);		
	}
	
	//
	// This returns a nice summery of all data for a
	// range in a stock movement.
	//
	public function stock_movement_ranges()
	{		
		$eodquote_model = App::make('App\Models\EodQuote');
		
		// Run query
		$rt = $eodquote_model->get_stock_movement_ranges(
			Input::get('symbol'), 
			Input::get('start'), 
			Input::get('end'), 
			Input::get('days'), 
			Input::get('down'), 
			Input::get('up')
		);
		
		// Make sure the query was happy.
		if(! $rt)
		{
			// Not Return happy
			return $this->api_response([], 0);			
		}
	
		// Do special things for a CSV return.
		$headers = [ 'start', 'end', 'start_price', 'end_price', 'diff', 'prct_change' ];
		$this->set_csv_return($headers, $rt['data'], 'stockpeer_' . Input::get('symbol') .'movment_ranges.csv');
	
		// Return happy
		return $this->api_response($rt);
	}
}

/* End File */