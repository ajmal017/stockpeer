<?php

namespace App\Models;
 
use DB;
use App;
use Cloudmanic\LaravelApi\Me;

class BackTests extends \Cloudmanic\LaravelApi\Model
{
  public $table = 'BackTests';
  public $backtesttrades_model = null;
  
  //
  // Construct.
  //
  public function __construct(App\Models\BackTestTrades $backtesttrades_model)
  {
    parent::__construct();
    
    $this->backtesttrades_model = $backtesttrades_model;
  }
  
  //
  // Format get.
  //
  public function _format_get(&$data)
  {
    // Add Trades
    $this->backtesttrades_model->set_order('BackTestTradesOpen', 'asc');
    $this->backtesttrades_model->set_col('BackTestTradesTestId', $data['BackTestsId']);
    $data['Trades'] = $this->backtesttrades_model->get();
  }
}

/* End File */