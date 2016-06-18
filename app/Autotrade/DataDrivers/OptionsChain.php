<?php
  
namespace App\Autotrade\DataDrivers;

use App;
use Auth;
use Crypt;
use Carbon\Carbon;

class OptionsChain
{
  private $cli = null;
  private $symbol = null;
  private $tradier = null;
  
  //
  // Construct.
  //
  public function __construct($cli, $symbol)
  {
    $this->cli = $cli;
    $this->symbol = $symbol;
    
    // Setup tradier
    $this->tradier = App::make('App\Library\Tradier');
    $this->tradier->set_token(Crypt::decrypt(Auth::user()->UsersTradierToken));
  }
  
  //
  // Pass in an array and get a list of quotes
  //
  public function get_quotes($symbols)
  {
    return $this->tradier->get_quotes($symbols);
  }
  
  //
  // Get data. Anytime someone wants data we call this function
  // and we make calls to Tradier to return the data. Some parent
  // function should manage how often this is called.
  //
  // $now - Carbon instance of time. 
  //
  public function get_data($now)
  {
    $rt = [ 'stock' => [], 'chain' => [] ];
    
    // Get the current SPY stock price.
    $stock = $this->tradier->get_quotes([ $this->symbol ]);
    $rt['stock'] = $stock[0];    
    
    // Get Expiration dates.
    $expirations = $this->tradier->get_option_expiration_dates($this->symbol);
    
    // Loop through expire dates looking for trades.
    foreach($expirations AS $key => $row)
    {      
      // Get a option chain.
      $rt['chain'][$row] = $this->tradier->get_option_chain($this->symbol, $row); 
    }
    
    // Return the data.
    return $rt;
  } 
}

/* End File */