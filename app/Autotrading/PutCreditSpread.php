<?php
  
namespace App\Autotrading;
  
use App;
  
class PutCreditSpread
{
  private $tradier_token = null;
  
  //
  // Set Tradier token.
  //
  public function set_tradier_token($token)
  {
    $this->tradier_token = $token;
  }
  
  //
  // Weekly SPY Percent Away
  //
  public function spy_weekly_percent_away()
  {
    $rt = [];
    $day_of_week = date('D');
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token($this->tradier_token);
    
		// Set the signal for what we are auto trading.
		$signals = [

			'buy' => [
				'type' => 'precent-away',
				'value' => 2.5,
				'action' => 'credit-spread',
				'spread_width' => 2,
				'min_credit' => 0.10,			
			]
			
		];
    
    // Get the current SPY stock price.
    $stock = $tradier->get_quotes([ 'spy' ]);
    
    // Figure out the strike price that is the min we can sell.
    $tmp = $stock['last'] - ($stock['last'] * ($signals['buy']['value'] / 100));    
    $fraction = $tmp - floor($tmp);
    $min_sell_strike = ($fraction >= .5) ? (floor($tmp) + .5) : floor($tmp);
     
    // Get a list of all possible expirations
    $expirations = $tradier->get_option_expiration_dates('spy');
    
    // Get the day of the week
    if($day_of_week == 'Fri')
    {
      $expire_date = $expirations[1];
    } else
    {
      $expire_date = $expirations[0];      
    }
    
    // Get a option chain.
    $chain = $tradier->get_option_chain('spy', $expire_date);    
    
    // Index the chain by strikes.
    foreach($chain AS $key => $row)
    {      
      // Only want puts.
      if($row['option_type'] == 'call')
      {
        continue;
      }
      
      // Make sure this put is not above the current price.
      if($row['strike'] > $min_sell_strike)
      {
        continue;
      }
      
      // Find the strike that is x points away.
      if(! $buy_leg = $this->_find_by_strike($chain, 'put', ($row['strike'] - $signals['buy']['spread_width'])))
      {
        continue;
      }
      
      // See if there is enough credit.
      $credit = $row['bid'] - $buy_leg['ask'];
      if($credit < $signals['buy']['min_credit'])
      {
        continue;
      }
      
			// Figure out the credit spread amount.
			$buy_cost = $row['ask'] - $buy_leg['bid'];
			$mid_point = ($credit + $buy_cost) / 2;	
      
      $rt[] = [
        'timestamp' => date('n-j-Y g:i:s a'),
        'timestamp_df1' => date('n/j/y g:i:s a'),
        'sell_leg' => $row['strike'],
        'buy_leg' => $buy_leg['strike'],
        'expire' => $expire_date,
        'expire_df1' => date('n/j/y', strtotime($expire_date)),       
        'credit' => number_format($credit, 2),
        'midpoint' => number_format($mid_point, 2),
        'precent_away' => number_format((1 - $row['strike'] / $stock['last']) * 100, 2),
        'occ_sell' => $row['symbol'],
        'occ_buy' => $buy_leg['symbol'] 
      ];
    }
    
    // Return happy.
    return $rt;
  }

  //
  // SPY Percent Away
  //
  public function spy_percent_away()
  {
    $rt = [];
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token($this->tradier_token);
    
		// Set the signal for what we are auto trading.
		$signals = [

			'buy' => [
  			'symbol' => 'spy',
				'type' => 'precent-away',
				'value' => 4,
				'action' => 'put-credit-spread',
				'spread_width' => 2,
				'min_credit' => 0.18,	
				'max_days_to_expire' => 45,
				'min_days_to_expire' => 0						
			]
			
		];
    
    // Get the current SPY stock price.
    $stock = $tradier->get_quotes([ $signals['buy']['symbol'] ]);
    
    // Figure out the strike price that is the min we can sell.
    $tmp = $stock['last'] - ($stock['last'] * ($signals['buy']['value'] / 100));    
    $fraction = $tmp - floor($tmp);
    $min_sell_strike = ($fraction >= .5) ? (floor($tmp) + .5) : floor($tmp);    
    
    // Figure out the strike price that is the min we can sell.
    $tmp = $stock['last'] - ($stock['last'] * ($signals['buy']['value'] / 100));    
    $fraction = $tmp - floor($tmp);
    $min_sell_strike = ($fraction >= .5) ? (floor($tmp) + .5) : floor($tmp);
     
    // Get a list of all possible expirations
    $expirations = $tradier->get_option_expiration_dates($signals['buy']['symbol']);

    // Loop through expire dates looking for trades.
    foreach($expirations AS $key => $row)
    {
      // Days to expire.
      $date1 = date_create("now");
      $date2 = date_create($row);
      $diff = date_diff($date1, $date2);
      
      // Don't want to go too far out.
      if($diff->days > $signals['buy']['max_days_to_expire'])
      {
        continue;
      }
      
      // Don't want to go too close out.
      if($diff->days < $signals['buy']['min_days_to_expire'])
      {
        continue;
      }  
      
      // Get a option chain.
      $chain = $tradier->get_option_chain($signals['buy']['symbol'], $row); 
      
      // Loop through chain and review.
      foreach($chain AS $key2 => $row2)
      {
        // Only puts
        if($row2['option_type'] != 'put')
        {
          continue;
        }
        
        // Skip open_interest of 0
        if($row2['open_interest'] <= 0)
        {
          continue;
        }
        
        // Skip strikes that are higher than our min strike.
        if($row2['strike'] > $min_sell_strike)
        {
          continue;          
        }
        
        // Find the strike that is x points away.
        if(! $buy_leg = $this->_find_by_strike($chain, 'put', ($row2['strike'] - $signals['buy']['spread_width'])))
        {
          continue;
        }
        
        // See if there is enough credit.
        $credit = $row2['bid'] - $buy_leg['ask'];
        if($credit < $signals['buy']['min_credit'])
        {
          continue;
        }    
        
        // Figure out the credit spread amount.
        $buy_cost = $row2['ask'] - $buy_leg['bid'];
        $mid_point = ($credit + $buy_cost) / 2;	           
        
        // We have a winner
        $rt[] = [
          'timestamp' => date('n-j-Y g:i:s a'),
          'timestamp_df1' => date('n/j/y g:i:s a'),
          'sell_leg' => $row2['strike'],
          'buy_leg' => $buy_leg['strike'],
          'expire' => $row,
          'expire_df1' => date('n/j/y', strtotime($row)),       
          'credit' => number_format($credit, 2),
          'midpoint' => number_format($mid_point, 2),
          'precent_away' => number_format((1 - $row2['strike'] / $stock['last']) * 100, 2),
          'occ_sell' => $row2['symbol'],
          'occ_buy' => $buy_leg['symbol'] 
        ];
      }
    }

    // Return happy.
    return $rt;
  } 
  
  // --------------------- Private Helper Functions ------------------------ //
  
  //
  // Find a strike price that is X number of strikes below.
  //
  private function _find_by_strike(&$chain, $type, $strike)
  {    
    foreach($chain AS $key => $row)
    {
      // Only want puts.
      if($row['option_type'] != $type)
      {
        continue;
      }
      
      if($strike == $row['strike'])
      {
        return $row;
      }
    }
    
    return false;    
  } 
  
}

/* End File */