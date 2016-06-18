<?php
  
namespace App\Autotrade\CleanupDrivers;

use App;
use Auth;
use Crypt;
use Carbon\Carbon;

class PaperCleanup
{
  private $cli = null;
  private $orders = null;
  private $account_driver = null;
  private $positions_driver = null;
  
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
  }  

  //
  // We call this before on_data has been called.
  //
  public function before_on_data()
  {
    return true;
  }

  //
  // We call this after on_data has been called.
  //
  public function after_on_data()
  {
    return true;
  }
}

/* End File */