<?php

namespace App\Models;

use \DB;
use \App;
use \DateTime;

class Data1MinSpy extends \Cloudmanic\LaravelApi\Model
{	
	//
	// Get trading dates.
	//
	public function get_trading_dates($start = null, $end = null)
	{
		$dates = [];
		
		// Setup query.
		$query = DB::table('Data1MinSpy');
		$query->select('Data1MinSpyDate');
		$query->groupBy('Data1MinSpyDate');
		$query->orderBy('Data1MinSpyDate', 'asc');

		// Do we have a start date.
		if(! is_null($start))
		{
			$query->where('Data1MinSpyDate', '>=', date('Y-m-d', strtotime($start)));
		}

		// Do we have an end date.
		if(! is_null($start))
		{
			$query->where('Data1MinSpyDate', '<=', date('Y-m-d', strtotime($end)));
		}
					
		// Run query loop through and make it pretty.						
		foreach($query->get() AS $key => $row)
		{
			$dates[] = $row->Data1MinSpyDate;
		}
		
		return $dates;
	}
	
	//
	// Get all data for one date.
	//
	public function get_by_date($date)
	{
		$this->set_col('Data1MinSpyDate', date('Y-m-d', strtotime($date)));
		
		// Strip out pre-market data.
		$this->set_col('Data1MinSpyTime', '06:29:00', '>=');
		$this->set_col('Data1MinSpyTime', '13:05:00', '<=');		
		
		// Set order to start at the start of the day and move to the end.
		$this->set_order('Data1MinSpyTime', 'asc');
		
		return $this->get();
	}
}

/* End File */