<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use App;
use Auth;
use Crypt;
use Input;
use Request;

class BlogTrades extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];
	
	//
	// Insert by TradeId - (special API just for spicer)
	//
	public function insert_by_tradeid()
	{
    $this->model = null;
    
    // Make sure we passed in a TradesId
    if(! Input::get('TradesId'))
    {
      return $this->api_response([], 0);
    }
    
    // This only works for Spicer's acccount.
    if(Auth::user()->UsersId != 1)
    {
      return $this->api_response([], 0);
    }
    
    // Get the trade
    $trades_model = App::make('App\Models\Trades');
    if(! $trade = $trades_model->get_by_id(Input::get('TradesId')))
    {
      return $this->api_response([], 0);      
    }
    
    if($trade['TradesType'] == 'Put Credit Spread')
    {
      $id = DB::table('BlogTrades')->insertGetId([
        'BlogTradesTitle' => $trade['Title'],
        'BlogTradesTicker' => $trade['TradesStock'],
        'BlogTradesPortfolio' => 'Index Credit Spreads',
        'BlogTradesType' => 'put',
        'BlogTradesExpireDate' => $trade['TradeExpiration'],
        'BlogTradesOpenDate' => $trade['TradesDateStart'],
        'BlogTradesCloseDate' => $trade['TradesDateEnd'],
        'BlogTradesBuyStrike' => $trade['TradesLongLeg1'],
        'BlogTradesSellStrike' => $trade['TradesShortLeg1'],
        'BlogTradesOpenCredit' => $trade['TradesCredit'],
        'BlogTradesCloseDebit' => (($trade['TradesShares'] * $trade['TradesSpreadWidth1']) - $trade['TradesEndPrice']) / ($trade['TradesShares'] * 100),
        'BlogTradesNote' => '',
        'BlogTradesStatus' => 'Active',
        'BlogTradesUpdatedAt' => date('Y-m-d G:i:s'),
        'BlogTradesCreatedAt' => date('Y-m-d G:i:s')                                                                 
      ]);
    } else
    {
      $parts = explode(' ', $trade['Title']);
      
      $id = DB::table('BlogTrades')->insertGetId([
        'BlogTradesTitle' => $trade['Title'],
        'BlogTradesTicker' => $parts[0],
        'BlogTradesPortfolio' => 'Index Credit Spreads',
        'BlogTradesType' => 'put',
        'BlogTradesExpireDate' => date('Y-m-d G:i:s', strtotime($parts[7] . ' ' . $parts[8] . ' ' . $parts[9])),
        'BlogTradesOpenDate' => $trade['TradesDateStart'],
        'BlogTradesCloseDate' => $trade['TradesDateEnd'],
        'BlogTradesBuyStrike' => $parts[4],
        'BlogTradesSellStrike' => $parts[6],
        'BlogTradesOpenCredit' => (($trade['TradesShares'] * ($parts[6] - $parts[4]) * 100) - $trade['TradesStartPrice']) / ($trade['TradesShares'] * 100),
        'BlogTradesCloseDebit' => (($trade['TradesShares'] * ($parts[6] - $parts[4]) * 100) - $trade['TradesEndPrice']) / ($trade['TradesShares'] * 100),
        'BlogTradesNote' => '',
        'BlogTradesStatus' => 'Active',
        'BlogTradesUpdatedAt' => date('Y-m-d G:i:s'),
        'BlogTradesCreatedAt' => date('Y-m-d G:i:s')                                                                 
      ]);      
    }
    
    return $this->api_response([ 'Id' => $id ]);
	}	
}

/* End File */