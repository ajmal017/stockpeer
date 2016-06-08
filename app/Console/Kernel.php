<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel 
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
  	'App\Console\Commands\Backtest',
		'App\Console\Commands\WebSockets',
		'App\Console\Commands\GetOptionsEod',
		'App\Console\Commands\Random',
		'App\Console\Commands\GetData1Min',
		'App\Console\Commands\MarkAssets',
		'App\Console\Commands\SpyIvvArb',
		'App\Console\Commands\GetTradierHistory',
		'App\Console\Commands\PositionsManage',
		'App\Console\Commands\GetSymbolEod',
		'App\Console\Commands\DayTrade',
		'App\Console\Commands\Import1MinData',
		'App\Console\Commands\CachePrime',
		'App\Console\Commands\ImportPastData',
		'App\Console\Commands\ImportEodOptions',
		'App\Console\Commands\ScreenerPrime',
		'App\Console\Commands\RecordFuturesTrades',
		'App\Console\Commands\AutoTrade'
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
  	$ts = date('Y-m-d-H.i.s');
  	
  	//$schedule->command('stockpeer:random')->cron('* 06-12 * * 1-5')->sendOutputTo('/tmp/random.log');
  	//$schedule->command('stockpeer:spyivvarb')->cron('* 06-12 * * 1-5')->sendOutputTo('/tmp/spyivvarb.log');
  	//$schedule->command('stockpeer:managepositions')->cron('* * * * *'); // Daemon does this now
  	
  	$schedule->command('stockpeer:getsymboleod')->dailyAt('13:10');   
    $schedule->command('stockpeer:markassets')->dailyAt('13:40');
    $schedule->command('stockpeer:gettradierhistory')->dailyAt('14:05');
    
    $schedule->command('stockpeer:cacheprime')->dailyAt('15:15');
    $schedule->command('stockpeer:cacheprime')->dailyAt('3:15');
    
    $schedule->command('stockpeer:importeodoptions')
      ->sendOutputTo(storage_path() . '/logs/stockpeer.importeodoptions.' . $ts . '.log')
      ->emailOutputTo('spicer@stockpeer.com')
      ->dailyAt('15:00');  
    
    
    // Deal with 1min data collection. (6:29am - 6:59am)
    for($i = 29; $i <= 59; $i++)
    {
      $schedule->command('stockpeer:getdata1min')->dailyAt('6:' . $i);
      $schedule->command('stockpeer:screenerprime')->dailyAt('6:' . $i);    
    }
    
    $schedule->command('stockpeer:getdata1min')->cron('* 07-12 * * 1-5'); // 7:00am - 12:59pm
    $schedule->command('stockpeer:getdata1min')->cron('2 13 * * 1-5'); // 1:02                
	
    $schedule->command('stockpeer:screenerprime')->cron('* 07-13 * * 1-5'); // 7:00am - 11:59pm    
	}

}

/* End File */