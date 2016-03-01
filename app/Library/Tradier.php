<?php

//
// By: Spicer Matthews
// Email: spicer@cloudmanic.com
// Date: 6/8/2015
// Description: Help make api calls to Tradier.com
//

namespace App\Library;

use DateTime;
use GuzzleHttp\Client;

class Tradier
{
  private $token = null;
  private $errors = [];  
  private $sandbox = false;
  private $last_limit = [ 'allowed' => 0, 'used' => 0, 'available' => 0, 'expires' => 0 ];     
    
  private $base_endpoint = 'https://api.tradier.com/v1/'; 
  private $base_sandbox_endpoint = 'https://sandbox.tradier.com/v1/';
  
  //
  // Set sandbox.
  //
  public function set_sandbox()
  {
    $this->sandbox = true;
  }
  
  //
  // Set the token. This is useful so we do not have to call the auth function all the time.
  //
  public function set_token($token)
  {    
    $this->token = $token; 
  } 
  
  //
  // Returns the rate limit from the last api call.
  //
  public function get_last_rate_limit()
  {
    return $this->last_limit;
  }
  
  //
  // Get last error message.
  //
  public function get_last_error()
  {
    return end($this->errors);
  }

  // ---------------- User Data ---------------------------- //
    
  //
  // Get a user's profile
  //
  public function get_user_profile()
  {
    $d = $this->_send_request('user/profile', 'get_user_profile');
    return (isset($d['profile']['account'])) ? $d['profile']['account'] : false;     
  }  
  
  //
  // Get a user's balances
  //
  public function get_user_balances()
  {
    $d = $this->_send_request('user/balances', 'get_user_balances');
    return (isset($d['accounts']['account'])) ? $d['accounts']['account'] : false;    
  }  
  
  // ---------------- Account Data ---------------------------- //  

  //
  // Get an account's balances
  //
  public function get_account_balances($account_id)
  {
    $d = $this->_send_request('accounts/' . $account_id . '/balances', 'get_account_balances');
    return (isset($d['balances'])) ? $d['balances'] : false;    
  }

  //
  // Get an account's positions
  //
  public function get_account_positions($account_id, $add_quotes = false)
  {
    $d = $this->_send_request('accounts/' . $account_id . '/positions', 'get_account_positions');
    $data = (isset($d['positions']['position'])) ? $d['positions']['position'] : false;  
    
    // No data or don't add quotes.
    if((! $add_quotes) || (! $data))
    {
      return $data;
    } 
    
    $syms = [];
    $quote_index = []; 
    
    // Get a list of all the syms used.
    foreach($data AS $key => $row)
    {
      $syms[] = $row['symbol'];   
    }
    
    // Get the current price for each position.
    if(! $quotes = $this->get_quotes($syms))
    {
      $this->errors[] = 'A call to get_quotes in get_account_positions failed with - ' . $this->get_last_error();
      return [];
    }
    
    // Index up the quotes.
    foreach($quotes AS $key => $row)
    {
      $quote_index[$row['symbol']] = $row;
    }
    
    // Add the quote to each possition.
    foreach($data AS $key => $row) 
    {
      $data[$key]['quote'] = $quote_index[$row['symbol']];
    }
    
    // Return data
    return $data;
  }
  
