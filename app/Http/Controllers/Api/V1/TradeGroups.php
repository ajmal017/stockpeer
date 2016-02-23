<?php

namespace App\Http\Controllers\Api\V1;

use App;
use Auth;
use Crypt;
use Input;
use Request;

class TradeGroups extends \Cloudmanic\LaravelApi\Controller 
{ 
	public $validation_create = [];
	public $validation_update = [];
	
  //
  // Get.
  //
  public function get()
  {
    // Did we pass in a magic filter?
    if(Input::get('filter'))
    {
      switch(Input::get('filter'))
      {
        case 'closed-only':
          $this->model->set_col('TradeGroupsStatus', 'Closed');
        break;
       
        case 'open-only':
          $this->model->set_col('TradeGroupsStatus', 'Open');
        break;
        
        case 'long-stock-only':
          $this->model->set_col('TradeGroupsType', 'Long Stock Trade');
        break; 

        case 'open-put-credit-spreads-only':
          $this->model->set_col('TradeGroupsStatus', 'Open');
          $this->model->set_col('TradeGroupsType', 'Put Credit Spread');
        break;

        case 'closed-put-credit-spreads-only':
          $this->model->set_col('TradeGroupsStatus', 'Closed');
          $this->model->set_col('TradeGroupsType', 'Put Credit Spread');
        break;
        
        case 'put-credit-spreads-only':
          $this->model->set_col('TradeGroupsType', 'Put Credit Spread');
        break;
        
        case 'weekly-put-credit-spreads-only':
          $this->model->set_col('TradeGroupsType', 'Weekly Put Credit Spread');
        break;
        
        case 'long-option-only':
          $this->model->set_col('TradeGroupsType', 'Long Option Trade');        
        break;                               
      }
    }
    
    // Special filter for excluding positions that are closed.
    if(Input::get('only-open-positions'))
    {
      $this->model->set_only_open_positions();
    }
    
    // Special filter for only getting Put credit spreads
    if(Input::get('only-put-credit-spreads'))
    {
      $this->model->set_col('TradeGroupsType', 'Put Credit Spread');
    }    

    // Special filter for only getting Call credit spreads
    if(Input::get('only-call-credit-spreads'))
    {
      $this->model->set_col('TradeGroupsType', 'Call Credit Spread');
    } 
    
    return parent::get();
  }
}

/* End File */