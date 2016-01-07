<?php

namespace App\Models;

use DB;
use Auth;
use App;
use Queue;
use Cloudmanic\LaravelApi\Me;

class TradierHistory extends \Cloudmanic\LaravelApi\Model
{
  
  //
  // Format get.
  //
  public function _format_get(&$data)
  {
    $symbols_model = App::make('App\Models\Symbols');
    
    // Unpack Details
    if(isset($data['TradierHistoryDetails']) && (! empty($data['TradierHistoryDetails'])))
    {
      $data['Details'] = json_decode($data['TradierHistoryDetails'], true);
    } else
    {
      $data['Details'] = [];
    }
    
    // Add a symbol look up
    if(isset($data['Details']['symbol']))
    {
      $symbols_model->set_col('SymbolsShort', $data['Details']['symbol']);
      if($sym = $symbols_model->first($data['Details']['symbol']))
      {
        $data['Symbol'] = $sym;
      } else
      {
        $data['Symbol'] = [];
      }
    }
  }
}

/* End File */