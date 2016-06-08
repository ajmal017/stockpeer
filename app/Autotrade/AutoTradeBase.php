<?php
  
namespace App\Autotrade;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;
use Libraries\Rsi;

class AutoTradeBase
{
  public $cli = null;
  public $time_base = null;
  public $data_driver = null;
  
  //
  // Construct.
  //
  public function __construct($cli, $time_base, $data_driver)
  {
    $this->cli = $cli;
    $this->time_base = $time_base;
    $this->data_driver = $data_driver;
  }
  
  //
  // Run the auto trader.
  //
  public function run()
  {
    // Just keep looping until we are done.
    while(1)
    {
      // Get current time object
      $now = Carbon::now();

      // Fire every min.
      if(($now->second == 0) && ($this->time_base == '1 Minute'))
      {
        $this->on_data($now, $this->data_driver->get_data($now));
      }
      
      // Sleep one second.
      sleep(1);
    }
  }
  
  //
  // We call this when we have data.
  // This function should be overwritten in 
  // a different focused file.
  //
  // $now - Carbon instance of time. 
  // $data - Data that was returned from the data driver.
  //
  public function on_data($now, $data)
  {
    return true;
  }  
}

/* End File */