  //
  // Get account positions broken up into the different order types.
  // I started this to break out credit spread and add more contenxt.
  // This has not been fully tested as I do not trade all types of orders
  // and the Tradier sandbox is not fully funcationing yet for orders and such.
  // PS> I have not thought too much about optimizing this call either.
  // My BIG O Might suck here. :)
  //
  public function get_account_positions_group_by_type($account_id)
  {
    $rt = [ 'stock' => [], 'options' => [], 'multi_leg' => [] ];    
    
    if(! $data = $this->get_account_positions($account_id, true))
    {
      return $rt;
    }
    
    // Add the quote to the data.
    foreach($data AS $key => $row)
    {
      // We guess options are spreads if they have two or more on the same timestamp
      if(isset($row['quote']['option_type']))
      {
        $timestamp = date('n/j/Y H:i:s', strtotime($row['date_acquired']));
        $spreads[$timestamp][] = $row;      
      } else
      {
        $rt['stock'][] = $row;
      }
    }
    
    // Now verify our spreads.
    foreach($spreads AS $key => $row)
    {
      if(count($row) <= 1)
      {
        $rt['options'][] = $row[0];
      } else // Must be a spread
      {
        $cost_basis = 0;
        $close_price = 0;
        
        foreach($row AS $key2 => $row2)
        {
          // TODO: We assume each spread is the same qty.
          $qty = abs($row2['quantity']);
          
          $cost_basis += $row2['cost_basis'];
          
          // Are we short or not (bid or ask).
          if($row2['cost_basis'] > 0)
          {
            $close_price += ($row2['quote']['bid'] * $row2['quantity'] * 100); 
          } else
          {
            $close_price += (($row2['quote']['ask'] * abs($row2['quantity']) * 100) * -1);           
          }
        }
    
        // Debit or credit spread??
        $type = ($cost_basis > 0) ? 'debit' : 'credit';
        $credit = ($type == 'credit') ? ($cost_basis * -1) : 0;      
        $debit = ($type == 'debit') ? $cost_basis : 0;
        
        // Figure out the gain or loss so far.
        // TODO: Test debit side of this.......
        // This credit thing is often just when the Tradier API messes up        
        if($credit > 0)
        {
          if($type == 'credit')
          {
            $gain_loss = $credit + $close_price;
            $precent_to_close = ($gain_loss / $credit) * 100; //(($gain_loss / $credit) > 0) ? (($gain_loss / $credit) * 100) : 0;
          } else
          {
            $gain_loss = $close_price + $debit;
            $precent_to_close = ($gain_loss / $credit) * 100;  //(($gain_loss / $debit) > 0) ? (($gain_loss / $debit) * 100) : 0;        
          }
        } else
        {
          $gain_loss = 0;
          $precent_to_close = 0;            
        }
        
        // Now lets see how this spread is doing.
        // TODO: we are assuming both legs expire on the say date.
        $date1 = new DateTime();
        $date2 = new DateTime($row[0]['quote']['expiration_date']);
        $days_to_expire = date_diff($date1, $date2)->days + 1;
    
        $rt['multi_leg'][] =  [ 
          'timestamp' => date('n-j-Y g:i:s a'),
          'timestamp_df1' => date('n/j/y g:i:s a'),
          'open_timestamp' => (isset($row[0])) ? date('n-j-Y g:i:s a', strtotime($row[0]['date_acquired'])) : '',
          'open_date' => (isset($row[0])) ? date('n/j/y', strtotime($row[0]['date_acquired'])) : '',         
          'type' => $type, 
          'credit' => number_format($credit, 2, '.', ''), 
          'debit' => number_format($debit, 2, '.', ''),
          'cost_basis' => number_format($cost_basis, 2, '.', ''),
          'days_to_expire' => $days_to_expire, 
          'close_price' => number_format($close_price, 2, '.', ''),
          'gain_loss' => number_format($gain_loss, 2, '.', ''),
          'precent_to_close' => number_format($precent_to_close, 2, '.', ''),
          'lots' => $qty,
          'legs' => $row 
        ];                       
      }
    }
    
    // Return happy.
    return $rt;    
  }
  
  //
  // Get an account's history
  //
  public function get_account_history($account_id, $limit = 1000)
  {
    $d = $this->_send_request('accounts/' . $account_id . '/history?limit=' . $limit, 'get_account_history');
    return (isset($d['history']['event'])) ? $d['history']['event'] : false;    
  }    
  
