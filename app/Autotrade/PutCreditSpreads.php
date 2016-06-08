<?php
  
namespace App\Autotrade;

use DB;
use App;
use Auth;
use Queue;
use Input;
use Carbon\Carbon;

class PutCreditSpreads extends AutoTradeBase
{
  //
  // Construct.
  //
  public function __construct($cli, $time_base, $data_driver)
  {
    parent::__construct($cli, $time_base, $data_driver);
  }  
  
  //
  // Everytime we get data (1st a min) we manage the data.
  // We get an options chain.
  //
  // $now - Carbon instance of time. 
  // $data - Data that was returned from the data driver.  
  //
  public function on_data($now, $data)
  {
    echo '<pre>' . print_r(array_keys($data['2016-06-10']['209']), TRUE) . '</pre>';
    
    return true;
  }  
  
}

/* End File */