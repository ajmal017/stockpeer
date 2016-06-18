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
  public function __construct($cli)
  {
    $this->cli = $cli;
    
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
  // Add money to balance. (only use in paper trading).
  //
  public function add_funds($amount)
  {
    $this->account['balance'] = $this->account['balance'] + $amount;
    $this->save();
  }
  
  //
  // Add a margin requirement. (only used in paper trading).
  //
  public function add_margin($amount)
  {
    $this->account['margin_required'] = $this->account['margin_required'] + $amount;
    $this->save();
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