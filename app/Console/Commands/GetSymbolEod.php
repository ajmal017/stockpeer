<?php 
namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetSymbolEod extends Command 
{
  private $_tradier = null;
  private $_symbols = [ 'VIX', 'SPY', 'IWM' ];
	private $_symbols_model = null;
	private $_eodquote_model = null;    
	protected $name = 'stockpeer:getsymboleod';
	protected $description = 'Make a Tradier API call and store end of data data for particular symbols';

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
    
    // Setup Tradier
    $this->_tradier = App::make('App\Library\Tradier');
    $this->_tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken)); 
    
		// Setup our models.
		$this->_symbols_model = App::make('App\Models\Symbols');
		$this->_eodquote_model = App::make('App\Models\EodQuote');     
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    $this->info('[' . date('n-j-Y g:i:s a') . '] Starting Import of GetSymbolEod.');

    $quotes = $this->_tradier->get_quotes($this->_symbols);
    
    // Loop through and store the quotes
    foreach($quotes AS $key => $row)
    {
		  // Lets get an index of all the underlying symbols.
		  if(! $symbol_id = $this->_symbols_model->get_symbol_id($row['symbol']))
		  {
		  	$symbol_id = $this->_symbols_model->insert([ 
		  		'SymbolsShort' => strtoupper($row['symbol']),
		  		'SymbolsFull' => ucwords(strtolower($row['description'])) 
		  	]);
		  }
		  
			// Insert into table.
			$this->_eodquote_model->insert([
				'EodQuoteSymbolId' => $symbol_id,
				'EodQuoteDate' => date('Y-m-d'),
				'EodQuoteOpen' => $row['open'],
				'EodQuoteHigh' => $row['high'],
				'EodQuoteLow' => $row['low'],
				'EodQuoteClose' => $row['last'],
				'EodQuoteVolume' => $row['volume']			
			]);			        
    }

    $this->info('[' . date('n-j-Y g:i:s a') . '] Ending Import of GetSymbolEod.');    
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
