<?php

namespace App\Models;

use \DB;
use \App;

class OptionsEod extends \Cloudmanic\LaravelApi\Model
{	
	public $no_account = true;
	private $exclude_lt = null;
	private $exclude_gt = null;	
	
	//
	// Pass in an array for exlcuding things options
	// less than a certain size.
	//
	public function set_exclude_lt($parms)
	{
		$this->exclude_lt = $parms;
	}
	
	// Pass in an array for exlcuding things options
	// greater than a certain size.
	//
	public function set_exclude_gt($parms)
	{
		$this->exclude_gt = $parms;
	}	
	
  //
  // Get a quote on an option by symbol, date, expire, strike, type.
  //
  public function get_quote($symbol, $date, $expire, $strike, $type)
  {
		// Get the id of the symbol we passed in.
		$symbols_model = App::make('App\Models\Symbols');
		$symbol_id = $symbols_model->get_symbol_id($symbol);  
		
		// Setup the query.
		$this->set_col('OptionsEodType', strtolower($type));
		$this->set_col('OptionsEodStrike', $strike);
		$this->set_col('OptionsEodExpiration', date('Y-m-d', strtotime($expire)));
    $this->set_col('OptionsEodQuoteDate', date('Y-m-d', strtotime($date)));
		$this->set_col('OptionsEodSymbolId', $symbol_id);	
    $rt = $this->get();
    
    return (count($rt)) ? $rt[0] : false;
  }
	
	//
	// This returns an array with the puts and calls
	// for a particular symbol on a particular date.
	// $exclude makes it so we just do not include 
	// options that are most likely useless. 
	// For example an option with no OptionsEodOpenInterest
	//
	public function get_chain($symbol, $date, $exclude = true, $sort = 'asc')
	{
		$rt = [ 'last' => 0, 'vix' => 0, 'rsi_14' => 0, 'symbol' => $symbol, 'date' => $date, 'call' => [], 'put' => [] ];
		
		// Get RSI Index.
		$rsi_index = $this->_get_rsi_index($symbol);
		
		// Set RSI
		if(isset($rsi_index[$date]))
		{
			$rt['rsi_14'] = $rsi_index[$date];
		}
		
		// Get the id of the symbol we passed in.
		$symbols_model = App::make('App\Models\Symbols');
		$symbol_id = $symbols_model->get_symbol_id($symbol);
		$vix_id = $symbols_model->get_symbol_id('VIX');				
		
		// It is useful to have the vix so we add it in the chain data.
		$eodquote_model = App::make('App\Models\EodQuote');
		$eodquote_model->set_select('EodQuoteClose');
		$eodquote_model->set_col('EodQuoteSymbolId', $vix_id);
		$eodquote_model->set_col('EodQuoteDate', date('Y-m-d', strtotime($date)));
		
		if($tmp = $eodquote_model->get())
		{			
			$rt['vix'] = $tmp[0]['EodQuoteClose'];
		}	
		
		// Query and get the data.
		$this->set_col('OptionsEodQuoteDate', date('Y-m-d', strtotime($date)));
		$this->set_col('OptionsEodSymbolId', $symbol_id);	
		$this->set_order('OptionsEodExpiration', $sort);
		$this->set_order('OptionsEodStrike', $sort);
		
		// Exclude stuff (less than)?
		if($exclude && $this->exclude_lt)
		{
			foreach($this->exclude_lt AS $key => $row)
			{
				$this->set_col($key, $row, '>');
			}
		}
		
		// Exclude stuff (greater than)?
		if($exclude && $this->exclude_gt)
		{
			foreach($this->exclude_gt AS $key => $row)
			{
				$this->set_col($key, $row, '<');
			}
		}		
		
		foreach($this->get() AS $key => $row)		
		{
			$rt[$row['OptionsEodType']][] = $row;
		}
		
		// Add the last stock price for easy access.
		$rt['last'] = (isset($rt['call'][0])) ? $rt['call'][0]['OptionsEodSymbolLast'] : 0;
		
		return $rt;
	}
	
	//
	// Return a list of dates in $sort order that were 
	// trading days based on the range we send into 
	// this function. 
	//
	public function get_trade_days($symbol, $start, $end, $sort = 'asc')
	{
		$rt = [];
	
		// Get the id of the symbol we passed in.
		$symbols_model = App::make('App\Models\Symbols');
		$symbol_id = $symbols_model->get_symbol_id($symbol);
		
		// Now make the fancy query and get all the dates. 
		$this->set_select('OptionsEodQuoteDate AS Date');
		$this->set_col('OptionsEodQuoteDate', date('Y-m-d', strtotime($start)), '>=');
		$this->set_col('OptionsEodQuoteDate', date('Y-m-d', strtotime($end)), '<=');
		$this->set_col('OptionsEodSymbolId', $symbol_id);
		$this->set_group('OptionsEodQuoteDate');		
		$this->set_order('OptionsEodQuoteDate', $sort);
		foreach($this->get() AS $key => $row)
		{
			$rt[] = $row['Date'];
		}
		
		return $rt;
	} 
	
	// ----------------- Private Helper Functions --------------- //
	
	//
	// Build an Index with RSI values.
	//
	private function _get_rsi_index($symbol)
	{
		$eodquote_model = App::make('App\Models\EodQuote');
		$rsi = App::make('App\Library\Rsi');
		
		// Set the period
		$rsi->set_period(14);		
		
		// First we get the symbol id.
		$symbols_model = App::make('App\Models\Symbols');
		if(! $sym_id = $symbols_model->get_symbol_id($symbol))
		{
			return false;
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
		return $rsi->get();		
	}	
}

/* End File */