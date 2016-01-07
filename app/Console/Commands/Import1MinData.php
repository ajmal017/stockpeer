<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Coinbase;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Import1MinData extends Command 
{
	protected $name = 'stockpeer:import1mindata';
	protected $description = 'Import 1min data from kibot.com.';

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
    $this->info('Starting Import');

    $file = '/Users/spicer/Dropbox/Stock1MinData/IWM.txt';
    $cont = file_get_contents($file);
    $rows = explode("\n", $cont);
    
    foreach($rows AS $key => $row)
    {
      $data = explode(",", $row);
      
      DB::table('Data1MinIwm')->insert([
        'Data1MinIwmLast' => $data[5],
        'Data1MinIwmVol' => $data[6],
        'Data1MinIwmDate' => date('Y-m-d', strtotime($data[0])),  
        'Data1MinIwmTime' => $data[1],
        'Data1MinIwmCreatedAt' => date('Y-m-d G:i:s')                  
      ]);
    }
    
    $this->info('Ending Import');    
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
