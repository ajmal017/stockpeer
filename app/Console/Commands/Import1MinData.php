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
	protected $description = 'Import 1min data from kibot.com (and data from Jose).';

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

    // /CL Jose data - ES
    $file = '/Users/spicer/Dropbox/Apps/Stockpeer/data/Futures1MinData/future_1m_ES_data.csv';
    $cont = file_get_contents($file);
    $rows = explode("\n", $cont);
    
    foreach($rows AS $key => $row)
    {
      // "Date","Time","Open","High","Low","Close","Up","Down"
      
      // Skip first row.
      if($key == 0)
      {
        continue;
      }
      
      $data = explode(",", $row);

      // We be done.
      if(! isset($data[2]))
      {
        continue;
      }

      // Insert data.      
      DB::table('Data1MinFutEs')->insert([
        'Data1MinFutEsOpen' => $data[2],
        'Data1MinFutEsHigh' => $data[3],
        'Data1MinFutEsLow' => $data[4],
        'Data1MinFutEsClose' => $data[5],                
        'Data1MinFutEsDate' => date('Y-m-d', strtotime($data[0])),  
        'Data1MinFutEsTime' => $data[1],
        'Data1MinFutEsCreatedAt' => date('Y-m-d G:i:s')                  
      ]);
    }

/*
    // /CL Jose data - CL
    $file = '/Users/spicer/Dropbox/Apps/Stockpeer/data/Futures1MinData/future_cl_1m_data.csv';
    $cont = file_get_contents($file);
    $rows = explode("\n", $cont);
    
    foreach($rows AS $key => $row)
    {
      // "Date","Time","Open","High","Low","Close","Up","Down"
      
      // Skip first row.
      if($key == 0)
      {
        continue;
      }
      
      $data = explode(",", $row);

      // We be done.
      if(! isset($data[2]))
      {
        continue;
      }

      // Insert data.      
      DB::table('Data1MinFutCl')->insert([
        'Data1MinFutClOpen' => $data[2],
        'Data1MinFutClHigh' => $data[3],
        'Data1MinFutClLow' => $data[4],
        'Data1MinFutClClose' => $data[5],                
        'Data1MinFutClDate' => date('Y-m-d', strtotime($data[0])),  
        'Data1MinFutClTime' => $data[1],
        'Data1MinFutClCreatedAt' => date('Y-m-d G:i:s')                  
      ]);
    }
*/

/*
    // IWM Kibot data
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
*/
    
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
