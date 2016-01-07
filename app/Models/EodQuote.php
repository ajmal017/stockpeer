<?php

namespace App\Models;

use \DB;
use \App;
use \DateTime;

class EodQuote extends \Cloudmanic\LaravelApi\Model
{	
	public $no_account = true;
	
	//
	// Get a quote for a certain day and ticker.
	//
	public function get_quote($ticker, $date)
	{
		// First we get the symbol id.
		$symbols_model = App::make('App\Models\Symbols');
		if(! $sym_id = $symbols_model->get_symbol_id($ticker))
		{
			return false;
		}
		
		// Setup query
		$this->set_col('EodQuoteSymbolId', $sym_id);  
		$this->set_col('EodQuoteDate', date('Y-m-d', strtotime($date)));
		$d = $this->get();
		
		// Return data.
		return (count($d)) ? $d[0] : false;  			
	}
	
	//
	// This function will return an array of dates
	// and precentages for all stock prices based on 
	// the number of days passed in by $days. The idea here
	// we make a moving range were we record the high and low.
	// This is useful data for selling spreads so we now how far 
	// out we can go based on past data. If we pass in a $success_down
	// we count the number of accurances down from that number.
	//
	public function get_stock_movement_ranges($ticker, $start, $end, $days, $success_down = null, $success_up = null)
	{
		$rt = [ 
			'symbol' => $ticker, 
			'start_date' => $start, 
			'end_date' => $end, 			
			'max_up' => 0, 
			'max_down' => 0, 
			'avg_up' => 0, 
			'avg_down' => 0, 
			'count_total' => 0,
			'count_up' => 0, 
			'count_down' => 0,
			'success_down_val' => $success_down,
			'success_down' => 0,
			'success_up_val' => $success_up,
			'success_up' => 0,			
			'prct_count' => [], 			
			'data' => [] 
		];
	
		// First we get the symbol id.
		$symbols_model = App::make('App\Models\Symbols');
		if(! $sym_id = $symbols_model->get_symbol_id($ticker))
		{
			return false;
		}
	
		// WE can't have the $end date be too far out.
		$tmp = $days + 2;
		$date = new DateTime($end);
		$current_date = new \DateTime();
		$current_date->modify("-$tmp day");
		
		if($date > $current_date)
		{
			$end = $current_date->format('Y-m-d');
		}		

		// Get all the quotes for this ticker.
		$quotes = DB::table('EodQuote')->select([ 'EodQuoteDate', 'EodQuoteClose' ])
											->where('EodQuoteSymbolId', $sym_id)
											->where('EodQuoteDate', '>=', $start)
											->where('EodQuoteDate', '<=', $end)
											->orderBy('EodQuoteDate', 'asc')
											->get();
	
		// Loop through the quotes and figure out the ranges.
		foreach($quotes AS $key => $row)
		{		
			// Get the stock quote X number of days in the future.
			$out_quote = $this->_get_stock_quote_days_away($sym_id, $row->EodQuoteDate, $days);			
			
			// Build obj to add to the $rt.
			$rt['data'][] = [
				'start' => $row->EodQuoteDate,
				'end' => $out_quote->EodQuoteDate,
				'start_price' => $row->EodQuoteClose,
				'end_price' => $out_quote->EodQuoteClose,
				'diff' => round(($out_quote->EodQuoteClose - $row->EodQuoteClose), 2),
				'prct_change' => round((($out_quote->EodQuoteClose - $row->EodQuoteClose) / $row->EodQuoteClose) * 100, 2)
			];
		}
	
		// Figure out the max change.
		$up = [];
		$down = [];		
		foreach($rt['data'] AS $key => $row)
		{
			// Figure out max up
			if($row['prct_change'] > $rt['max_up'])
			{
				$rt['max_up'] = $row['prct_change'];
			}
			
			// Figure out max down
			if($row['prct_change'] < $rt['max_down'])
			{
				$rt['max_down'] = $row['prct_change'];
			}
			
			// Storage the averages
			if($row['prct_change'] > 0)
			{
				$up[] = $row['prct_change'];
			} else
			{
				$down[] = $row['prct_change'];
			}	
			
			// Range count.
			$base = round($row['prct_change'], 0);
			
			if(! isset($rt['prct_count'][$base]))
			{
				$rt['prct_count'][$base] = 0;
			}
			
			$rt['prct_count'][$base]++;
		}
		
		// Figure out the average move up / down.
		$rt['count_up'] = count($up);
		$rt['count_down'] = count($down);	
		$rt['avg_up'] = ($rt['count_up'] > 0) ? round(array_sum($up) / $rt['count_up'], 2) : 0;
		$rt['avg_down'] = ($rt['count_down'] > 0) ? round(array_sum($down) / $rt['count_down'], 2) : 0;		
		
		// Total periods
		$rt['count_total'] = $rt['count_up'] + $rt['count_down'];
		
		// Success down
		if(! is_null($success_down))
		{
			$failed = 0;
			foreach($rt['prct_count'] AS $key => $row)
			{
				// Just movements down.
				if($key > $success_down)
				{
					continue;
				}
				
				if($key <= $success_down)
				{
					$failed = $failed + $row;
				}
			}
			
			$rt['success_down'] = round((($rt['count_total'] - $failed) / $rt['count_total']), 2);
		}
		
		// Success up
		if(! is_null($success_up))
		{
			$failed = 0;
			foreach($rt['prct_count'] AS $key => $row)
			{			
				// Just movements down.
				if($key < $success_up)
				{
					continue;
				}
				
				if($key >= $success_up)
				{
					$failed = $failed + $row;
				}
			}
			
			$rt['success_up'] = round((($rt['count_total'] - $failed) / $rt['count_total']), 2);
		}		
		
		// Return the data.
		return $rt;
	}
	
	// ------------------- Private Helper Functions ---------------------- //
	
	//
	// Get the next quote based on a future date. For example this is for
	// saying "what was the price of the stock 45 days from now?". 45 days from now
	// might not be a trading day so we do some magic to round up to the next trading day.
	//
	private function _get_stock_quote_days_away($sym_id, $today, $days)
	{
		// Figure out the date of the right side of the range.
		$date = new DateTime($today);
		$date->modify("+$days day");
		$out_date = $date->format("Y-m-d");	
	
		// If we do not find the correct date we move it up one. 
		if(! $d = DB::table('EodQuote')->select([ 'EodQuoteDate', 'EodQuoteClose' ])->where('EodQuoteSymbolId', $sym_id)->where('EodQuoteDate', $out_date)->first())
		{
			return $this->_get_stock_quote_days_away($sym_id, $today, ($days+1));
		}
	
		// Return quote
		return $d;
	}
}

/* End File */