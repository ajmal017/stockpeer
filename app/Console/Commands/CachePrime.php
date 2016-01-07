<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Cache;
use Coinbase;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CachePrime extends Command 
{
	protected $name = 'stockpeer:cacheprime';
	protected $description = 'Run this to prime the in-memory cache.';

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
    $this->info('Starting CachePrime');

    $this->_prime_eod_symbols();
    $this->_prime_eod_options();
 
    $this->info('Ending CachePrime');    
	}
	
	//
	// Prime end of day options data.
	//
  private function _prime_eod_options()
  {
    // Load up SPY Data
    $optionseod_model = App::make('App\Models\OptionsEod');
    $days = $optionseod_model->get_trade_days('SPY', '2005-01-01', date('Y-m-d'));
    
    Cache::forever('Options.Eod.Dates', $days);
    
    $lasts = [];
    
    // Loop through days.
    foreach($days AS $key => $row)
    {
      // --------------- Deal With Puts ----------------- //
      $csv = [];
      
      $data = DB::table('OptionsEod')
                ->select('OptionsEodType', 'OptionsEodExpiration', 'OptionsEodStrike', 'OptionsEodLast', 
                          'OptionsEodBid', 'OptionsEodAsk', 'OptionsEodImpliedVol', 'OptionsEodDelta', 'OptionsEodSymbolLast')
                ->where('OptionsEodSymbolId', 1)
                ->where('OptionsEodType', 'put')
                ->where('OptionsEodQuoteDate', $row)
                ->orderby('OptionsEodExpiration', 'asc')
                ->get();
    
      // Grab the last symbol pice
      $lasts[$row] = (isset($data[0])) ? $data[0]->OptionsEodSymbolLast : 0;
    
      // Save space by storing as CSV
      foreach($data AS $key2 => $row2)
      {
        unset($row2->OptionsEodSymbolLast);
        $csv[] = implode(',', (array) $row2);
      }
    
      // Store in cache
      Cache::forever('Options.Eod.SPY.Puts.' . $row, $csv);
      
      // --------------- Deal With Calls ----------------- //
      
      $csv = [];
      
      $data = DB::table('OptionsEod')
                ->select('OptionsEodType', 'OptionsEodExpiration', 'OptionsEodStrike', 'OptionsEodLast', 
                          'OptionsEodBid', 'OptionsEodAsk', 'OptionsEodImpliedVol', 'OptionsEodDelta')
                ->where('OptionsEodSymbolId', 1)
                ->where('OptionsEodType', 'call')
                ->where('OptionsEodQuoteDate', $row)
                ->orderby('OptionsEodExpiration', 'asc')
                ->get();
    
      // Save space by storing as CSV
      foreach($data AS $key2 => $row2)
      {
        $csv[] = implode(',', (array) $row2);
      }
    
      // Store in cache
      Cache::forever('Options.Eod.SPY.Calls.' . $row, $csv);    
    }
    
    // Store the Last prices for the SPY.
    Cache::forever('Options.Eod.SPY.Lasts', $lasts);    
  }
  
  //
  // Create memcache for end of day prices.
  //
  public function _prime_eod_symbols()
  {
    // Vix.
    $vix_rank = [];
    $vix_running = [];
    $prime = [];    
    
    // Get VIX data from Tradier.
    $tradier = App::make('App\Library\Tradier');
    $tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
    $data = $tradier->get_historical_pricing('vix', 'daily', '1/1/2000', date('n/j/Y'));

    // Loop through data.              
    foreach($data AS $key => $row)
    {
      if(count($vix_running) >= 30)
      {
        array_shift($vix_running);
      } 
    
      $vix_running[] = $row['close'];
      
      $prime[$row['date']] = $row['close'];
      
      // Calculate the IVR
      if(count($vix_running) >= 30)
      {
        $l = 0;
        $s = 0;
        
        foreach($vix_running AS $key2 => $row2)
        {
          if($row2 < $row['close'])
          {
            $l++;
          } 
          
          if($row2 == $row['close'])
          {
            $s++;
          } 
          
          $vix_rank[$row['date']] = number_format((($l + (0.5 * $s)) / count($vix_running)) * 100, 2);
        }    
      }
    }
    
    // Store the Last prices for the IVR. (This is an estimate of IVR)
    Cache::forever('EodQuote.EodQuoteClose.Snp.IVR', $vix_rank); 
    
    // Store the Last prices for the VIX.
    Cache::forever('EodQuote.EodQuoteClose.VIX', $prime);      
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