  //
  // Get an account's orders
  //
  public function get_account_orders($account_id, $add_quotes = false)
  {
    $d = $this->_send_request('accounts/' . $account_id . '/orders', 'get_account_orders');
    
    // Make an array if just one item
    if(isset($d['orders']['order']['id']))
    {
      $tmp = $d['orders']['order'];
      $d['orders']['order'] = [ $tmp ];
    }
    
    // Do we add the quotes of this order?
    if($add_quotes && (isset($d['orders']['order'])))
    {
      $syms = [];
      $quote_index = [];
      
      // Loop through and get a list of symbols we need to get a quote for.
      foreach($d['orders']['order'] AS $key => $row)
      {
        // TODO: make this work for other order types.
        switch($row['class'])
        {
          case 'equity':
            $syms[] = $row['symbol'];
          break;
          
          case 'multileg':
            foreach($row['leg'] AS $key2 => $row2)
            {
              $syms[] = $row2['option_symbol'];
            }
          break;
          
          case 'option':
            $syms[] = $row['option_symbol']; 
          break;          
        }
      }
      
      // Get the current price for each position.
      if(! $quotes = $this->get_quotes($syms))
      {
        $this->errors[] = 'A call to get_quotes in get_account_orders failed with - ' . $this->get_last_error();
        return [];
      }

      // Index up the quotes.
      foreach($quotes AS $key => $row)
      {
        $quote_index[$row['symbol']] = $row;
      }
      
      // Add the quotes into the data.
      foreach($d['orders']['order'] AS $key => $row)
      {
        // TODO: make this work for other order types.
        switch($row['class'])
        {
          case 'equity':
            $d['orders']['order'][$key]['quote'] = $quote_index[$row['symbol']];          
          break;          
          
          case 'multileg':
            foreach($row['leg'] AS $key2 => $row2)
            {
              if(isset($quote_index[$row2['option_symbol']]))
              {
                $d['orders']['order'][$key]['leg'][$key2]['quote'] = $quote_index[$row2['option_symbol']];
              } 
            }
          break;
          
          case 'option':
            $d['orders']['order'][$key]['quote'] = $quote_index[$row['option_symbol']]; 
          break;
        }
      }   
    }
    
    return (isset($d['orders']['order'])) ? $d['orders']['order'] : [];    
  }   
  
  //
  // Get an individual order
  //
  public function get_account_order($account_id, $orderid)
  {
    $d = $this->_send_request('accounts/' . $account_id . '/orders/' . $orderid, 'get_account_order');
    return (isset($d['order'])) ? $d['order'] : false;    
  }   
    
  // ---------------- Trading ---------------------------- //    
    
  //
  // Place an order.
  //
  public function place_order($account_id, $order)
  {
    $d = $this->_send_request('accounts/' . $account_id . '/orders', 'place_order', 'post', $order);
    return (isset($d['order'])) ? $d['order'] : false;       
  }
  
  
  // ---------------- Market Data ---------------------------- //
  
  //
  // Get quotes
  //
  public function get_quotes($symbols)
  {
    $d = $this->_send_request('markets/quotes?symbols=' . implode(',', $symbols), 'get_quotes');
  
    if(isset($d['quotes']['quote']))
    {
      if(isset($d['quotes']['quote']['symbol']))
      {
        return [ $d['quotes']['quote'] ];
      } else
      {
        return $d['quotes']['quote'];
      
      }
    } else
    {
      return [];
    }
  }
  
  //
  // Get time and sales
  //
  // $interval = tick, 1min, 5min or 15min
  // $start / $end = 2015-06-08T10:06:00
  // $session_filter = all, open
  //
  public function get_timesales($symbol, $interval = '1min', $start = null, $end = null, $session_filter = 'all')
  {    
    $q = [
      'symbol' => $symbol,
      'interval' => $interval,
      'start' => (! is_null($start)) ? $start : date('Y-m-d 09:30', strtotime('now')),
      'end' => (! is_null($end)) ? $end : date('Y-m-d 16:00', strtotime('now')),
      'session_filter' => $session_filter
    ];
    
    $d = $this->_send_request('markets/timesales?' . http_build_query($q),  'get_timesales');
    return (isset($d['series']['data'])) ? $d['series']['data'] : false;
  } 
  
  //
  // Get an option chain
  //
  public function get_option_chain($symbol, $expiration)
  {    
    $q = [ 'symbol' => $symbol, 'expiration' => $expiration ];
    $d = $this->_send_request('markets/options/chains?' . http_build_query($q),  'get_option_chain');
    return (isset($d['options']['option'])) ? $d['options']['option'] : false;
  }   
  
  //
  // Get an option's strike prices
  //
  public function get_option_strike_prices($symbol, $expiration)
  {
    $q = [ 'symbol' => $symbol, 'expiration' => $expiration ];
    $d = $this->_send_request('markets/options/strikes?' . http_build_query($q),  'get_option_strike_prices');
    return (isset($d['strikes']['strike'])) ? $d['strikes']['strike'] : false;    
  }
  
  //
  // Get option's expiration dates
  //
  public function get_option_expiration_dates($symbol)
  {
    $q = [ 'symbol' => $symbol ];
    $d = $this->_send_request('markets/options/expirations?' . http_build_query($q),  'get_option_expiration_dates');
    return (isset($d['expirations']['date'])) ? $d['expirations']['date'] : false;     
  }  
  
