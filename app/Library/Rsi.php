<?php
//
// By: Spicer Matthews
// Date: 1/17/2015
// Description: Library to help calculate RSI Values.
//
	
namespace App\Library;

use \App;
use \View;

class Rsi
{
	private $_periods = 14;
	private $_period_values = [];
	private $_rsi_values = [];	
	
	//
	// Construct. 
	//
	public function _construct($periods = 14)
	{
		$this->_periods = 14;
	}
	
	//
	// Set periods.
	//
	public function set_period($period)
	{
		$this->_periods = $period;		
	}
	
	//
	// Add period. A period some period of time we are measuring.
	// For example on a one year chart it might be a day. On a one day
	// chart it might be one min. We pass in the value of the underlying 
	// we are tracking. We assign this period a timestamp.
	//
	public function add_period($timestamp, $value)
	{
		// Clear out past data.
		$this->_rsi_values = [];
				
		$this->_period_values[$timestamp] = $value;
	}
	
	//
	// Return an array with RSI values.
	//
	public function get()
	{
		// We have already processed the RSI.
		if(count($this->_rsi_values))
		{
			return $this->_rsi_values;
		}
		
		// Calcuate RSI.
		$this->_process_rsi();
		
		return $this->_rsi_values;
	}
	
	// ------------------ Private Helper Functions ------------------ //
	
	//
	// Go through the periods we have an calculate the RSI values.
	// To save processing time we don't go back in time. Just add 
	// RSI values for any periods that we have not already calculated.
	//
	//                  100
  //  RSI = 100 - --------
  //               1 + RS
  //
  //  RS = Average Gain / Average Loss
	//
	// http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:relative_strength_index_rsi
	//
	private function _process_rsi()
	{		
		// We need to have enough periods added first 
		if(count($this->_period_values) < $this->_periods)
		{
			// Just zero out the periods before we have enough. 
			foreach($this->_period_values AS $key => $row)
			{
				$this->_rsi_values[$key] = 0;
			}
			
			return false;
		}
		
		// We have at least enough periods to start doing some real math
		$combined = [];
		$winners = [];
		$users = [];
		$last = null;
		$last_avg_losser = null;
		$last_avg_winner = null;		
		foreach($this->_period_values AS $key => $row)
		{
			// First time through loop.
			if(is_null($last))
			{	
				$this->_rsi_values[$key] = 0;
				$last = $row;
				continue;
			}
			
			// Calc winers and losers.
			$change = $row - $last;
			$combined[] = $change;
			
			if($change > 0)
			{
				$losser[] = 0;
				$winner[] = $change;
			} else
			{
				$winner[] = 0;
				$losser[] = $change * -1;
			}
			
			// Need the period amount to continue
			if(count($combined) < $this->_periods)
			{
				$this->_rsi_values[$key] = 0;
				continue;
			}
			
			// Get the RS value. If this is the second time we do a smoothing function.
			if(is_null($last_avg_losser))
			{
				$avg_winner = array_sum($winner) / $this->_periods;
				$avg_losser = array_sum($losser) / $this->_periods;						
			} else
			{
				// Deal with zero.
				if($last_avg_winner == 0)
				{
					$avg_winner = end($winner) / $this->_periods;					
				} else
				{
					$avg_winner = (($last_avg_winner * ($this->_periods - 1)) + end($winner)) / $this->_periods;
				}
				
				// Deal with teh case of Zero
				if($last_avg_losser == 0)
				{
					$avg_losser = end($losser) / $this->_periods;						
				} else
				{
					$avg_losser = (($last_avg_losser * ($this->_periods - 1)) + end($losser)) / $this->_periods;						
				}
			}
			
			// Special cases.	
			if($avg_losser == 0)
			{
				$rsi = 100;
			} else if($avg_winner == 0)
			{
				$rsi = 0;				
			} else
			{
				$RS = $avg_winner / $avg_losser;
				$rsi = 100 - (100 / (1 + $RS));			
			}
			
			// Store RSI value.
			$this->_rsi_values[$key] = $rsi;

			// Pop old data off the top.
			array_shift($winner);
			array_shift($losser);
			array_shift($combined);
			
			// Set the last values
			$last_avg_losser = $avg_losser;
			$last_avg_winner = $avg_winner;		
			$last = $row;								
		}
		 
	}
}

/* End File */