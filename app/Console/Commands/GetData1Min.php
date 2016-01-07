<?php 

namespace App\Console\Commands;

use DB;
use App;
use Auth;
use Crypt;
use Config;
use App\Library\Tradeking;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GetData1Min extends Command 
{
	private $_tradier = null;
	private $_trade_king = null;
	private $_symbols = [ 'SPY', 'IVV', 'IWM', 'VIX' ];
	private $_options = [ 'SPY' ];
	private $_lasts = [];	
	private $_data1minspy_model = null;  
	protected $name = 'stockpeer:getdata1min';
	protected $description = 'Import 1 Min quote data from the Tradier API.';

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
	}

  //
  // Execute the console command.
  //
	public function fire()
	{
    // Get ticker data. (run this one first)
    $this->_store_ticker_data();
		
		// Get options data.
		$this->_store_options_data();		
	}
	
	//
	// Get and store options data.
	//
  private function _store_options_data()
  {
    $symbols_model = App::make('App\Models\Symbols');
    $time = date('H:i:s');
    $date = date('Y-m-d');
    
    // Loop through the options we want to get.
    foreach($this->_options AS $key => $row)
    {
      $sym = strtolower($row);
      $table = 'Options1Min' . ucfirst($sym);
      
      // Get the expire dates
      $expires = $this->_tradier->get_option_expiration_dates($sym);
      
      // Loop through the dates and get the chains.
      foreach($expires AS $key2 => $row2)
      {
        $chain = $this->_tradier->get_option_chain($sym, $row2);
        
        // Loop through the chain and store.
        foreach($chain AS $key3 => $row3)
        { 
          // Get the symbol_id
          if(! $sym_id = $symbols_model->get_symbol_id($row3['symbol']))
          {
            $sym_id = $symbols_model->insert([ 
              'SymbolsType' => 'Option',
              'SymbolsOptionType' => ucfirst(strtolower($row3['option_type'])),              
              'SymbolsStrike' => $row3['strike'], 
              'SymbolsUnderlying' => strtoupper($sym),
              'SymbolsExpire' => $row3['expiration_date'],               
              'SymbolsFull' => $row3['description'],
              'SymbolsShort' => $row3['symbol'] 
            ]);
          }

          // Insert into our database.
          DB::table($table)->insert([
            $table . 'SymbolId' => $sym_id,   	    
            $table . 'Last' => (isset($row3['last'])) ? $row3['last'] : 0,
            $table . 'Bid' => (isset($row3['bid'])) ? $row3['bid'] : 0, 	
            $table . 'Ask' => (isset($row3['ask'])) ? $row3['ask'] : 0,
            $table . 'BidSize' => $row3['bidsize'], 	
            $table . 'AskSize' => $row3['asksize'],
            $table . 'UnderlyingLast' => $this->_lasts[strtolower($row3['underlying'])],      
            $table . 'OpenInterest' => $row3['open_interest'],
            $table . 'Date' => $date, 
            $table . 'Time' => $time
          ]);
        }
      }
    }
    
    $this->info('[' . date('n-j-Y g:i:s a') . '] Done getting 1 min Option data for ' . implode(',', $this->_options) . ' and archiving it.'); 
  }
	
	//
	// Get ticker data.
	//
	private function _store_ticker_data()
	{
  	// EST (server is PST)
    $time = date('H:i:s', strtotime("+3 hours"));
    $date = date('Y-m-d');  	
  	
    // Get quotes
    $quotes = $this->_tradier->get_quotes($this->_symbols);
    
    foreach($quotes AS $key => $row)
    {
      $table = ucfirst(strtolower($row['symbol']));
      
      $this->_lasts[strtolower($row['symbol'])] = $row['last'];
      
      DB::table('Data1Min' . $table)->insert([
        'Data1Min' . $table . 'Last' => $row['last'],
        'Data1Min' . $table . 'Bid' => (isset($row['bid'])) ? $row['bid'] : 0,
        'Data1Min' . $table . 'Ask' => (isset($row['ask'])) ? $row['ask'] : 0,        
        'Data1Min' . $table . 'Vol' => $row['volume'],
        'Data1Min' . $table . 'Change' => $row['change_percentage'],
        'Data1Min' . $table . 'Date' => $date,
        'Data1Min' . $table . 'Time' => $time,
        'Data1Min' . $table . 'CreatedAt' => date('Y-m-d H:i:s')                                                            
      ]);
    }
    
    $this->info('[' . date('n-j-Y g:i:s a') . '] Done getting 1 min Quote data for ' . implode(',', $this->_symbols) . ' and archiving it.');      	
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