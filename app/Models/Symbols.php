<?php

namespace App\Models;

use \DB;

class Symbols extends \Cloudmanic\LaravelApi\Model
{	
	public $no_account = true;
	
	//
	// Return the id of the symbol based on the short version.
	//
	public function get_symbol_id($ticker)
	{
		$this->set_col('SymbolsShort', strtoupper($ticker));
		if(! $sbm = $this->get())
		{
			return false;
		} else
		{
			return $sbm[0]['SymbolsId'];
		}		
	}
	
	//
	// Create a quick referance index.
	//
	public function get_index()
	{
		$data = [];
		
		foreach($this->get() AS $key => $row)
		{
			$data[$row['SymbolsShort']] = $row['SymbolsId'];
		}
		
		return $data;
	}
}

/* End File */