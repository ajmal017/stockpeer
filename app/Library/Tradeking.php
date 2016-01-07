<?php

//
// By: Spicer Matthews
// Email: spicer@cloudmanic.com
// Date: 8/11/2014
// Description: Help make api calls to Tradeking.com
//

namespace App\Library;

use \OAuth;

class Tradeking
{
	private $_oauth = null;
	private $_api_base = 'https://api.tradeking.com/v1';

	//
	// Constructor.
	// 
	public function __construct($consumer_key, $consumer_secret, $access_token, $access_secret)
	{			
		// Setup an OAuth consumer
		$this->_oauth = new OAuth($consumer_key, $consumer_secret, 
															OAUTH_SIG_METHOD_HMACSHA1, 
															OAUTH_AUTH_TYPE_AUTHORIZATION);
		$this->_oauth->setToken($access_token, $access_secret);			
	}
	
	//
	// Return the oauth connection.
	//
	public function get_connection()
	{
		return $this->_oauth;
	}
	
	//
	// Return the api base url.
	//
	public function get_api_base()
	{
		return $this->_api_base;
	}
	
	//
	// Get a stock quote.
	//
	public function get_stock_quote($sym)
	{
		try {
		  // Make a request to the API endpoint
		  $endpoint = $this->_api_base . '/market/ext/quotes.json?symbols=' . $sym;
		  $this->_oauth->fetch($endpoint);
		  
		  // Parse the response
		  $response_info = $this->_oauth->getLastResponseInfo();
		  $r = $this->_oauth->getLastResponse();
		  $rt = json_decode($r, true);
		  
			return $rt['response']['quotes']['quote'];
		  
		} catch(OAuthException $E) 
		{
		  $this->error('Exception caught!');
		  $this->error('Response: ' . $E->lastResponse);
		}			
	}	
	
	//
	// Get all the expire dates for a particular option.
	//
	public function get_option_expire_dates($sym)
	{
		$dates = [];
		
		try {
		  // Make a request to the API endpoint
		  $endpoint = $this->_api_base . '/market/options/expirations.json?symbol=' . $sym;
		  $this->_oauth->fetch($endpoint);
		  		  
		  // Parse the response
		  $response_info = $this->_oauth->getLastResponseInfo();
		  $r = $this->_oauth->getLastResponse();
		  $rt = json_decode($r, true);
			
			// Populate our return.
			if(isset($rt['response']['expirationdates']['date']))
			{
				$dates = $rt['response']['expirationdates']['date'];
			}			
		} catch(OAuthException $E) 
		{
		  $this->error('Exception caught!');
		  $this->error('Response: ' . $E->lastResponse);
		}
		
		return $dates;		
	}
	
	//
	// Get all the options by a symbol and expire date.
	// With $contract_size we can pick which contract size
	// we want to return. If we set this to null it will return both
	// contract sizes of 100 and 10. 
	//
	public function get_option_by_symbol_expire($sym, $date, $contract_size = 100)
	{
		$date = str_ireplace('-', '', $date);
		
		try {
		  // Make a request to the API endpoint
		  $endpoint = $this->_api_base . '/market/options/search.json?symbol=' . $sym . '&unique=xdate&query=xdate-eq:' . $date;
		  $this->_oauth->fetch($endpoint);
		  
		  // Parse the response
		  $response_info = $this->_oauth->getLastResponseInfo();
		  $r = $this->_oauth->getLastResponse();
		  $rt = json_decode($r, true);
		  
		  // Return everything.
		  if(is_null($contract_size))
		  {
				return $rt['response']['quotes']['quote'];
			}
			
			// Strip out just the contract sizes we want.
			$rtt = [];
			foreach($rt['response']['quotes']['quote'] AS $key => $row)
			{
				if($row['contract_size'] == $contract_size)
				{
					$rtt[] = $row;
				}
			}
			
			// Return stripped contract size.
			return $rtt;
			
		} catch(OAuthException $E) 
		{
		  $this->error('Exception caught!');
		  $this->error('Response: ' . $E->lastResponse);
		}
		
		return [];			
	}	
	
	//
	// Collect error.
	//
	public function error($str)
	{
		// We should do something here someday .....
	}	
}

/* End File */