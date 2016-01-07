<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Input;
use Queue;
use Request;

class Backtests extends \Cloudmanic\LaravelApi\Controller 
{ 
  public $model_name = 'App\\Models\\BackTests';
	public $validation_create = [];
	public $validation_update = [];
	
  //
  // Setup a backtest. We setup and then 
  // run because we do not want the websockets
  // to get ahead of the server.
  //
  public function setup_backtest()
  {   
    // Insert the backtest into the DB.
    $backtests_model = App::make('App\Models\BackTests');
    
    // Insert the backtest
    $id = $backtests_model->insert([
      'BackTestsName' => Input::get('BackTestsName'),  
      'BackTestsType' => Input::get('BackTestsType'),
      'BackTestsStart' => date('Y-m-d', strtotime(Input::get('BackTestsStart'))),     	
      'BackTestsEnd' => date('Y-m-d', strtotime(Input::get('BackTestsEnd'))),
      'BackTestsStartBalance' => Input::get('BackTestsStartBalance'),
      'BackTestsTradeSize' => Input::get('BackTestsTradeSize'),
      'BackTestsCloseAt' => Input::get('BackTestsCloseAt'), 			
      'BackTestsStopAt' => Input::get('BackTestsStopAt'),  	  	
      'BackTestsStatus' => 'Pending',
      'BackTestsMinDaysExpire' => Input::get('BackTestsMinDaysExpire'),
      'BackTestsMaxDaysExpire' => Input::get('BackTestsMaxDaysExpire'),
      'BackTestsOpenAt' => Input::get('BackTestsOpenAt'),
      'BackTestOpenPercentAway' => Input::get('BackTestOpenPercentAway'),
      'BackTestsMinOpenCredit' => Input::get('BackTestsMinOpenCredit'),
      'BackTestsOneTradeAtTime' => Input::get('BackTestsOneTradeAtTime'),
      'BackTestsTradeSelect' => Input::get('BackTestsTradeSelect')
    ]);
    
    // Give a default name if need be.
    if(! Input::get('name'))
    {
      $backtests_model->update([ 'BackTestsName' => 'Backtest #' . $id ], $id);
    }
      
    // Return Happy
    return $this->api_response([ 'Id' => $id ]);
  }
  
  //
  // Run the back test.
  //
  public function run()
  {
    // Send the backtest to the backtest queue
    Queue::pushOn('stockpeer.com.websocket', 'Backtesting:start', [ 
      'UsersId' => (string) Auth::user()->UsersId,
      'Payload' => [ 'BackTestsId' => Input::get('BackTestsId') ] 
    ]);
    
    // Return Happy
    return $this->api_response();        
  }
}

/* End File */