  //
  // Get historical pricing
  //
  // $symbol = symbol
  // $interval = daily, weekly or monthly (default: daily)
  // $start / $end = YYYY-MM-DD
  //
  public function get_historical_pricing($symbol, $interval = 'daily', $start = null, $end = null)
  {    
    $q = [
      'symbol' => $symbol,
      'interval' => $interval,
      'start' => (! is_null($start)) ? date('Y-m-d', strtotime($start)) : '1980-01-01',
      'end' => (! is_null($end)) ? date('Y-m-d', strtotime($end)) : date('Y-m-d', strtotime('now'))
    ];
    
    $d = $this->_send_request('markets/history?' . http_build_query($q),  'get_historical_pricing');
    return (isset($d['history']['day'])) ? $d['history']['day'] : false;
  }  
  
  //
  // Get the intraday status
  //
  public function get_intraday_status()
  {
    $d = $this->_send_request('markets/clock', 'get_intraday_status', 'get');
    return (isset($d['clock'])) ? $d['clock'] : false;
  }  
  
  //
  // Create a streaming session
  //
  public function get_streaming_session()
  {
    $d = $this->_send_request('markets/events/session',  'get_streaming_session', 'post');
    return (isset($d['stream'])) ? $d['stream'] : false;
  }

  // ---------------- Public Helper Functions --------------- //
  
  //
  // Pass in an option chain, option type, and a strike and we return just the option.
  //
  public function get_option_from_type_strike(&$chain, $type, $strike)
  {
    foreach($chain AS $key => $row)
    {
      if(($row['strike'] == $strike) && ($row['option_type'] == $type))
      {
        return $row;
      }
    }    
  }
  
  // ---------------- Private Helper Functions --------------- //
  
  //
  // Send request to API.
  //
  private function _send_request($url, $func, $type = 'get', $post_data = [])
  {
    // Make sure we have a token.
    if(is_null($this->token))
    {
      $this->errors[] = $func . ': No API token set. Please authorize by using auth().';
      return false;      
    }
    
    // Build the url
    if($this->sandbox)
    {
      $url = $this->base_sandbox_endpoint . $url;
    } else
    {
      $url = $this->base_endpoint . $url;      
    }
    
    // Setup headers.
    if($type == 'post')
    {
      $headers = [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Cloudmanic Labs / Tradier PHP',
        'Authorization: Bearer ' . $this->token      
      ];      
    } else
    {
      $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Cloudmanic Labs / Tradier PHP',
        'Authorization: Bearer ' . $this->token      
      ];
    }
    
		// Setup request.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    
		// Is this a post requests?
		if($type == 'post')
		{  		
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		}    
		
		// Send Api request.
		$response = curl_exec($ch);

    // Break up the body and the headers
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
		
		// Build the headers into a nice array.
		$hds = [];
		foreach(explode("\n", $header) AS $key => $row)
		{
  		if(! empty($row))
  		{
  		  $tmp = explode(':', $row);
        
        if(isset($tmp[1]) && $tmp[0])
        {
          $hds[trim($tmp[0])] = trim($tmp[1]);
        }
      }
		}
		
		// Get status code.
		$http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		// Convert body into a PHP array.
		if($http_status_code == '200')
		{
		  $json = json_decode($body, true);
		  
      if(isset($json['errors']))
      {
        foreach($json['errors'] AS $key => $row)
        {
          $this->errors[] = $row; 
        }
      }
		} else
		{
  		$json = [];
		}
	
    // Close curl connection.
		curl_close($ch);	
		
    // Make sure the API returned happy.
    if($http_status_code != '200')
    {
      $this->errors[] = $func . ': Trader API did not return a status code of 200. (' . $http_status_code . ') : ' . $body;
      return false;
    } 
    
    // Update rate limit from this call.
    if(isset($hds['X-Ratelimit-Allowed']))
    {
      $this->last_limit = [
        'allowed' => $hds['X-Ratelimit-Allowed'], 
        'used' => $hds['X-Ratelimit-Used'], 
        'available' => $hds['X-Ratelimit-Available'], 
        'expires' => $hds['X-Ratelimit-Expiry']
      ];
    }
    
    // Return happy.
    return $json;    
  }      
}

/* End File */