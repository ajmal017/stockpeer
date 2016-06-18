<?php
  
namespace App\Autotrade\OrdersDrivers;

use App;
use Auth;
use Crypt;
use Carbon\Carbon;

class PaperOrders
{
  private $cli = null;
  private $orders = null;
  private $store_path = '';
  private $account_driver = null;
  private $positions_driver = null;
  private $orders_file = 'orders.json';
  
  //
  // Construct.
  //
  public function __construct($cli, $account_driver, $data_driver, $positions_driver)
  {
    $this->cli = $cli;
    
    // Setup drivers.
    $this->data_driver = $data_driver;
    $this->account_driver = $account_driver;
    $this->positions_driver = $positions_driver;
    
    // Set the storage path.
    $this->store_path = storage_path() . '/papertrade/';
    
    // Setup account file.
    if(! is_file($this->store_path . $this->orders_file))
    {
      $start = [];
      file_put_contents($this->store_path . $this->orders_file, json_encode($start));
    }
    
    // Load the account into memory.
    $this->orders = json_decode(file_get_contents($this->store_path . $this->orders_file), true);
  }  
  
  //
  // Put an order in for a put credit spread.
  // 
  // $buy_leg = symbol (ie. SPY160708P00197500)
  // $sell_leg = symbol (ie. SPY160708P00195500)
  // $type = {limit, market}
  // $credit = some amount
  // $lots = number of contracts to buy
  //
  public function order_put_credit_spread($buy_leg, $sell_leg, $type, $credit, $lots = 1)
  {
    // Get quotes for the symbols passed in.
    $quotes = $this->data_driver->get_quotes([ $buy_leg, $sell_leg ]);
    
    // Figure out how much this trade is going to cost in margin.
    $width = $quotes[1]['strike'] - $quotes[0]['strike'];
    $margin = $width * 100 * $lots;
    
    // See if we have enough margin to make this trade
    if($this->account_driver->get_available_to_trade() < $margin)
    {
      return false;
    }
    
    // Set a order_id
    $order_id = uniqid();
    
    // Place the trade.
    $this->orders[] = [
      'order_id' => $order_id, 
      'buy_leg' => $buy_leg,
      'sell_leg' => $sell_leg,
      'lots' => $lots,
      'credit' => $credit,
      'description_buy' => $quotes[0]['description'],
      'description_sell' => $quotes[1]['description'],
      'status' => 'filled'
    ];
    
    // Update the account margin.
    $this->account_driver->add_margin($margin);
    
    // Add the credit to the balance.
    $this->account_driver->add_funds($credit * 100 * $lots);
    
    // Add the positions.
    $this->positions_driver->open_positon($buy_leg, $lots, $order_id);
    $this->positions_driver->open_positon($sell_leg, ($lots * -1), $order_id);
    
    // Save the order.
    $this->save();
  }
  
  //
  // Save the orders information to file. 
  //
  public function save()
  {
    file_put_contents($this->store_path . $this->orders_file, json_encode($this->orders));
  }
}

/* End File */