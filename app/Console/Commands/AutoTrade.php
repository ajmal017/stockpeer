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

class AutoTrade extends Command 
{
  private $type = '';
  
	private $types_map = [
  	
  	'45-dte-spy-put-credit-spreads' => [
    	
      'class' => 'PutCreditSpreads',
      'symbol' => 'SPY',
      'time_base' => '1 Minute',
      'data_driver' => 'App\Autotrade\DataDrivers\OptionsChain' 
      	
  	]
	
	];
	
	protected $name = 'stockpeer:autotrade';
	protected $description = 'Start an instance of our auto trader.';

  //
  // Create a new command instance.
  //
	public function __construct()
	{
		parent::__construct();		
		
    // No DB logging.
    DB::connection()->disableQueryLog();		
    
    // Log user in as spicer (maybe fix this later)
    Auth::loginUsingId(1);    
	}

  //
  // Execute the console command.
  //
	public function fire()
	{  	
  	// Validate the type we passed in.
  	if(! in_array($this->argument('type'), array_keys($this->types_map)))
  	{
    	$this->error('Unknown type passed in. Needs to be: ' . implode(', ', array_keys($this->types_map)));
    	return false;
  	} else
  	{
    	$this->type = $this->argument('type');
  	}
  	
    $this->info('[' . date('Y-m-d G:i:s') . '] Starting AutoTrade - ' . $this->argument('type') . '.'); 
    
    // Load the drivers.
    $data_driver = new $this->types_map[$this->type]['data_driver']($this, $this->types_map[$this->type]['symbol']);
    
    // Create instance of this type and run with it.
    $class = 'App\Autotrade\\' . $this->types_map[$this->type]['class'];
    $auto_trade = new $class($this, $this->types_map[$this->type]['time_base'], $data_driver); 	
    
    // Run the auto trade instance
    $auto_trade->run();

    // All done. (really should never get here).
    $this->info('[' . date('Y-m-d G:i:s') . '] Ending AutoTrade - ' . $this->argument('type') . '.');
	}

  //
  // Get the console command arguments.
  //
	protected function getArguments()
	{
		return [
			[ 'type', InputArgument::REQUIRED, '[ ' . implode(', ', array_keys($this->types_map)) . ' ]' ],			
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
