<?php 

namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SpyIvvArb extends Command 
{
	protected $name = 'stockpeer:spyivvarb';
	protected $description = 'Auto trade our SPY IVV arb strategy';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();
    
    // Log user in.
    Auth::loginUsingId(1);    
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('[' . date('Y-m-d H:i:s') . '] Starting SpyIvvArb');
    
    // Setup Tradier
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken)); 
    
    // Get quotes
    $quotes = $tradier->get_quotes([ 'spy', 'ivv' ]);

    // Log data.
    if(! is_file('/tmp/arb.log'))
    {
      file_put_contents('/tmp/arb.log', json_encode([]));
    }
    
    $raw = file_get_contents('/tmp/arb.log');
    
    $json = json_decode($raw, true);
    
    $json[] = [ 
      'trade_date' => date('Y-m-d H:i:s'), 
      'spy_last' => $quotes[0]['last'], 
      'ivv_last' => $quotes[1]['last'], 
      'spy_change' => $quotes[0]['change_percentage'], 
      'ivv_change' => $quotes[1]['change_percentage'],
      'spy_bid' => $quotes[0]['bid'],
      'spy_ask' => $quotes[0]['ask'],
      'ivv_bid' => $quotes[1]['bid'],
      'ivv_ask' => $quotes[1]['ask']            
    ];
    
    file_put_contents('/tmp/arb.log', json_encode($json));

    // Now figure out the difference.
    $diff = $quotes[1]['last'] - $quotes[0]['last'];
    
    $this->info('Diff: ' . $diff);
    
    // See if we have any open orders. See if we can close.
    if($open = DB::table('ArbTest')->where('ArbTestStatus', 'Open')->first())
    {
      if(($diff >= 1.09) && ($diff <= 1.11))
      {
        DB::table('ArbTest')->where('ArbTestId', $open->ArbTestId)->update([
          'ArbTestSpyClose' => $quotes[0]['last'],
          'ArbTestIvvClose' => $quotes[1]['last'],
          'ArbTestStatus' => 'Closed', 
          'ArbTestUpdatedAt' => date('Y-m-d H:i:s')
        ]);
      } else
      {
        $this->info('Open Trade Not Closed');
        return false;
      }
    }
    
    if($diff >= 1.15)
    {
      // Long SPY, Short IVV
      DB::table('ArbTest')->insert([
        'ArbTestSpyShort' => 'No',
        'ArbTestDiff' => $diff,
        'ArbTestSpyOpen' => $quotes[0]['last'],
        'ArbTestIvvOpen' => $quotes[1]['last'],
        'ArbTestStatus' => 'Open', 
        'ArbTestUpdatedAt' => date('Y-m-d H:i:s'),
        'ArbTestCreatedAt' => date('Y-m-d H:i:s')
      ]);      
      
      $this->info('New Trade');
      
    } else if($diff <= 1.10)
    {
      // Short SPY, Long IVV
      DB::table('ArbTest')->insert([
        'ArbTestSpyShort' => 'Yes',
        'ArbTestDiff' => $diff,
        'ArbTestSpyOpen' => $quotes[0]['last'],
        'ArbTestIvvOpen' => $quotes[1]['last'],
        'ArbTestStatus' => 'Open', 
        'ArbTestUpdatedAt' => date('Y-m-d H:i:s'),
        'ArbTestCreatedAt' => date('Y-m-d H:i:s')
      ]);  
      
      $this->info('New Trade');           
    }
    
    
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