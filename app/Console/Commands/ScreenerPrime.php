<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Cache;
use App\Library\Screener;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ScreenerPrime extends Command 
{
	protected $name = 'stockpeer:screenerprime';
	protected $description = 'Prime the screener we run for possible trades.';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();
    
    // Log user in as spicer
    Auth::loginUsingId(1);    
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('Starting Screener Prime');

    // Just to make sure the cache expired.
    sleep(5);

    // Prime
    $screener = new Screener;
    $screener->spy_percent_away();    
    $screener->spy_weekly_percent_away();

    $this->info('Ending Screener Prime');    
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
