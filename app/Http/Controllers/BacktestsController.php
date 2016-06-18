<?php
  
namespace App\Http\Controllers;  

use DB;
use App;
use View;
use Input;

class BacktestsController extends Controller 
{
  private $_data = [];
  
	//
	// Construct.
	//
	public function __construct()
	{
		$this->_data['header'] = [
			'title' => 'Options Backtesting Results | Stockpeer',
			'image' => 'https://dukitbr4wfrx2.cloudfront.net/blog/find-the-best-options-broker_25.png',
			'thumb' => 'https://dukitbr4wfrx2.cloudfront.net/blog/find-the-best-options-broker_25_thumb.png',			
			'description' => 'Here we are using the results of a backtest on Stockpeer.com'
		];
	}
	
	//
	// Index.
	///
	public function index()
	{
    return View::make('template.main', $this->_data)->nest('body', 'backtests.index', $this->_data);	
	}	  
  
  //
  // Options Spreads
  //
  public function options_spreads($hash)
  {
    $this->_data['backtest'] = (array) DB::table('BackTests')->where('BackTestsPublicHash', $hash)->first();
    $this->_data['backtest']['Trades'] = [];
    
    // Get trades.
    $trades = DB::table('BackTestTrades')
                ->where('BackTestTradesTestId', $this->_data['backtest']['BackTestsId'])
                ->get();
                
    foreach($trades AS $key => $row)
    {
      $this->_data['backtest']['Trades'][] = (array) $row;
    }
    
    return View::make('template.main', $this->_data)->nest('body', 'backtests.options-spreads-view', $this->_data);	
  }
}

/* End File */