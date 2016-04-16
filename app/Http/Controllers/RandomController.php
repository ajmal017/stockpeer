<?php

namespace App\Http\Controllers;

use DB;
use App;
use Input;

class RandomController extends Controller 
{
  //
  // Backtest Futures - CL
  //
  public function bt_cl()
  {
    $bt = App::make('App\Backtesting\FuturesCL1Min');
    
    if(Input::get('start_date'))
    {
      $start_date = Input::get('start_date');
    } else
    {
      $start_date = '2016-01-01';
    }
  
    if(Input::get('end_date'))
    {
      $end_date = Input::get('end_date');
    } else
    {
      $end_date = '2016-12-31';
    }
  
    if(Input::get('cash'))
    {
      $cash = Input::get('cash');
    } else
    {
      $cash = 3000;
    }
    
    $bt->run([
      'start_date' => $start_date,
      'end_date' => $end_date,
      'cash' => $cash
    ]);
    
    return $bt->return_html();
  }
}

/* End File */