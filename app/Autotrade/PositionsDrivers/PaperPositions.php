<?php
  
namespace App\Autotrade\PositionsDrivers;

use App;
use Auth;
use Crypt;
use Carbon\Carbon;

class PaperPositions
{
  private $cli = null;
  private $positions = null;
  private $store_path = '';
  private $positions_file = 'positions.json';
  private $data_driver = null;
  
  //
  // Construct.
  //
  public function __construct($cli, $data_driver)
  {
    $this->cli = $cli;
    
    // Add drivers
    $this->data_driver = $data_driver;
    
    // Set the storage path.
    $this->store_path = storage_path() . '/papertrade/';
    
    // Setup account file.
    if(! is_file($this->store_path . $this->positions_file))
    {
      $start = [];
      file_put_contents($this->store_path . $this->positions_file, json_encode($start));
    }
    
    // Load the account into memory.
    $this->positions = json_decode(file_get_contents($this->store_path . $this->positions_file), true);
  }  
  
  //
  // Return open positions.
  //
  public function get_positions()
  {
    return $this->positions;
  }
  
  //
  // Open a new position.
  //
  public function open_positon($sym, $qty, $order_id)
  {
    // Get a quote.
    $quotes = $this->data_driver->get_quotes([ $sym ]);
    $quote = $quotes[0];
    
    // Which index do we take.
    if($qty > 0)
    {
      $index = 'bid';
    } else
    {
      $index = 'ask';
    }
    
    // Store the position
    $this->positions[] = [
      'order_ids' => [ $order_id ],
      'symbol' => $sym, 
      'qty' => $qty,
      'price' => $quote[$index]
    ];
    
    $this->save();
  }
  
  
  // 
  // Save account. Write account data to file.
  //
  public function save()
  {
    file_put_contents($this->store_path . $this->positions_file, json_encode($this->positions));
  }  
}

/* End File */