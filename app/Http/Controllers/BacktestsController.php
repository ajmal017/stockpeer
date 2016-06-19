<?php
  
namespace App\Http\Controllers;  

use DB;
use App;
use View;
use Input;
use Queue;
use DateTime;
use Session;

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
  // Get a backtested result. (by hash)
  //
  public function get($id)
  {
    $bt = (array) DB::table('BackTests')->where('BackTestsId', $id)->first();
    return $bt;
  }
  
  //
  // Get trades.
  //
  public function get_trades($id)
  {
    $rt = [];
    
    $dd = DB::table('BackTestTrades')
      ->where('BackTestTradesTestId', $id)
      ->orderBy('BackTestTradesId', 'asc')
      ->get();
      
    // Clean up data
    foreach($dd AS $key => $row)
    {
      $rt[] = (array) $row;
    }
    
    return $rt;
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
  
  //
  // Get backtest status.
  //
  public function status($id)
  {
    sleep(1);
    
    // See if we have a session with an index.
    if(Session::has('bt-' . $id))
    {
      $index = Session::get('bt-' . $id);
    } else
    {
      $index = 0;
    }
    
    $rt = [];
    $progress = 0;
    
    // See if we have any new trades
    $dd = DB::table('BackTestTrades')
      ->where('BackTestTradesTestId', $id)
      ->where('BackTestTradesId', '>', $index)
      ->orderBy('BackTestTradesId', 'asc')
      ->get();
      
    // Clean up data
    foreach($dd AS $key => $row)
    {
      $rt[] = (array) $row;
      $index = $row->BackTestTradesId;
    }
    
    // See if we are completed
    $bt = (array) DB::table('BackTests')->where('BackTestsId', $id)->first();
    
    // Figure out progress.
    if(count($rt))
    {
      $last = $rt[count($rt) - 1];
      
      // Figure out how many days this backtest runs.
      $start = new DateTime($bt['BackTestsStart']);
      $end  = new DateTime($bt['BackTestsEnd']);
      $diff = $start->diff($end);
      $total_days = $diff->days;
      
      // Figure out where we are based on the last trade.
      $start = new DateTime($bt['BackTestsStart']);
      $end  = new DateTime($last['BackTestTradesClose']);
      $diff = $start->diff($end);
      $current_days = $diff->days;  

      // figure out the progress
      $progress = number_format(($current_days / $total_days) * 100, 2);           
    }
    
    // Store the index.
    Session::put('bt-' . $id, $index);
    
    // Return happy.
    return [ 'index' => $index, 'status' => $bt['BackTestsStatus'], 'progress' => $progress, 'trades' => $rt ];
  }
  
  //
  // Run a backtest.
  //
  public function run()
  {
    // Send the backtest to the backtest queue
    Queue::pushOn('stockpeer.com.websocket', 'Backtesting:start', [ 
      'UsersId' => "1",
      'Payload' => [ 'BackTestsId' => Input::get('BackTestsId') ] 
    ]);
    
    // Return happy.
    return [];    
  }
  
  //
  // Setup a backtest
  //
  public function setup_backtest()
  {
    // Insert the backtest into the DB.
    $backtests_model = App::make('App\Models\BackTests');
    
    // Insert the backtest
    $id = $backtests_model->insert([
      'BackTestsAccountId' => 1,
      'BackTestsName' => Input::get('BackTestsName'),  
      'BackTestsType' => Input::get('BackTestsType'),
      'BackTestsStart' => date('Y-m-d', strtotime(Input::get('BackTestsStart'))),     	
      'BackTestsEnd' => date('Y-m-d', strtotime(Input::get('BackTestsEnd'))),
      'BackTestsStartBalance' => Input::get('BackTestsStartBalance'),
      'BackTestsTradeSize' => Input::get('BackTestsTradeSize'),
      'BackTestsCloseAt' => Input::get('BackTestsCloseAt'), 			
      'BackTestsStopAt' => Input::get('BackTestsStopAt'),  	  	
      'BackTestsStatus' => 'Pending',
      'BackTestsSpreadWidth' => Input::get('BackTestsSpreadWidth'),
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
    return [ 'Id' => $id ];    
  }
}

/* End File */