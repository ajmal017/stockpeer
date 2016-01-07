<?php 

namespace App\Console\Commands;

use DB;
use App;
use Mail;
use Exception;
use App\Library\Tradeking;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetOptionsEod extends Command 
{
  private $_trade_king = null;
  private $_symbols = [ 'SPY', 'IWM' ];  
	protected $name = 'stockpeer:getoptionseod';
	protected $description = 'Import EOD options from the Tradeking API.';

  //
  // Create a new command instance.
  // 
	public function __construct()
	{
		parent::__construct();
		
    // No DB logging.
    DB::connection()->disableQueryLog();
			
    // Setup keys/secrets for access
    $consumer_key = env('TRADEKING_CONSUMER_KEY');
    $consumer_secret = env('TRADEKING_CONSUMER_SECRET');
    $access_token = env('TRADEKING_ACCESS_TOKEN');
    $access_secret = env('TRADEKING_ACCESS_SECRET');		
		
    // Start the tradeking library.
    $this->_trade_king = new Tradeking($consumer_key, $consumer_secret, $access_token, $access_secret);		
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('[' . date('n-j-Y g:i:s a') . '] Getting a EOD Options quotes for ' . implode(',', $this->_symbols));

    $this->_get_options();
		
    $this->info('[' . date('n-j-Y g:i:s a') . '] Done getting 5min quotes for ' . implode(',', $this->_symbols) . ' and archiving it.');
	}

	//
	// Get all the possible options for the stocks we want.
	//
	private function _get_options()
	{
		$options = [];
		$options_model = App::make('App\Models\FiveMinSpy');
			
		// Setup our models.
		$symbols_model = App::make('App\Models\Symbols');
		$optionseod_model = App::make('App\Models\OptionsEod');
		
		// Lets get an index of all the underlying symbols.
		$symbol_index = $symbols_model->get_index();   
		
		// Loop through the different symbols.
		foreach($this->_symbols AS $key => $row)
		{
			$options[$row] = [];

			// Get a stock quote for this symbol.
			$stock = $this->_trade_king->get_stock_quote($row);
			
			try {
  			// Get expire dates.
  			$ex_dates = $this->_trade_king->get_option_expire_dates($row);
			  
				// See if we have an entry in the Symbols table for this symbol
				if(! isset($symbol_index[strtoupper($row)]))
				{
					$sym_id = $symbols_model->insert([ 'SymbolsShort' => strtoupper($row) ]);
					$symbol_index = $symbols_model->get_index();
				} else
				{
					$sym_id = $symbol_index[strtoupper($row)];
				}			  
			  
			  // Parse the response			  
				foreach($ex_dates AS $key2 => $row2)
				{
					$ops = $this->_trade_king->get_option_by_symbol_expire($row, $row2);
					
					foreach($ops AS $key3 => $row3)
					{												  					
						// Insert the data into the OptionsEod table.
						try {
  						$optionseod_model->insert([
  						  'OptionsEodSymbolId' => $sym_id,
  						  'OptionsEodSymbolLast' => $stock['last'],
  						  'OptionsEodType' => $row3['put_call'],
  						  'OptionsEodExpiration' => date('Y-m-d', strtotime($row3['xdate'])),
  						  'OptionsEodQuoteDate' => date('Y-m-d', strtotime($row3['date'])),
  						  'OptionsEodStrike' => $row3['strikeprice'],
  						  'OptionsEodLast' => $row3['last'],
  						  'OptionsEodBid' => $row3['bid'],
  						  'OptionsEodAsk' => $row3['ask'],
  						  'OptionsEodVolume' => $row3['vl'],
  						  'OptionsEodOpenInterest' => (isset($row3['openinterest'])) ? str_ireplace(',', '', $row3['openinterest']) : 0,
  						  'OptionsEodImpliedVol' => $row3['imp_Volatility'],
  						  'OptionsEodDelta' => (isset($row3['idelta'])) ? $row3['idelta'] : 0,
  						  'OptionsEodGamma' => (isset($row3['igamma'])) ? $row3['igamma'] : 0,
  						  'OptionsEodTheta' => (isset($row3['itheta'])) ? $row3['itheta'] : 0,
  						  'OptionsEodVega' => (isset($row3['ivega'])) ? $row3['ivega'] : 0,
  						]);
						} catch(Exception $e) {
              
              // Mail and tell stockpeer admin about this.
              Mail::send('emails.system-errors.Exception', 
                        [ 'operation' => 'stockpeer:getoptionseod (001)', 'error' => $e->getMessage(), 'other' => $row3 ], 
                        function($message)
              {
                $message->to('support@stockpeer.com', 'Spicer Matthews')->subject('Stockpeer Exception Caught - getoptionseod');
              });
              
              die("\nFailed With Exception error.\n");              
						}
					
					}
					
					// Sleep a bit
					sleep(5);	
				}				
			} catch(OAuthException $E) 
			{
				$this->error('Exception caught!');
				$this->error('Response: ' . $E->lastResponse);
			}
			
			$this->info('[' . date('n-j-Y g:i:s a') . '] Done EOD Options quotes data for ' . $row . '.');
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