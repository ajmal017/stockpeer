<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Coinbase;
use Carbon\Carbon;
use App\Library\Helper;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Random extends Command 
{
	protected $name = 'stockpeer:random';
	protected $description = 'Used to run one time random commands.';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
/*
    $this->info('Starting Random');
    
    $r = DB::select('SELECT OptionsEodId, OptionsEodExpiration FROM OptionsEod WHERE WEEKDAY(OptionsEodExpiration) = 5');
    
    foreach($r AS $key => $row)
    {
	    echo $row->OptionsEodId . "\n";
	    
	    // Move back a day.
	    echo $row->OptionsEodExpiration . "\n";
	    $new_date = date('Y-m-d', strtotime($row->OptionsEodExpiration . ' -1 day'));
	    
	    echo $new_date . "\n";
	    
	    //DB::table('OptionsEod')->where('OptionsEodId', $row->OptionsEodId)->update([ 'OptionsEodExpiration' => $new_date ]);
    }
    
*/
/*
    Auth::loginUsingId(1);
    
    $bt = App::make('App\Backtesting\OptionDirectional');
    
    $symbol = 'spy';
    
    // Run the backtest        
    $trades = $bt->run([
      'symbol' => 'spy',
      'cash' => '1000.00',
      'start_date' => '2011-01-01',
      'end_date' => '2015-12-31',
      
      'signals' => [
        'lot_size' => 10,            
        
/*
        'close' => 10,
        
        'max_per_expire_date' => 200,
    
        'days_to_wait_after_loss' => 0,
        
        'debt' => [
          'amount' => 0,
          'payment' => 0,
          'months' => 0
        ],
        
        'stop' => [
          'type' => $backtest->BackTestsStopAt,
          'value' => 0.00
        ], 
    
    
        'buy' => [
    			'symbol' => 'spy',
      		'type' => $backtest->BackTestsOpenAt,
      		'value' => $backtest->BackTestOpenPercentAway,
      		'action' => 'credit-spread',
      		'trade-select' => $backtest->BackTestsTradeSelect,
      		'spread_width' => 2,
      		'min_credit' => $backtest->BackTestsMinOpenCredit,
      		'midpoint' => 'yes',
      		'one_at_time' => $backtest->BackTestsOneTradeAtTime,	
      		'max_days_to_expire' => $backtest->BackTestsMaxDaysExpire,
      		'min_days_to_expire' => $backtest->BackTestsMinDaysExpire
        ]
*/

/*		
        
      ]
    ]); 
    
    $profit = 0;
    
    echo '<pre>' . print_r($bt->trade_log, TRUE) . '</pre>' . "\n";
    
    foreach($bt->trade_log AS $key => $row)
    {      
      $profit = $profit + $row['BackTestTradesProfit'];

      //echo $row['BackTestTradesProfit'] . "\n";
      
//       echo $row['BackTestTradesSymStart'] . ' : ' . $row['BackTestTradesSymEnd'] . ' : ' . $row['BackTestTradesProfit'] . ' : ' . $row['BackTestTradesStrike']. ' - '. $row['BackTestTradesOpenCost'] . ' : ' . $row['BackTestTradesCloseCost'] . "\n";    
    }
    
    echo '<pre>' . print_r($bt->positions, TRUE) . '</pre>';
    
    echo '<pre>' . print_r($profit, TRUE) . '</pre>';
*/
	}
	
  //
  // Get the console command arguments.
  //
	protected function getArguments()
	{
		return [];
	}

	//
	// Get the console command options.
	//
	protected function getOptions()
	{
		return [];
	}
}

/* End File */
