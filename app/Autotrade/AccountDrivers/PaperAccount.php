<?php
  
namespace App\Autotrade\AccountDrivers;

use App;
use Auth;
use Crypt;
use Carbon\Carbon;

class PaperAccount
{
  private $cli = null;
  private $tradier = null;
  private $account = null;
  private $store_path = '';
  private $account_file = 'account.json';
  
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
    
    // Set the storage path.
    $this->store_path = storage_path() . '/papertrade/';
    
    // Setup account file.
    if(! is_file($this->store_path . $this->account_file))
    {
      $start = [
        'balance' => 10000.00,
        'margin_required' => 0,
        'completed_trades' => [] 
      ];
      
      file_put_contents($this->store_path . $this->account_file, json_encode($start));
    }
    
    // Load the account into memory.
    $this->account = json_decode(file_get_contents($this->store_path . $this->account_file), true);
  }
  
  //
  // Return the account balance.
  //
  public function get_balance()
  {
    return $this->account['balance'];
  }
  
  //
  // Return the margin_requirement.
  //
  public function get_margin_required()
  {
    return $this->account['margin_required'];
  }
  
  //
  // Return the amount available to trade.
  //
  public function get_available_to_trade()
  {
    return $this->account['balance'] - $this->account['margin_required'];
  }
  
  //
  // Return completed trades.
  //
  public function get_completed_trades()
  {
    return $this->account['completed_trades'];
  }  
  
  // 
  // Save account. Write account data to file.
  //
  public function save()
  {
    file_put_contents($this->store_path . $this->account_file, json_encode($this->account));
  }
}

/* End File */