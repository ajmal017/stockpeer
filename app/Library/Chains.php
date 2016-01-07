<?php

//
// By: Spicer Matthews
// Email: spicer@cloudmanic.com
// Date: 9/16/2014
// Description: Help class for dealing with options chains.
//

namespace App\Library;

class Chains
{
	private $_expire_index = 'OptionsEodExpiration';
	private $_strike_index = 'OptionsEodStrike';	

	//
	// Set expire index.
	//
	public function set_expire_index($col)
	{
		$this->_expire_index = $col;
	}
	
	//
	// Set strike index.
	//
	public function set_strike_index($col)
	{
		$this->_strike_index = $col;
	}	

	//
	// Return an option by strike, expire, and type from the chain.
	//
	public function get_option(&$chain, $type, $expire, $strike)
	{
		foreach($chain[$type] AS $key => $row)
		{
			if(($row[$this->_strike_index] == $strike) && ($row[$this->_expire_index] == $expire))
			{
				return $row;
			}
		}

		return false;
	}

	//
	// Return the option that is X number of strikes away.
	//
	public function get_option_away(&$chain, $expire, $strike, $away, $type, $up_down)
	{
		foreach($chain[$type] AS $key => $row)
		{
			if($up_down == 'down')
			{
				if(($row[$this->_strike_index] == ($strike - $away)) && ($row[$this->_expire_index] == $expire))
				{
					return $row;
				}
			} else if($up_down == 'up')
			{
				if(($row[$this->_strike_index] == ($strike + $away)) && ($row[$this->_expire_index] == $expire))
				{
					return $row;
				}
			}
		}

		return false;
	}

	//
	// Get strikes from a chain.
	//
	public function get_strikes_from_chain(&$chain)
	{
		$rt = [];
		$tmp = [];

		foreach($chain['put'] AS $key => $row)
		{
			$tmp[$row[$this->_strike_index]] = $row[$this->_strike_index];
		}

		foreach($tmp AS $key => $row)
		{
			$rt[] = $key;
		}

		// Sort
		sort($rt);

		return $rt;
	}

	//
	// Get expirations from a chain.
	//
	public function get_expiration_from_chain(&$chain)
	{
		$rt = [];
		$tmp = [];

		foreach($chain['put'] AS $key => $row)
		{
			$tmp[$row[$this->_expire_index]] = $row[$this->_expire_index];
		}

		foreach($tmp AS $key => $row)
		{
			$rt[] = $key;
		}

		return $rt;
	}
}

/* End File */