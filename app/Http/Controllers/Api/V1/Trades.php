<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Crypt;
use Input;
use Request;

class Trades extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];
	
	//
	// Open a trade.
	//
	public function preview_trade()
	{
    // Set the order
    $order = Input::get('order');
  	
  	// Send order to Tradier
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
    if(! $data = $tradier->place_order(Auth::user()->UsersTradierAccountId, $order))
    {
      return $this->api_response(null, 0, [ 'Tradier' => $tradier->get_last_error() ]);      
    }   
    
    // Return the data.
    return $this->api_response($data);
	}
	
	//
	// Return total gains by year.
	//
	public function pl_by_year($year)
	{
		$rev = 0.00;
	
		// Lets get all the trades for the year. (old way of doing things).
		$trades_model = App::make('App\Models\Trades');
		$trades = $trades_model->get_completed_trades_by_year($year);

		// Loop through and add up the profit.
		foreach($trades AS $key => $row)
		{
			$rev += $row['ProfitRaw'];
		}
		
		// Lets get all the trade for the year (new way of doing things)
		$tradegroups_model = App::make('App\Models\TradeGroups');
		$trades = $tradegroups_model->get_completed_trades_by_year($year);

		// Loop through and add up the profit.
		foreach($trades AS $key => $row)
		{
			$rev += $row['Profit_Loss'];
		}

		
		return $this->api_response([ 'p_l' => $rev, 'p_l_df' => number_format($rev, 2) ]);
	}	
}

/* End File */