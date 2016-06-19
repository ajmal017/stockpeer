<?php 
  
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Mail;
use Queue;
use League\CLImate\CLImate;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Backtest extends Command 
{
	protected $name = 'stockpeer:backtest';
	protected $description = 'Run this command to cycle through a backtest.';

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
    $this->info('[' . date('Y-m-d G:i:s') . '] Starting Backtest #' . $this->argument('BackTestsId') . '.');  	
  	
    // Get the backtest we are running.
    if(! $backtest = DB::table('BackTests')->where('BackTestsId', $this->argument('BackTestsId'))->first())
    {
      $this->error("Backtest not found.");
      return false;
    }
    
    // Log user in as spicer
    Auth::loginUsingId($backtest->BackTestsAccountId); 
    
    // Setup backtest model
    $backtests_model = App::make('App\Models\BackTests');
    $backtesttrades_model = App::make('App\Models\BackTestTrades');       
    
    // Mark when we start the backtest.
    $backtests_model->update([
      'BackTestsClockStart' => date('Y-m-d G:i:s'),
      'BackTestsStatus' => 'Started'
    ], $backtest->BackTestsId);
  	
    // See what type of test we are doing.
    switch($backtest->BackTestsType)
    {
      // LongButterflySpread
      case 'Long Butterfly Spread':
        
        $bt = App::make('App\Backtesting\LongButterflySpread');
        
        $symbol = 'spy';
        
        // Run the backtest        
        $trades = $bt->run([
          'symbol' => $symbol,
          'cash' => $backtest->BackTestsStartBalance,
          'start_date' => $backtest->BackTestsStart,
          'end_date' => $backtest->BackTestsEnd,
          'option_type' => 'calls',
          'min_days_to_expire' => $backtest->BackTestsMinDaysExpire,
          'max_days_to_expire' => $backtest->BackTestsMaxDaysExpire,
          'max_price_to_pay' => 88.00,
          'wing_width_percent' => 0.05
        ], $backtest->BackTestsId); 
        
        die();
        
      break; 
      
      
      // PutCreditSpreads
      case 'Put Credit Spreads':
    
        $bt = App::make('App\Backtesting\PutCreditSpreads');
        
        $symbol = 'spy';

        // Run the backtest        
        $trades = $bt->run([
          'symbol' => $symbol,
          'cash' => $backtest->BackTestsStartBalance,
          'start_date' => $backtest->BackTestsStart,
          'end_date' => $backtest->BackTestsEnd,
          
          'signals' => [
            'lot_size' => $this->get_lot_settings($backtest->BackTestsTradeSize),            
            
            'close' => $this->get_closing_settings($backtest->BackTestsCloseAt),
            
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

/*
            'stop' => [
              'type' => 'short-delta-greater-than',
              'value' => -0.40
            ], 
            
*/
            'buy' => [
        			'symbol' => 'spy',
          		'type' => $backtest->BackTestsOpenAt,
          		'value' => $backtest->BackTestOpenPercentAway,
          		'action' => 'credit-spread',
          		'trade-select' => $backtest->BackTestsTradeSelect,
          		'spread_width' => $backtest->BackTestsSpreadWidth,
          		'min_credit' => $backtest->BackTestsMinOpenCredit,
          		'midpoint' => 'yes',
          		'one_at_time' => $backtest->BackTestsOneTradeAtTime,	
          		'max_days_to_expire' => $backtest->BackTestsMaxDaysExpire,
          		'min_days_to_expire' => $backtest->BackTestsMinDaysExpire
            ]		
            
          ]
        ], $backtest->BackTestsId);      
      
        $summary = $bt->get_trade_summary();
      
      
      break;
    }
    
/*
    // Load the trades into the Orders db.
    foreach($trades AS $key => $row)
    {
      $row['BackTestTradesTestId'] = $backtest->BackTestsId;
      $backtesttrades_model->insert($row);
    }
*/
    
    // Mark when we end the backtest.
    $backtests_model->update([
      'BackTestsCagr' => $summary['cagr'],
      'BackTestsProfit' => $summary['profit'],     
      'BackTestsClockEnd' => date('Y-m-d G:i:s'),
      'BackTestsStatus' => 'Ended',
      'BackTestsWins' => $summary['wins'],       
      'BackTestsLosses' => $summary['losses'], 
      'BackTestsTotalTrades' => $summary['total'], 
      'BackTestsEndBalance' => $summary['end_cash'], 
      'BackTestsWinRate' => $summary['win_rate'], 
      'BackTestsAvgCredit' => $summary['avg_credit'], 
      'BackTestsAvgDaysInTrade' => $summary['avg_days_in_trade'],
      'BackTestsPublicHash' => md5(uniqid())
    ], $backtest->BackTestsId);   
    
    // Tell the app we are done.
    Queue::pushOn('stockpeer.com.websocket', 'Backtesting:done', [ 
      'UsersId' => (string) Auth::user()->UsersId, 
      'Payload' => [ 'action' => 'done' ] 
    ]);    
    
    // Print the trades out.
    //$climate = new CLImate;
    //$climate->table($trades);
    
    //echo '<pre>' . print_r($summary, TRUE) . '</pre>';
    
    $this->info('[' . date('Y-m-d G:i:s') . '] Backtest #' . $backtest->BackTestsId . ' Done.');
	}
	
	//
	// Figure out the closing settings.
	//
	public function get_closing_settings($closing)
	{
    // Let expire.
    if($closing == 'let-expire')
    {
      return [ 'type' => 'hit-target-price', 'value' => 0.00 ];
    }
    
    // Get parts.
    $parts = explode('-', $closing);
    
    return [
      'type' => 'hit-target-price',
      'value' => $parts[1]      
    ]; 
	}
	
	//
	// Figure out lot settings.
	//
	public function get_lot_settings($lot)
	{
    $parts = explode('-', $lot);
    
    return [
      'type' => ($parts[0] == 'fixed') ? 'fixed-lot' : 'percent-of-cash',
      'value' => ($parts[0] == 'fixed') ? $parts[1] : ($parts[1] * 0.01)      
    ];
	}

  //
  // Get the console command arguments.
  //
	protected function getArguments()
	{
		return [
			[ 'BackTestsId', InputArgument::REQUIRED, '[ 1 ]' ],			
		];
	}

  //
  // Get the console command options.
  //
	protected function getOptions()
	{
		return [];
	}

}
