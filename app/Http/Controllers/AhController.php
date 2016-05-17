<?php

namespace App\Http\Controllers;

use Cache;
use View;

class AhController extends Controller 
{
  // 
  // Return the cached IVR
  //
  public function ivr()
  {
    $vix_rank_30 = Cache::get('Quotes.SnP500.Rank.30');
    $vix_rank_60 = Cache::get('Quotes.SnP500.Rank.60');
    $vix_rank_90 = Cache::get('Quotes.SnP500.Rank.90');
    $vix_rank_365 = Cache::get('Quotes.SnP500.Rank.365');            
    return [ 'ivr_30' => $vix_rank_30, 'ivr_60' => $vix_rank_60, 'ivr_90' => $vix_rank_90, 'ivr_365' => $vix_rank_365 ];
  }
}

/* End File */